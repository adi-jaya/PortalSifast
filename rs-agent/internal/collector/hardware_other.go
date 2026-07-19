//go:build !windows

package collector

func platformHardware() platformHW {
	return platformHW{}
}
