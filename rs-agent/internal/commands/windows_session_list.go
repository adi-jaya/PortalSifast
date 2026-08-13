//go:build windows

package commands

import (
	"bytes"
	"encoding/json"
	"fmt"
	"os"
	"path/filepath"
	"strings"
	"unsafe"

	"golang.org/x/sys/windows"
)

const (
	createUnicodeEnvironment = 0x00000400
	createNoWindow           = 0x08000000
	createNewConsole         = 0x00000010
)

var (
	userenv                     = windows.NewLazySystemDLL("userenv.dll")
	procCreateEnvironmentBlock  = userenv.NewProc("CreateEnvironmentBlock")
	procDestroyEnvironmentBlock = userenv.NewProc("DestroyEnvironmentBlock")
	procDuplicateTokenEx        = advapi32.NewProc("DuplicateTokenEx")
)

type windowJSON struct {
	PID   uint32 `json:"pid"`
	Exe   string `json:"exe"`
	Title string `json:"title"`
}

// listWindowsInUserSession launches PowerShell in the interactive user session
// so MainWindowTitle sees the real desktop (Session 0 services cannot EnumWindows).
func listWindowsInUserSession(sessionID uint32) ([]Window, error) {
	var userToken windows.Token
	r, _, err := procWTSQueryUserToken.Call(uintptr(sessionID), uintptr(unsafe.Pointer(&userToken)))
	if r == 0 {
		return nil, fmt.Errorf("WTSQueryUserToken: %w", err)
	}
	defer userToken.Close()

	primary, err := duplicatePrimaryToken(userToken)
	if err != nil {
		return nil, err
	}
	defer primary.Close()

	publicDir := os.Getenv("PUBLIC")
	if publicDir == "" {
		publicDir = `C:\Users\Public`
	}
	outPath := filepath.Join(publicDir, fmt.Sprintf("portalsifast-windows-%d.json", sessionID))
	_ = os.Remove(outPath)
	defer os.Remove(outPath)

	ps := windowsPowerShellPath()
	script := fmt.Sprintf(
		`$ErrorActionPreference='SilentlyContinue'; `+
			`$rows = @(Get-Process | Where-Object { $_.MainWindowTitle -and $_.MainWindowTitle.Trim() -ne '' } | `+
			`ForEach-Object { [pscustomobject]@{ pid = $_.Id; exe = ($_.ProcessName + '.exe'); title = $_.MainWindowTitle } }); `+
			`($rows | ConvertTo-Json -Compress -Depth 3) | Set-Content -LiteralPath '%s' -Encoding UTF8`,
		escapePSPath(outPath),
	)

	cmdLine, err := windows.UTF16PtrFromString(fmt.Sprintf(
		`"%s" -NoProfile -NonInteractive -ExecutionPolicy Bypass -Command %s`,
		ps,
		quoteForCmd(script),
	))
	if err != nil {
		return nil, err
	}

	var env *uint16
	if er, _, e := procCreateEnvironmentBlock.Call(uintptr(unsafe.Pointer(&env)), uintptr(primary), 0); er == 0 {
		return nil, fmt.Errorf("CreateEnvironmentBlock: %w", e)
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
	err = windows.CreateProcessAsUser(
		primary,
		nil,
		cmdLine,
		nil,
		nil,
		false,
		flags,
		env,
		nil,
		&si,
		&pi,
	)
	if err != nil {
		flags = uint32(createUnicodeEnvironment | createNewConsole)
		err = windows.CreateProcessAsUser(
			primary,
			nil,
			cmdLine,
			nil,
			nil,
			false,
			flags,
			env,
			nil,
			&si,
			&pi,
		)
		if err != nil {
			return nil, fmt.Errorf("CreateProcessAsUser: %w", err)
		}
	}
	defer windows.CloseHandle(pi.Thread)
	defer windows.CloseHandle(pi.Process)

	wait, err := windows.WaitForSingleObject(pi.Process, 15000)
	if err != nil {
		return nil, fmt.Errorf("wait: %w", err)
	}
	if wait != windows.WAIT_OBJECT_0 {
		_ = windows.TerminateProcess(pi.Process, 1)
		return nil, fmt.Errorf("list windows helper timed out")
	}

	raw, err := os.ReadFile(outPath)
	if err != nil {
		return nil, fmt.Errorf("read helper output: %w", err)
	}
	raw = bytes.TrimPrefix(bytes.TrimSpace(raw), []byte{0xEF, 0xBB, 0xBF})
	if len(raw) == 0 || string(raw) == "null" {
		return []Window{}, nil
	}

	var rows []windowJSON
	if err := json.Unmarshal(raw, &rows); err != nil {
		var one windowJSON
		if err2 := json.Unmarshal(raw, &one); err2 != nil {
			return nil, fmt.Errorf("parse helper json: %w (body=%q)", err, truncate(string(raw), 200))
		}
		rows = []windowJSON{one}
	}

	out := make([]Window, 0, len(rows))
	for _, row := range rows {
		title := strings.TrimSpace(row.Title)
		exe := strings.TrimSpace(row.Exe)
		if row.PID == 0 || title == "" {
			continue
		}
		if exe == "" {
			exe = "unknown.exe"
		}
		out = append(out, Window{PID: row.PID, Exe: exe, Title: title})
	}
	return out, nil
}

func duplicatePrimaryToken(token windows.Token) (windows.Token, error) {
	var primary windows.Token
	const securityImpersonation = 2
	const tokenPrimary = 1
	r, _, err := procDuplicateTokenEx.Call(
		uintptr(token),
		windows.MAXIMUM_ALLOWED,
		0,
		uintptr(securityImpersonation),
		uintptr(tokenPrimary),
		uintptr(unsafe.Pointer(&primary)),
	)
	if r == 0 {
		if err != nil && err != windows.ERROR_SUCCESS {
			return 0, fmt.Errorf("DuplicateTokenEx: %w", err)
		}
		return 0, fmt.Errorf("DuplicateTokenEx failed")
	}
	return primary, nil
}

func windowsPowerShellPath() string {
	systemRoot := os.Getenv("SystemRoot")
	if systemRoot == "" {
		systemRoot = `C:\Windows`
	}
	return filepath.Join(systemRoot, "System32", "WindowsPowerShell", "v1.0", "powershell.exe")
}

func escapePSPath(path string) string {
	return strings.ReplaceAll(path, "'", "''")
}

func quoteForCmd(script string) string {
	return `"` + strings.ReplaceAll(script, `"`, `\"`) + `"`
}

func truncate(s string, n int) string {
	if len(s) <= n {
		return s
	}
	return s[:n] + "…"
}
