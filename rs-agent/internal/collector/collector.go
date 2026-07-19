package collector

import (
	"fmt"
	"net"
	"os"
	"runtime"
	"strings"
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
		usage, err = disk.Usage("C:\\")
		if err != nil {
			return Snapshot{}, fmt.Errorf("disk: %w", err)
		}
	}

	ip, mac := primaryNet()
	tz, _ := time.Now().Zone()
	boot := time.Unix(int64(info.BootTime), 0)
	hwExtra := platformHardware()

	cpuPct := 0.0
	if len(cpuPercents) > 0 {
		cpuPct = cpuPercents[0]
	}

	username := currentUsername()
	if username == "" {
		username = hwExtra.Username
	}

	serial := strings.TrimSpace(hwExtra.SerialNumber)
	if serial == "" {
		serial = info.HostID
	}

	return Snapshot{
		UUID:         deviceUUID,
		Hostname:     hostname,
		ComputerName: firstNonEmpty(hwExtra.ComputerName, hostname),
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
			Manufacturer: hwExtra.Manufacturer,
			Model:        hwExtra.Model,
			SerialNumber: serial,
			Motherboard:  hwExtra.Motherboard,
			BIOS:         hwExtra.BIOS,
			BootTime:     boot,
			Timezone:     tz,
			Domain:       hwExtra.Domain,
			Username:     username,
		},
		Metrics: Metrics{
			CPUPercent:    cpuPct,
			RAMPercent:    vm.UsedPercent,
			DiskPercent:   usage.UsedPercent,
			UptimeSeconds: info.Uptime,
		},
	}, nil
}

type platformHW struct {
	Manufacturer string
	Model        string
	SerialNumber string
	Motherboard  string
	BIOS         string
	Domain       string
	Username     string
	ComputerName string
}

func currentUsername() string {
	if u := strings.TrimSpace(os.Getenv("USERNAME")); u != "" {
		return u
	}
	return strings.TrimSpace(os.Getenv("USER"))
}

func firstNonEmpty(values ...string) string {
	for _, v := range values {
		if strings.TrimSpace(v) != "" {
			return strings.TrimSpace(v)
		}
	}
	return ""
}

func primaryNet() (ip, mac string) {
	type candidate struct {
		ip, mac string
		score   int
	}
	var best *candidate

	ifaces, err := net.Interfaces()
	if err != nil {
		return "", ""
	}

	for _, iface := range ifaces {
		if iface.Flags&net.FlagUp == 0 || iface.Flags&net.FlagLoopback != 0 {
			continue
		}
		name := strings.ToLower(iface.Name)
		if isVirtualIface(name) {
			continue
		}
		macStr := iface.HardwareAddr.String()
		if macStr == "" || macStr == "00:00:00:00:00:00" {
			continue
		}

		addrs, err := iface.Addrs()
		if err != nil {
			continue
		}
		for _, addr := range addrs {
			var candidateIP net.IP
			switch v := addr.(type) {
			case *net.IPNet:
				candidateIP = v.IP
			case *net.IPAddr:
				candidateIP = v.IP
			}
			if candidateIP == nil || candidateIP.IsLoopback() {
				continue
			}
			v4 := candidateIP.To4()
			if v4 == nil {
				continue
			}
			score := 10
			if isCGNAT(v4) {
				score = 1 // Tailscale / carrier-grade NAT — keep as fallback only
			}
			c := candidate{ip: v4.String(), mac: macStr, score: score}
			if best == nil || c.score > best.score {
				best = &c
			}
		}
	}

	if best == nil {
		return "", ""
	}
	return best.ip, best.mac
}

func isVirtualIface(name string) bool {
	needles := []string{
		"tailscale", "nordlynx", "wireguard", "wsl", "vethernet",
		"hyper-v", "virtualbox", "vmware", "docker", "br-", "veth",
	}
	for _, n := range needles {
		if strings.Contains(name, n) {
			return true
		}
	}
	return false
}

func isCGNAT(ip net.IP) bool {
	// 100.64.0.0/10 — often Tailscale / CGNAT
	if ip[0] != 100 {
		return false
	}
	return ip[1] >= 64 && ip[1] <= 127
}
