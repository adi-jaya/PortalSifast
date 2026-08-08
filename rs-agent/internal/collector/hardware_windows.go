//go:build windows

package collector

import (
	"strings"

	"github.com/yusufpapurcu/wmi"
)

type win32ComputerSystem struct {
	Manufacturer string
	Model        string
	Domain       string
	UserName     string
	Name         string
}

type win32BIOS struct {
	Manufacturer      string
	SMBIOSBIOSVersion string
	SerialNumber      string
}

type win32BaseBoard struct {
	Manufacturer string
	Product      string
	SerialNumber string
}

type win32ComputerSystemProduct struct {
	Vendor            string
	Name              string
	IdentifyingNumber string
}

func platformHardware() platformHW {
	out := platformHW{}

	var systems []win32ComputerSystem
	if err := wmi.Query("SELECT Manufacturer, Model, Domain, UserName, Name FROM Win32_ComputerSystem", &systems); err == nil && len(systems) > 0 {
		s := systems[0]
		out.Manufacturer = cleanWMI(s.Manufacturer)
		out.Model = cleanWMI(s.Model)
		out.Domain = cleanWMI(s.Domain)
		out.Username = cleanWMI(s.UserName)
		out.ComputerName = cleanWMI(s.Name)
	}

	var products []win32ComputerSystemProduct
	if err := wmi.Query("SELECT Vendor, Name, IdentifyingNumber FROM Win32_ComputerSystemProduct", &products); err == nil && len(products) > 0 {
		p := products[0]
		if out.Manufacturer == "" {
			out.Manufacturer = cleanWMI(p.Vendor)
		}
		if out.Model == "" {
			out.Model = cleanWMI(p.Name)
		}
		out.SerialNumber = cleanWMI(p.IdentifyingNumber)
	}

	var boards []win32BaseBoard
	if err := wmi.Query("SELECT Manufacturer, Product, SerialNumber FROM Win32_BaseBoard", &boards); err == nil && len(boards) > 0 {
		b := boards[0]
		mfg := cleanWMI(b.Manufacturer)
		prod := cleanWMI(b.Product)
		out.Motherboard = strings.TrimSpace(strings.Join(filterEmpty(mfg, prod), " "))
		if out.SerialNumber == "" {
			out.SerialNumber = cleanWMI(b.SerialNumber)
		}
	}

	var biosRows []win32BIOS
	if err := wmi.Query("SELECT Manufacturer, SMBIOSBIOSVersion, SerialNumber FROM Win32_BIOS", &biosRows); err == nil && len(biosRows) > 0 {
		b := biosRows[0]
		mfg := cleanWMI(b.Manufacturer)
		ver := cleanWMI(b.SMBIOSBIOSVersion)
		out.BIOS = strings.TrimSpace(strings.Join(filterEmpty(mfg, ver), " "))
		if out.SerialNumber == "" {
			out.SerialNumber = cleanWMI(b.SerialNumber)
		}
	}

	return out
}

func cleanWMI(v string) string {
	v = strings.TrimSpace(v)
	switch strings.ToLower(v) {
	case "", "none", "to be filled by o.e.m.", "default string", "system manufacturer", "system product name", "o.e.m.":
		return ""
	default:
		return v
	}
}

func filterEmpty(parts ...string) []string {
	out := make([]string, 0, len(parts))
	for _, p := range parts {
		if p != "" {
			out = append(out, p)
		}
	}
	return out
}
