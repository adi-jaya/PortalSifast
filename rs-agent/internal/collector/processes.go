package collector

type ProcessEntry struct {
	PID        uint32  `json:"pid"`
	Exe        string  `json:"exe"`
	Path       string  `json:"path,omitempty"`
	User       string  `json:"user,omitempty"`
	CPUPercent float64 `json:"cpu_percent,omitempty"`
}

type SuspiciousProcess struct {
	PID        uint32   `json:"pid"`
	Exe        string   `json:"exe"`
	Path       string   `json:"path,omitempty"`
	CPUPercent float64  `json:"cpu_percent,omitempty"`
	Reasons    []string `json:"reasons"`
}
