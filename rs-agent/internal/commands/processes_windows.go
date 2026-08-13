//go:build windows

package commands

import "github.com/portalsifast/rs-agent/internal/collector"

func listProcesses() ([]ProcessEntry, error) {
	raw, err := collector.ListAllProcesses()
	if err != nil {
		return nil, err
	}

	out := make([]ProcessEntry, len(raw))
	for i, row := range raw {
		out[i] = ProcessEntry{
			PID:        row.PID,
			Exe:        row.Exe,
			Path:       row.Path,
			User:       row.User,
			CPUPercent: row.CPUPercent,
		}
	}

	return out, nil
}
