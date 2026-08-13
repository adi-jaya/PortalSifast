//go:build windows

package commands

import (
	"fmt"
	"os"
	"path/filepath"
	"strings"
	"unsafe"

	"golang.org/x/sys/windows"
)

// killPIDInUserSession terminates a process (and children) from the interactive user session.
func killPIDInUserSession(sessionID, pid uint32) error {
	var userToken windows.Token
	r, _, err := procWTSQueryUserToken.Call(uintptr(sessionID), uintptr(unsafe.Pointer(&userToken)))
	if r == 0 {
		return fmt.Errorf("WTSQueryUserToken: %w", err)
	}
	defer userToken.Close()

	primary, err := duplicatePrimaryToken(userToken)
	if err != nil {
		return err
	}
	defer primary.Close()

	publicDir := os.Getenv("PUBLIC")
	if publicDir == "" {
		publicDir = `C:\Users\Public`
	}
	resultPath := filepath.Join(publicDir, fmt.Sprintf("portalsifast-kill-%d-%d.txt", sessionID, pid))
	_ = os.Remove(resultPath)
	defer os.Remove(resultPath)

	ps := windowsPowerShellPath()
	script := fmt.Sprintf(
		`$ErrorActionPreference='Stop'; try { Stop-Process -Id %d -Force -ErrorAction Stop; 'ok' | Set-Content -LiteralPath '%s' -Encoding ASCII } catch { $_.Exception.Message | Set-Content -LiteralPath '%s' -Encoding ASCII; exit 1 }`,
		pid,
		escapePSPath(resultPath),
		escapePSPath(resultPath),
	)

	cmdLine, err := windows.UTF16PtrFromString(fmt.Sprintf(
		`"%s" -NoProfile -NonInteractive -ExecutionPolicy Bypass -Command %s`,
		ps,
		quoteForCmd(script),
	))
	if err != nil {
		return err
	}

	var env *uint16
	if er, _, e := procCreateEnvironmentBlock.Call(uintptr(unsafe.Pointer(&env)), uintptr(primary), 0); er == 0 {
		return fmt.Errorf("CreateEnvironmentBlock: %w", e)
	}
	defer procDestroyEnvironmentBlock.Call(uintptr(unsafe.Pointer(env)))

	var si windows.StartupInfo
	si.Cb = uint32(unsafe.Sizeof(si))
	si.Flags = windows.STARTF_USESHOWWINDOW
	si.ShowWindow = windows.SW_HIDE
	desktop, _ := windows.UTF16PtrFromString(`winsta0\default`)
	si.Desktop = desktop

	var pi windows.ProcessInformation
	flags := uint32(createUnicodeEnvironment | createNoWindow)
	err = windows.CreateProcessAsUser(primary, nil, cmdLine, nil, nil, false, flags, env, nil, &si, &pi)
	if err != nil {
		flags = uint32(createUnicodeEnvironment | createNewConsole)
		err = windows.CreateProcessAsUser(primary, nil, cmdLine, nil, nil, false, flags, env, nil, &si, &pi)
		if err != nil {
			return fmt.Errorf("CreateProcessAsUser: %w", err)
		}
	}
	defer windows.CloseHandle(pi.Thread)
	defer windows.CloseHandle(pi.Process)

	wait, err := windows.WaitForSingleObject(pi.Process, 15000)
	if err != nil {
		return fmt.Errorf("wait kill helper: %w", err)
	}
	if wait != windows.WAIT_OBJECT_0 {
		_ = windows.TerminateProcess(pi.Process, 1)
		return fmt.Errorf("kill helper timed out")
	}

	raw, _ := os.ReadFile(resultPath)
	msg := strings.TrimSpace(string(raw))
	if msg != "" && msg != "ok" {
		return fmt.Errorf("%s", msg)
	}
	if msg == "" {
		return fmt.Errorf("kill helper produced no result")
	}
	return nil
}
