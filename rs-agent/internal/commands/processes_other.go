//go:build !windows

package commands

import (
	"fmt"
)

func listProcesses() ([]ProcessEntry, error) {
	return nil, fmt.Errorf("list_processes hanya didukung di Windows")
}
