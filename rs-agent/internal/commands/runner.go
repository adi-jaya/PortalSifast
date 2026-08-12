package commands

import (
	"errors"
	"fmt"
)

const (
	TypeListWindows = "list_windows"
	TypeKillPID     = "kill_pid"
	StatusSucceeded = "succeeded"
	StatusFailed    = "failed"
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
	ListWindows func() ([]Window, error)
	LookupExe   func(pid uint32) (string, error)
	KillPID     func(pid uint32) (exe string, err error)
}

func NewRunner() *Runner {
	return &Runner{
		ListWindows: listWindows,
		LookupExe:   processImageName,
		KillPID:     killPID,
	}
}

func (r *Runner) Run(cmd Command) (status string, result map[string]any) {
	switch cmd.Type {
	case TypeListWindows:
		return r.runListWindows()
	case TypeKillPID:
		return r.runKillPID(cmd.Payload)
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
		return StatusFailed, map[string]any{"error": err.Error()}
	}
	if windows == nil {
		windows = []Window{}
	}
	return StatusSucceeded, map[string]any{"windows": windows}
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
