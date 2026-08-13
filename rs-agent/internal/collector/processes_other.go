//go:build !windows

package collector

func collectSuspiciousProcesses() []SuspiciousProcess {
	return nil
}

func ListAllProcesses() ([]ProcessEntry, error) {
	return []ProcessEntry{}, nil
}
