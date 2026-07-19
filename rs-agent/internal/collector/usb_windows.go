//go:build windows

package collector

import (
	"strings"

	"github.com/yusufpapurcu/wmi"
)

type win32USBHub struct {
	Name          string
	DeviceID      string
	NumberOfPorts uint16
	Status        string
}

type win32PnPEntity struct {
	Name         string
	PNPClass     string
	DeviceID     string
	Description  string
	Manufacturer string
	Status       string
}

type win32DiskDrive struct {
	Caption       string
	InterfaceType string
	PNPDeviceID   string
	MediaType     string
}

type win32Printer struct {
	Name     string
	PortName string
}

func collectUSBInventory() USBInventory {
	inv := USBInventory{
		Estimated: true,
		Note:      "Jumlah port kosong adalah estimasi Windows (hub NumberOfPorts − perangkat terhubung).",
		Devices:   []USBDevice{},
	}

	portsTotal := 0
	var hubs []win32USBHub
	if err := wmi.Query("SELECT Name, DeviceID, NumberOfPorts, Status FROM Win32_USBHub", &hubs); err == nil {
		for _, h := range hubs {
			if h.NumberOfPorts > 0 {
				portsTotal += int(h.NumberOfPorts)
			}
			inv.Devices = append(inv.Devices, USBDevice{
				Name:     firstNonEmpty(h.Name, "USB Hub"),
				Kind:     "hub",
				DeviceID: h.DeviceID,
			})
		}
	}

	seen := map[string]bool{}
	var entities []win32PnPEntity
	_ = wmi.Query("SELECT Name, PNPClass, DeviceID, Description, Manufacturer, Status FROM Win32_PnPEntity WHERE PNPClass='USB' OR DeviceID LIKE 'USB%'", &entities)
	for _, e := range entities {
		id := strings.ToUpper(e.DeviceID)
		if id == "" || seen[id] {
			continue
		}
		// Skip root hubs already counted as hubs when possible.
		name := firstNonEmpty(e.Name, e.Description)
		if name == "" {
			continue
		}
		lower := strings.ToLower(name + " " + e.PNPClass + " " + e.Description)
		if strings.Contains(lower, "root hub") || strings.Contains(lower, "generic hub") {
			continue
		}
		kind := classifyUSB(lower, e.PNPClass)
		if kind == "hub" {
			continue
		}
		seen[id] = true
		inv.Devices = append(inv.Devices, USBDevice{
			Name:     name,
			Kind:     kind,
			DeviceID: e.DeviceID,
		})
	}

	var disks []win32DiskDrive
	if err := wmi.Query("SELECT Caption, InterfaceType, PNPDeviceID, MediaType FROM Win32_DiskDrive", &disks); err == nil {
		for _, d := range disks {
			if !strings.EqualFold(d.InterfaceType, "USB") {
				continue
			}
			id := strings.ToUpper(d.PNPDeviceID)
			if id != "" && seen[id] {
				inv.RemovableStorageCount++
				continue
			}
			seen[id] = true
			inv.RemovableStorageCount++
			inv.Devices = append(inv.Devices, USBDevice{
				Name:     firstNonEmpty(d.Caption, "USB Disk"),
				Kind:     "storage",
				DeviceID: d.PNPDeviceID,
			})
		}
	}

	var printers []win32Printer
	if err := wmi.Query("SELECT Name, PortName FROM Win32_Printer", &printers); err == nil {
		for _, p := range printers {
			port := strings.ToUpper(p.PortName)
			if !strings.Contains(port, "USB") {
				continue
			}
			inv.PrinterCount++
			key := "PRINTER:" + strings.ToUpper(p.Name)
			if seen[key] {
				continue
			}
			seen[key] = true
			inv.Devices = append(inv.Devices, USBDevice{
				Name:     p.Name,
				Kind:     "printer",
				DeviceID: p.PortName,
			})
		}
	}

	used := 0
	for _, d := range inv.Devices {
		if d.Kind == "hub" {
			continue
		}
		used++
		if d.Kind == "storage" {
			// already counted via disks; avoid double-count on RemovableStorageCount from classify
		}
	}
	// Recompute removable from device kinds to stay consistent.
	storageCount := 0
	printerCount := 0
	for _, d := range inv.Devices {
		switch d.Kind {
		case "storage":
			storageCount++
		case "printer":
			printerCount++
		}
	}
	inv.RemovableStorageCount = storageCount
	inv.HasRemovableStorage = storageCount > 0
	inv.PrinterCount = printerCount

	inv.PortsUsed = used
	if portsTotal > 0 {
		inv.PortsTotal = portsTotal
		empty := portsTotal - used
		if empty < 0 {
			empty = 0
		}
		inv.PortsEmpty = empty
	} else {
		inv.PortsTotal = used
		inv.PortsEmpty = 0
		inv.Note = "NumberOfPorts hub tidak tersedia; ports_total diset sama dengan perangkat terdeteksi (estimasi kasar)."
	}

	return inv
}
