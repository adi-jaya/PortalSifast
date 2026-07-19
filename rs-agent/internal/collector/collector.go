package collector

import (
	"fmt"
	"net"
	"os"
	"runtime"
	"time"

	"github.com/shirou/gopsutil/v4/cpu"
	"github.com/shirou/gopsutil/v4/disk"
	"github.com/shirou/gopsutil/v4/host"
	"github.com/shirou/gopsutil/v4/mem"
)

type Snapshot struct {
	UUID         string
	Hostname     string
	ComputerName string
	IPAddress    string
	MACAddress   string
	AgentVersion string
	Hardware     Hardware
	Metrics      Metrics
}

type Hardware struct {
	OS           string
	OSVersion    string
	Architecture string
	CPUModel     string
	CPUCores     int
	RAMTotalMB   uint64
	DiskTotalGB  uint64
	Manufacturer string
	Model        string
	SerialNumber string
	Motherboard  string
	BIOS         string
	BootTime     time.Time
	Timezone     string
	Domain       string
	Username     string
}

type Metrics struct {
	CPUPercent    float64
	RAMPercent    float64
	DiskPercent   float64
	UptimeSeconds uint64
}

func Collect(deviceUUID, agentVersion string) (Snapshot, error) {
	hostname, _ := os.Hostname()
	info, err := host.Info()
	if err != nil {
		return Snapshot{}, fmt.Errorf("host info: %w", err)
	}

	vm, err := mem.VirtualMemory()
	if err != nil {
		return Snapshot{}, fmt.Errorf("memory: %w", err)
	}

	// Short interval keeps idle CPU low while still producing a sample.
	cpuPercents, err := cpu.Percent(200*time.Millisecond, false)
	if err != nil {
		return Snapshot{}, fmt.Errorf("cpu: %w", err)
	}

	cpuInfo, _ := cpu.Info()
	cpuModel := ""
	cores := runtime.NumCPU()
	if len(cpuInfo) > 0 {
		cpuModel = cpuInfo[0].ModelName
		if cpuInfo[0].Cores > 0 {
			cores = int(cpuInfo[0].Cores)
		}
	}

	usage, err := disk.Usage("/")
	if err != nil {
		// Windows-friendly fallback; ignore if both fail later.
		usage, err = disk.Usage("C:\\")
		if err != nil {
			return Snapshot{}, fmt.Errorf("disk: %w", err)
		}
	}

	ip, mac := primaryNet()
	tz, _ := time.Now().Zone()
	boot := time.Unix(int64(info.BootTime), 0)

	cpuPct := 0.0
	if len(cpuPercents) > 0 {
		cpuPct = cpuPercents[0]
	}

	return Snapshot{
		UUID:         deviceUUID,
		Hostname:     hostname,
		ComputerName: hostname,
		IPAddress:    ip,
		MACAddress:   mac,
		AgentVersion: agentVersion,
		Hardware: Hardware{
			OS:           info.Platform,
			OSVersion:    info.PlatformVersion,
			Architecture: runtime.GOARCH,
			CPUModel:     cpuModel,
			CPUCores:     cores,
			RAMTotalMB:   vm.Total / (1024 * 1024),
			DiskTotalGB:  usage.Total / (1024 * 1024 * 1024),
			BootTime:     boot,
			Timezone:     tz,
			Username:     currentUsername(),
			SerialNumber: info.HostID,
		},
		Metrics: Metrics{
			CPUPercent:    cpuPct,
			RAMPercent:    vm.UsedPercent,
			DiskPercent:   usage.UsedPercent,
			UptimeSeconds: info.Uptime,
		},
	}, nil
}

func currentUsername() string {
	if u := os.Getenv("USERNAME"); u != "" {
		return u
	}
	return os.Getenv("USER")
}

func primaryNet() (ip, mac string) {
	ifaces, err := net.Interfaces()
	if err != nil {
		return "", ""
	}

	for _, iface := range ifaces {
		if iface.Flags&net.FlagUp == 0 || iface.Flags&net.FlagLoopback != 0 {
			continue
		}
		addrs, err := iface.Addrs()
		if err != nil {
			continue
		}
		for _, addr := range addrs {
			var candidate net.IP
			switch v := addr.(type) {
			case *net.IPNet:
				candidate = v.IP
			case *net.IPAddr:
				candidate = v.IP
			}
			if candidate == nil || candidate.IsLoopback() {
				continue
			}
			candidate = candidate.To4()
			if candidate == nil {
				continue
			}
			return candidate.String(), iface.HardwareAddr.String()
		}
	}

	return "", ""
}
