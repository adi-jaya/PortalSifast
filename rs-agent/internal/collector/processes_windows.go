//go:build windows

package collector

import (
	"path/filepath"
	"sort"
	"strconv"
	"strings"
	"sync"

	"github.com/shirou/gopsutil/v4/process"
)

const (
	processListCap       = 500
	suspiciousCPUPercent = 30.0
	suspiciousCPUStreak  = 2
)

var (
	cpuWatchMu sync.Mutex
	cpuWatch   = map[string]int{}
)

var minerExeFragments = []string{
	"xmrig", "minergate", "nicehash", "ethminer", "cpuminer", "miner.exe", "nscpu", "coinhive",
}

var suspiciousPathFragments = []string{
	`\appdata\local\temp\`,
	`\windows\temp\`,
	`\programdata\`,
	`\downloads\`,
}

var processAllowlist = map[string]struct{}{
	"chrome.exe":                  {},
	"msedge.exe":                  {},
	"firefox.exe":                 {},
	"cursor.exe":                  {},
	"code.exe":                    {},
	"explorer.exe":                {},
	"dwm.exe":                     {},
	"searchhost.exe":              {},
	"shellexperiencehost.exe":     {},
	"startmenuexperiencehost.exe": {},
	"applicationframehost.exe":    {},
	"runtimebroker.exe":           {},
	"svchost.exe":                 {},
	"taskmgr.exe":                 {},
	"teams.exe":                   {},
	"outlook.exe":                 {},
	"winword.exe":                 {},
	"excel.exe":                   {},
	"powerpnt.exe":                {},
	"onedrive.exe":                {},
	"githubdesktop.exe":           {},
	"anydesk.exe":                 {},
	"rs-agent.exe":                {},
}

func collectSuspiciousProcesses() []SuspiciousProcess {
	entries, err := enumerateProcesses()
	if err != nil {
		return nil
	}

	out := make([]SuspiciousProcess, 0)
	seen := make(map[string]struct{})

	for _, entry := range entries {
		reasons := suspiciousReasons(entry)
		if len(reasons) == 0 {
			continue
		}

		key := processKey(entry.PID, entry.Exe)
		if _, ok := seen[key]; ok {
			continue
		}
		seen[key] = struct{}{}

		out = append(out, SuspiciousProcess{
			PID:        entry.PID,
			Exe:        entry.Exe,
			Path:       entry.Path,
			CPUPercent: entry.CPUPercent,
			Reasons:    reasons,
		})
	}

	sort.Slice(out, func(i, j int) bool {
		return out[i].CPUPercent > out[j].CPUPercent
	})

	if len(out) > 50 {
		out = out[:50]
	}

	return out
}

func ListAllProcesses() ([]ProcessEntry, error) {
	return listAllProcesses()
}

func listAllProcesses() ([]ProcessEntry, error) {
	entries, err := enumerateProcesses()
	if err != nil {
		return nil, err
	}

	sort.Slice(entries, func(i, j int) bool {
		if entries[i].CPUPercent == entries[j].CPUPercent {
			return strings.ToLower(entries[i].Exe) < strings.ToLower(entries[j].Exe)
		}
		return entries[i].CPUPercent > entries[j].CPUPercent
	})

	if len(entries) > processListCap {
		entries = entries[:processListCap]
	}

	return entries, nil
}

func enumerateProcesses() ([]ProcessEntry, error) {
	procs, err := process.Processes()
	if err != nil {
		return nil, err
	}

	out := make([]ProcessEntry, 0, len(procs))
	for _, p := range procs {
		name, err := p.Name()
		if err != nil || name == "" {
			continue
		}

		exePath, _ := p.Exe()
		cpu, _ := p.CPUPercent()
		user, _ := p.Username()

		entry := ProcessEntry{
			PID:        uint32(p.Pid),
			Exe:        strings.ToLower(filepath.Base(exePathOrName(exePath, name))),
			Path:       exePath,
			User:       usernameBase(user),
			CPUPercent: roundCPU(cpu),
		}
		out = append(out, entry)
	}

	return out, nil
}

func suspiciousReasons(entry ProcessEntry) []string {
	if _, allowed := processAllowlist[entry.Exe]; allowed {
		cpuWatchMu.Lock()
		delete(cpuWatch, processKey(entry.PID, entry.Exe))
		cpuWatchMu.Unlock()
		return nil
	}

	reasons := make([]string, 0, 3)

	if matchesMinerName(entry.Exe) {
		reasons = append(reasons, "known_miner_name")
	}

	if entry.Path != "" && matchesSuspiciousPath(entry.Path) {
		reasons = append(reasons, "suspicious_path")
	}

	key := processKey(entry.PID, entry.Exe)
	if entry.CPUPercent >= suspiciousCPUPercent {
		cpuWatchMu.Lock()
		cpuWatch[key]++
		streak := cpuWatch[key]
		cpuWatchMu.Unlock()
		if streak >= suspiciousCPUStreak {
			reasons = append(reasons, "high_cpu")
		}
	} else {
		cpuWatchMu.Lock()
		delete(cpuWatch, key)
		cpuWatchMu.Unlock()
	}

	return reasons
}

func matchesMinerName(exe string) bool {
	for _, fragment := range minerExeFragments {
		if strings.Contains(exe, fragment) {
			return true
		}
	}
	return false
}

func matchesSuspiciousPath(path string) bool {
	normalized := strings.ToLower(strings.ReplaceAll(path, "/", `\`))
	for _, fragment := range suspiciousPathFragments {
		if strings.Contains(normalized, fragment) {
			return true
		}
	}
	return false
}

func processKey(pid uint32, exe string) string {
	return strings.ToLower(exe) + ":" + strconv.FormatUint(uint64(pid), 10)
}

func exePathOrName(path, name string) string {
	if path != "" {
		return path
	}
	return name
}

func usernameBase(user string) string {
	if user == "" {
		return ""
	}
	if idx := strings.LastIndex(user, `\`); idx >= 0 && idx+1 < len(user) {
		return user[idx+1:]
	}
	return user
}

func roundCPU(v float64) float64 {
	if v < 0 {
		return 0
	}
	return float64(int(v*10+0.5)) / 10
}
