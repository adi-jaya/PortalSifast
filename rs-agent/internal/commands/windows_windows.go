//go:build windows

package commands

import (
	"fmt"
	"path/filepath"
	"strings"
	"sync"
	"syscall"
	"unsafe"

	"golang.org/x/sys/windows"
)

const (
	wtsActive    = 0
	wtsConnected = 1

	desktopReadObjects = 0x0001
)

var (
	user32                           = windows.NewLazySystemDLL("user32.dll")
	kernel32                         = windows.NewLazySystemDLL("kernel32.dll")
	wtsapi32                         = windows.NewLazySystemDLL("wtsapi32.dll")
	advapi32                         = windows.NewLazySystemDLL("advapi32.dll")
	procImpersonateLoggedOnUser      = advapi32.NewProc("ImpersonateLoggedOnUser")
	procRevertToSelf                 = advapi32.NewProc("RevertToSelf")
	procEnumWindows                  = user32.NewProc("EnumWindows")
	procIsWindowVisible              = user32.NewProc("IsWindowVisible")
	procGetWindowTextW               = user32.NewProc("GetWindowTextW")
	procGetWindowTextLengthW         = user32.NewProc("GetWindowTextLengthW")
	procGetWindowThreadProcessId     = user32.NewProc("GetWindowThreadProcessId")
	procOpenInputDesktop             = user32.NewProc("OpenInputDesktop")
	procSetThreadDesktop             = user32.NewProc("SetThreadDesktop")
	procCloseDesktop                 = user32.NewProc("CloseDesktop")
	procQueryFullProcessImageNameW   = kernel32.NewProc("QueryFullProcessImageNameW")
	procWTSGetActiveConsoleSessionId = kernel32.NewProc("WTSGetActiveConsoleSessionId")
	procWTSQueryUserToken            = wtsapi32.NewProc("WTSQueryUserToken")
	procWTSEnumerateSessionsW        = wtsapi32.NewProc("WTSEnumerateSessionsW")
	procWTSFreeMemory                = wtsapi32.NewProc("WTSFreeMemory")
	enumMu                           sync.Mutex
	enumAcc                          []Window
)

type wtsSessionInfo struct {
	sessionID uint32
	state     uint32
}

// wtsSessionInfoNative matches WTS_SESSION_INFOW layout on amd64.
type wtsSessionInfoNative struct {
	sessionID       uint32
	_               uint32
	pWinStationName *uint16
	state           uint32
}

func listWindows() ([]Window, error) {
	sessionIDs, sessErr := activeUserSessionIDs()

	seen := make(map[string]struct{})
	out := make([]Window, 0)
	add := func(wins []Window) {
		for _, w := range wins {
			key := fmt.Sprintf("%d:%s:%s", w.PID, w.Exe, w.Title)
			if _, ok := seen[key]; ok {
				continue
			}
			seen[key] = struct{}{}
			out = append(out, w)
		}
	}

	if sessErr == nil {
		// Preferred when running as LocalSystem service: helper in user session.
		for _, sessionID := range sessionIDs {
			wins, err := listWindowsInUserSession(sessionID)
			if err != nil {
				continue
			}
			add(wins)
		}
		if len(out) > 0 {
			return out, nil
		}

		// Fallback: impersonate + SetThreadDesktop + EnumWindows.
		for _, sessionID := range sessionIDs {
			wins, err := listWindowsInSession(sessionID)
			if err != nil {
				continue
			}
			add(wins)
		}
		if len(out) > 0 {
			return out, nil
		}
	}

	// Last resort: direct EnumWindows (works for interactive / non-service runs).
	direct, err := enumVisibleWindows()
	if err != nil {
		if len(out) == 0 && sessErr != nil {
			return nil, sessErr
		}
		return out, nil
	}
	add(direct)
	return out, nil
}

func activeUserSessionIDs() ([]uint32, error) {
	ids := make(map[uint32]struct{})

	if console, _, _ := procWTSGetActiveConsoleSessionId.Call(); console != 0 && console != ^uintptr(0) {
		ids[uint32(console)] = struct{}{}
	}

	for _, info := range enumerateWTSSessions() {
		if info.sessionID == 0 {
			continue
		}
		if info.state != wtsActive && info.state != wtsConnected {
			continue
		}
		ids[info.sessionID] = struct{}{}
	}

	if len(ids) == 0 {
		return nil, fmt.Errorf("no active user session")
	}

	out := make([]uint32, 0, len(ids))
	for id := range ids {
		out = append(out, id)
	}
	return out, nil
}

func enumerateWTSSessions() []wtsSessionInfo {
	var (
		pSessionInfo *wtsSessionInfoNative
		count        uint32
	)
	r, _, _ := procWTSEnumerateSessionsW.Call(
		0,
		0,
		1,
		uintptr(unsafe.Pointer(&pSessionInfo)),
		uintptr(unsafe.Pointer(&count)),
	)
	if r == 0 || count == 0 || pSessionInfo == nil {
		return nil
	}
	defer procWTSFreeMemory.Call(uintptr(unsafe.Pointer(pSessionInfo)))

	entries := unsafe.Slice(pSessionInfo, count)
	out := make([]wtsSessionInfo, 0, count)
	for _, entry := range entries {
		out = append(out, wtsSessionInfo{sessionID: entry.sessionID, state: entry.state})
	}
	return out
}

func listWindowsInSession(sessionID uint32) ([]Window, error) {
	var token windows.Token
	r, _, err := procWTSQueryUserToken.Call(uintptr(sessionID), uintptr(unsafe.Pointer(&token)))
	if r == 0 {
		return nil, fmt.Errorf("WTSQueryUserToken session %d: %w", sessionID, err)
	}
	defer token.Close()

	if err := impersonateLoggedOnUser(token); err != nil {
		return nil, fmt.Errorf("impersonate session %d: %w", sessionID, err)
	}
	defer revertToSelf()

	if err := bindInputDesktop(); err != nil {
		return nil, fmt.Errorf("bind desktop session %d: %w", sessionID, err)
	}

	return enumVisibleWindows()
}

func bindInputDesktop() error {
	hDesktop, _, err := procOpenInputDesktop.Call(0, 0, desktopReadObjects)
	if hDesktop == 0 {
		if err != nil && err != windows.ERROR_SUCCESS {
			return err
		}
		return fmt.Errorf("OpenInputDesktop failed")
	}
	defer procCloseDesktop.Call(hDesktop)

	r, _, err := procSetThreadDesktop.Call(hDesktop)
	if r == 0 {
		if err != nil && err != windows.ERROR_SUCCESS {
			return err
		}
		return fmt.Errorf("SetThreadDesktop failed")
	}
	return nil
}

func enumVisibleWindows() ([]Window, error) {
	enumMu.Lock()
	defer enumMu.Unlock()
	enumAcc = nil

	cb := syscall.NewCallback(enumWindowsProc)
	r, _, err := procEnumWindows.Call(cb, 0)
	if r == 0 && err != windows.ERROR_SUCCESS {
		return nil, fmt.Errorf("enum windows: %w", err)
	}

	out := make([]Window, len(enumAcc))
	copy(out, enumAcc)
	return out, nil
}

func enumWindowsProc(hwnd uintptr, _ uintptr) uintptr {
	visible, _, _ := procIsWindowVisible.Call(hwnd)
	if visible == 0 {
		return 1
	}

	length, _, _ := procGetWindowTextLengthW.Call(hwnd)
	if length == 0 {
		return 1
	}

	buf := make([]uint16, length+1)
	procGetWindowTextW.Call(hwnd, uintptr(unsafe.Pointer(&buf[0])), uintptr(len(buf)))
	title := strings.TrimSpace(windows.UTF16ToString(buf))
	if title == "" {
		return 1
	}

	var pid uint32
	procGetWindowThreadProcessId.Call(hwnd, uintptr(unsafe.Pointer(&pid)))
	if pid == 0 {
		return 1
	}

	exe, _ := processImageName(pid)
	enumAcc = append(enumAcc, Window{
		PID:   pid,
		Exe:   filepath.Base(exe),
		Title: title,
	})
	return 1
}

func processImageName(pid uint32) (string, error) {
	handle, err := windows.OpenProcess(windows.PROCESS_QUERY_LIMITED_INFORMATION, false, pid)
	if err != nil {
		return "", err
	}
	defer windows.CloseHandle(handle)

	size := uint32(32768)
	buf := make([]uint16, size)
	r, _, callErr := procQueryFullProcessImageNameW.Call(
		uintptr(handle),
		0,
		uintptr(unsafe.Pointer(&buf[0])),
		uintptr(unsafe.Pointer(&size)),
	)
	if r == 0 {
		if callErr != nil {
			return "", callErr
		}
		return "", fmt.Errorf("query process image name failed")
	}
	return windows.UTF16ToString(buf), nil
}

func killPID(pid uint32) (string, error) {
	exe, err := processImageName(pid)
	if err != nil {
		return "", fmt.Errorf("lookup process: %w", err)
	}
	if IsBlocked(exe) {
		return filepath.Base(exe), ErrBlocked
	}

	// Preferred: kill from interactive user session (works for GUI apps from Session 0 service).
	for _, sessionID := range mustActiveUserSessionIDs() {
		if killErr := killPIDInUserSession(sessionID, pid); killErr == nil {
			return filepath.Base(exe), nil
		}
	}

	if err := terminateProcess(pid); err == nil {
		return filepath.Base(exe), nil
	}

	for _, sessionID := range mustActiveUserSessionIDs() {
		if killed, killErr := killPIDInSession(sessionID, pid, exe); killErr == nil {
			return killed, nil
		}
	}

	return filepath.Base(exe), fmt.Errorf("terminate pid %d: access denied", pid)
}

func mustActiveUserSessionIDs() []uint32 {
	ids, err := activeUserSessionIDs()
	if err != nil {
		return nil
	}
	return ids
}

func killPIDInSession(sessionID, pid uint32, exe string) (string, error) {
	var token windows.Token
	r, _, err := procWTSQueryUserToken.Call(uintptr(sessionID), uintptr(unsafe.Pointer(&token)))
	if r == 0 {
		return "", err
	}
	defer token.Close()

	if err := impersonateLoggedOnUser(token); err != nil {
		return "", err
	}
	defer revertToSelf()

	if err := terminateProcess(pid); err != nil {
		return filepath.Base(exe), err
	}
	return filepath.Base(exe), nil
}

func terminateProcess(pid uint32) error {
	handle, err := windows.OpenProcess(windows.PROCESS_TERMINATE, false, pid)
	if err != nil {
		return err
	}
	defer windows.CloseHandle(handle)
	return windows.TerminateProcess(handle, 1)
}

func impersonateLoggedOnUser(token windows.Token) error {
	r, _, err := procImpersonateLoggedOnUser.Call(uintptr(token))
	if r == 0 {
		if err != nil && err != windows.ERROR_SUCCESS {
			return err
		}
		return fmt.Errorf("ImpersonateLoggedOnUser failed")
	}
	return nil
}

func revertToSelf() {
	_, _, _ = procRevertToSelf.Call()
}
