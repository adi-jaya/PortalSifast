package commands

import (
	"errors"
	"fmt"
)

const (
	TypeListWindows    = "list_windows"
	TypeListProcesses  = "list_processes"
	TypeKillPID        = "kill_pid"
	TypeCaptureDesktop = "capture_desktop"
	StatusSucceeded    = "succeeded"
	StatusFailed       = "failed"
)

type Window struct {
	PID   uint32 `json:"pid"`
	Exe   string `json:"exe"`
	Title string `json:"title"`
}

type Command struct {
	ID      uint64         `json:"id"`
	Type    string         `json:"type"`
	Payload map[string]any `json:"payload"`
}

var ErrBlocked = errors.New("blocked")

type Runner struct {
	ListWindows    func() ([]Window, error)
	ListProcesses  func() ([]ProcessEntry, error)
	LookupExe      func(pid uint32) (string, error)
	KillPID        func(pid uint32) (exe string, err error)
	CaptureDesktop func() (DesktopCapture, error)
}

type ProcessEntry struct {
	PID        uint32  `json:"pid"`
	Exe        string  `json:"exe"`
	Path       string  `json:"path,omitempty"`
	User       string  `json:"user,omitempty"`
	CPUPercent float64 `json:"cpu_percent,omitempty"`
}

func NewRunner() *Runner {
	return &Runner{
		ListWindows:    listWindows,
		ListProcesses:  listProcesses,
		LookupExe:      processImageName,
		KillPID:        killPID,
		CaptureDesktop: captureDesktop,
	}
}

func (r *Runner) Run(cmd Command) (status string, result map[string]any) {
	switch cmd.Type {
	case TypeListWindows:
		return r.runListWindows()
	case TypeListProcesses:
		return r.runListProcesses()
	case TypeKillPID:
		return r.runKillPID(cmd.Payload)
	case TypeCaptureDesktop:
		return r.runCaptureDesktop()
	default:
		return StatusFailed, map[string]any{"error": fmt.Sprintf("unknown command %s", cmd.Type)}
	}
}

func (r *Runner) runListWindows() (string, map[string]any) {
	if r.ListWindows == nil {
		return StatusFailed, map[string]any{"error": "list_windows unavailable"}
	}
	windows, err := r.ListWindows()
	if err != nil {
		return StatusFailed, map[string]any{"error": err.Error(), "windows": []Window{}}
	}
	if windows == nil {
		windows = []Window{}
	}
	result := map[string]any{"windows": windows}
	if len(windows) == 0 {
		result["note"] = "no visible titled windows (service may lack desktop access; use list_processes)"
	}
	return StatusSucceeded, result
}

func (r *Runner) runListProcesses() (string, map[string]any) {
	if r.ListProcesses == nil {
		return StatusFailed, map[string]any{"error": "list_processes unavailable"}
	}
	processes, err := r.ListProcesses()
	if err != nil {
		return StatusFailed, map[string]any{"error": err.Error()}
	}
	if processes == nil {
		processes = []ProcessEntry{}
	}
	return StatusSucceeded, map[string]any{"processes": processes}
}

func (r *Runner) runKillPID(payload map[string]any) (string, map[string]any) {
	pid, ok := PayloadPID(payload)
	if !ok {
		return StatusFailed, map[string]any{"error": "invalid pid"}
	}

	exe := PayloadExe(payload)
	if r.LookupExe != nil {
		if lookedUp, err := r.LookupExe(pid); err == nil && lookedUp != "" {
			exe = lookedUp
		}
	}

	if IsBlocked(exe) {
		return StatusFailed, map[string]any{
			"error": ErrBlocked.Error(),
			"pid":   pid,
			"exe":   NormalizedExe(exe),
		}
	}

	if r.KillPID == nil {
		return StatusFailed, map[string]any{"error": "kill_pid unavailable"}
	}

	killedExe, err := r.KillPID(pid)
	if err != nil {
		out := map[string]any{
			"error": err.Error(),
			"pid":   pid,
		}
		if killedExe != "" {
			out["exe"] = NormalizedExe(killedExe)
		} else if exe != "" {
			out["exe"] = NormalizedExe(exe)
		}
		if errors.Is(err, ErrBlocked) {
			out["error"] = ErrBlocked.Error()
		}
		return StatusFailed, out
	}

	return StatusSucceeded, map[string]any{
		"pid": pid,
		"exe": NormalizedExe(killedExe),
	}
}

func (r *Runner) runCaptureDesktop() (string, map[string]any) {
	if r.CaptureDesktop == nil {
		return StatusFailed, map[string]any{"error": "capture_desktop unavailable"}
	}
	cap, err := r.CaptureDesktop()
	if err != nil {
		return StatusFailed, map[string]any{"error": err.Error()}
	}
	return StatusSucceeded, desktopCaptureResult(cap)
}
