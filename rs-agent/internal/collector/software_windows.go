//go:build windows

package collector

import (
	"fmt"
	"os"
	"path/filepath"
	"strings"

	"github.com/yusufpapurcu/wmi"
	"golang.org/x/sys/windows/registry"
)

type win32Service struct {
	Name        string
	State       string
	DisplayName string
}

type win32Process struct {
	Name string
}

func collectCriticalSoftware() []CriticalSoftware {
	defs := []struct {
		id, name string
		services []string
		process  []string
		display  []string
		paths    []string
	}{
		{
			id: "synology_drive", name: "Synology Drive",
			services: []string{"Synology Drive Service", "CloudStation", "SynologyCloudStation"},
			process:  []string{"SynologyDrive.exe", "CloudStation.exe"},
			display:  []string{"synology drive", "cloud station"},
			paths: []string{
				filepath.Join(os.Getenv("ProgramFiles"), "Synology", "SynologyDrive", "SynologyDrive.exe"),
				filepath.Join(os.Getenv("ProgramFiles"), "Synology", "CloudStation", "bin", "CloudStation.exe"),
			},
		},
		{
			id: "anydesk", name: "AnyDesk",
			services: []string{"AnyDesk", "AnyDeskService"},
			process:  []string{"AnyDesk.exe"},
			display:  []string{"anydesk"},
			paths: []string{
				filepath.Join(os.Getenv("ProgramFiles"), "AnyDesk", "AnyDesk.exe"),
				filepath.Join(os.Getenv("ProgramFiles(x86)"), "AnyDesk", "AnyDesk.exe"),
			},
		},
		{
			id: "radmin_server", name: "Radmin Server",
			services: []string{"RadminServer", "rserver3", "Radmin VPN Service"},
			process:  []string{"rserver3.exe", "Radmin.exe"},
			display:  []string{"radmin server", "radmin"},
			paths: []string{
				filepath.Join(os.Getenv("ProgramFiles"), "Radmin Viewer 3", "Radmin.exe"),
				filepath.Join(os.Getenv("ProgramFiles(x86)"), "Radmin Viewer 3", "Radmin.exe"),
				filepath.Join(os.Getenv("ProgramFiles"), "Radmin Server 3", "rserver3.exe"),
				filepath.Join(os.Getenv("ProgramFiles(x86)"), "Radmin Server 3", "rserver3.exe"),
			},
		},
	}

	services := map[string]win32Service{}
	var svcRows []win32Service
	if err := wmi.Query("SELECT Name, State, DisplayName FROM Win32_Service", &svcRows); err == nil {
		for _, s := range svcRows {
			services[strings.ToLower(s.Name)] = s
		}
	}

	procs := map[string]bool{}
	var procRows []win32Process
	if err := wmi.Query("SELECT Name FROM Win32_Process", &procRows); err == nil {
		for _, p := range procRows {
			procs[strings.ToLower(p.Name)] = true
		}
	}

	uninstall := uninstallDisplayNames()

	out := make([]CriticalSoftware, 0, len(defs))
	for _, d := range defs {
		item := CriticalSoftware{ID: d.id, Name: d.name, Status: "missing"}

		for _, svcName := range d.services {
			if s, ok := services[strings.ToLower(svcName)]; ok {
				if strings.EqualFold(s.State, "Running") {
					item.Status = "running"
					item.Detail = fmt.Sprintf("service %s", s.Name)
					break
				}
				item.Status = "installed"
				item.Detail = fmt.Sprintf("service %s (%s)", s.Name, s.State)
			}
		}
		if item.Status != "running" {
			for _, p := range d.process {
				if procs[strings.ToLower(p)] {
					item.Status = "running"
					item.Detail = fmt.Sprintf("process %s", p)
					break
				}
			}
		}
		if item.Status == "missing" {
			for _, path := range d.paths {
				if path != "" {
					if _, err := os.Stat(path); err == nil {
						item.Status = "installed"
						item.Detail = path
						break
					}
				}
			}
		}
		if item.Status == "missing" {
			for _, needle := range d.display {
				for _, name := range uninstall {
					if strings.Contains(strings.ToLower(name), needle) {
						item.Status = "installed"
						item.Detail = name
						break
					}
				}
				if item.Status != "missing" {
					break
				}
			}
		}
		out = append(out, item)
	}
	return out
}

func uninstallDisplayNames() []string {
	roots := []registry.Key{registry.LOCAL_MACHINE}
	paths := []string{
		`SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall`,
		`SOFTWARE\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall`,
	}
	var names []string
	for _, root := range roots {
		for _, path := range paths {
			k, err := registry.OpenKey(root, path, registry.ENUMERATE_SUB_KEYS|registry.QUERY_VALUE)
			if err != nil {
				continue
			}
			subkeys, err := k.ReadSubKeyNames(-1)
			if err != nil {
				_ = k.Close()
				continue
			}
			for _, sk := range subkeys {
				sub, err := registry.OpenKey(k, sk, registry.QUERY_VALUE)
				if err != nil {
					continue
				}
				display, _, err := sub.GetStringValue("DisplayName")
				_ = sub.Close()
				if err == nil && strings.TrimSpace(display) != "" {
					names = append(names, display)
				}
			}
			_ = k.Close()
		}
	}
	return names
}
