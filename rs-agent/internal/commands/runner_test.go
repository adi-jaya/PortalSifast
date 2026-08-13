package commands

import (
	"errors"
	"testing"
)

func TestRunnerListWindows(t *testing.T) {
	t.Parallel()

	runner := &Runner{
		ListWindows: func() ([]Window, error) {
			return []Window{{PID: 10, Exe: "notepad.exe", Title: "Untitled"}}, nil
		},
	}

	status, result := runner.Run(Command{ID: 1, Type: TypeListWindows})
	if status != StatusSucceeded {
		t.Fatalf("status=%s result=%v", status, result)
	}
	windows, ok := result["windows"].([]Window)
	if !ok || len(windows) != 1 || windows[0].Exe != "notepad.exe" {
		t.Fatalf("windows=%v", result["windows"])
	}
}

func TestRunnerKillBlocksCriticalProcess(t *testing.T) {
	t.Parallel()

	killed := false
	runner := &Runner{
		LookupExe: func(pid uint32) (string, error) {
			return `C:\Windows\System32\lsass.exe`, nil
		},
		KillPID: func(pid uint32) (string, error) {
			killed = true
			return "lsass.exe", nil
		},
	}

	status, result := runner.Run(Command{
		ID:      2,
		Type:    TypeKillPID,
		Payload: map[string]any{"pid": float64(4), "exe": "lsass.exe"},
	})
	if status != StatusFailed {
		t.Fatalf("status=%s", status)
	}
	if result["error"] != "blocked" {
		t.Fatalf("result=%v", result)
	}
	if killed {
		t.Fatal("must not kill blocked process")
	}
}

func TestRunnerKillAllowsNormalProcess(t *testing.T) {
	t.Parallel()

	runner := &Runner{
		LookupExe: func(pid uint32) (string, error) {
			return `C:\Program Files\Google\Chrome\Application\chrome.exe`, nil
		},
		KillPID: func(pid uint32) (string, error) {
			return "chrome.exe", nil
		},
	}

	status, result := runner.Run(Command{
		ID:      3,
		Type:    TypeKillPID,
		Payload: map[string]any{"pid": float64(4242)},
	})
	if status != StatusSucceeded {
		t.Fatalf("status=%s result=%v", status, result)
	}
	if result["exe"] != "chrome.exe" {
		t.Fatalf("exe=%v", result["exe"])
	}
}

func TestRunnerUnknownType(t *testing.T) {
	t.Parallel()

	status, _ := (&Runner{}).Run(Command{Type: "wipe_disk"})
	if status != StatusFailed {
		t.Fatalf("status=%s", status)
	}
}

func TestRunnerKillPIDInvalid(t *testing.T) {
	t.Parallel()

	status, result := (&Runner{}).Run(Command{Type: TypeKillPID, Payload: map[string]any{}})
	if status != StatusFailed || result["error"] != "invalid pid" {
		t.Fatalf("status=%s result=%v", status, result)
	}
}

func TestRunnerKillPropagatesBlockedError(t *testing.T) {
	t.Parallel()

	runner := &Runner{
		LookupExe: func(uint32) (string, error) { return "notepad.exe", nil },
		KillPID:   func(uint32) (string, error) { return "explorer.exe", ErrBlocked },
	}

	status, result := runner.Run(Command{Type: TypeKillPID, Payload: map[string]any{"pid": 99}})
	if status != StatusFailed || result["error"] != "blocked" {
		t.Fatalf("status=%s result=%v", status, result)
	}
}

func TestRunnerListProcesses(t *testing.T) {
	t.Parallel()

	runner := &Runner{
		ListProcesses: func() ([]ProcessEntry, error) {
			return []ProcessEntry{{PID: 42, Exe: "chrome.exe", CPUPercent: 1.2}}, nil
		},
	}

	status, result := runner.Run(Command{ID: 4, Type: TypeListProcesses})
	if status != StatusSucceeded {
		t.Fatalf("status=%s result=%v", status, result)
	}
	processes, ok := result["processes"].([]ProcessEntry)
	if !ok || len(processes) != 1 || processes[0].Exe != "chrome.exe" {
		t.Fatalf("processes=%v", result["processes"])
	}
}

func TestRunnerListWindowsError(t *testing.T) {
	t.Parallel()

	runner := &Runner{ListWindows: func() ([]Window, error) { return nil, errors.New("boom") }}
	status, result := runner.Run(Command{Type: TypeListWindows})
	if status != StatusFailed || result["error"] != "boom" {
		t.Fatalf("status=%s result=%v", status, result)
	}
}
