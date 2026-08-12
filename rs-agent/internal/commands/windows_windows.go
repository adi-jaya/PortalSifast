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

var (
	user32                         = windows.NewLazySystemDLL("user32.dll")
	kernel32                       = windows.NewLazySystemDLL("kernel32.dll")
	procEnumWindows                = user32.NewProc("EnumWindows")
	procIsWindowVisible            = user32.NewProc("IsWindowVisible")
	procGetWindowTextW             = user32.NewProc("GetWindowTextW")
	procGetWindowTextLengthW       = user32.NewProc("GetWindowTextLengthW")
	procGetWindowThreadProcessId   = user32.NewProc("GetWindowThreadProcessId")
	procQueryFullProcessImageNameW = kernel32.NewProc("QueryFullProcessImageNameW")
	enumMu                         sync.Mutex
	enumAcc                        []Window
)

func listWindows() ([]Window, error) {
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

	handle, err := windows.OpenProcess(windows.PROCESS_TERMINATE, false, pid)
	if err != nil {
		return filepath.Base(exe), fmt.Errorf("open process: %w", err)
	}
	defer windows.CloseHandle(handle)

	if err := windows.TerminateProcess(handle, 1); err != nil {
		return filepath.Base(exe), fmt.Errorf("terminate: %w", err)
	}
	return filepath.Base(exe), nil
}
