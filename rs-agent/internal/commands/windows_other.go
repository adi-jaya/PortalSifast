//go:build !windows

package commands

import "fmt"

func listWindows() ([]Window, error) {
	return nil, fmt.Errorf("list_windows hanya didukung di Windows")
}

func processImageName(pid uint32) (string, error) {
	return "", fmt.Errorf("lookup process hanya didukung di Windows")
}

func killPID(pid uint32) (string, error) {
	return "", fmt.Errorf("kill_pid hanya didukung di Windows")
}
