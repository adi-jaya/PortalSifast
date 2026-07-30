package collector

import (
	"math"
	"strings"
)

// CriticalSoftware describes presence of remote-access / sync tools.
type CriticalSoftware struct {
	ID     string `json:"id"`
	Name   string `json:"name"`
	Status string `json:"status"` // running | installed | missing
	Detail string `json:"detail,omitempty"`
}

// USBDevice is one connected USB endpoint (best-effort classification).
type USBDevice struct {
	Name     string `json:"name"`
	Kind     string `json:"kind"` // storage | printer | hub | hid | other
	DeviceID string `json:"device_id,omitempty"`
}

// USBInventory estimates port usage. PortsEmpty is approximate on Windows.
type USBInventory struct {
	PortsTotal            int         `json:"ports_total"`
	PortsUsed             int         `json:"ports_used"`
	PortsEmpty            int         `json:"ports_empty"`
	RemovableStorageCount int         `json:"removable_storage_count"`
	HasRemovableStorage   bool        `json:"has_removable_storage"`
	PrinterCount          int         `json:"printer_count"`
	Estimated             bool        `json:"estimated"`
	Note                  string      `json:"note,omitempty"`
	Devices               []USBDevice `json:"devices"`
}

// SensorReading is one ACPI thermal zone reading (best-effort, Windows-only).
type SensorReading struct {
	Name         string  `json:"name"`
	TemperatureC float64 `json:"temperature_c"`
}

// Sensors holds best-effort ACPI thermal data. Many OEM boards do not expose
// MSAcpi_ThermalZoneTemperature via WMI, so Supported=false with empty
// Readings is common and expected — it is not an agent error.
type Sensors struct {
	Supported bool            `json:"supported"`
	Note      string          `json:"note,omitempty"`
	Readings  []SensorReading `json:"readings"`
}

// kelvinTenthsToCelsius converts MSAcpi_ThermalZoneTemperature's raw value
// (tenths of a degree Kelvin) to Celsius, rounded to one decimal place.
func kelvinTenthsToCelsius(raw uint32) float64 {
	return math.Round((float64(raw)/10-273.15)*10) / 10
}

func classifyUSB(lower, pnpClass string) string {
	if strings.Contains(lower, "hub") {
		return "hub"
	}
	if strings.Contains(lower, "print") || strings.EqualFold(pnpClass, "Printer") {
		return "printer"
	}
	if strings.Contains(lower, "disk") ||
		strings.Contains(lower, "storage") ||
		strings.Contains(lower, "mass storage") ||
		strings.Contains(lower, "flash") ||
		strings.Contains(lower, "thumb") ||
		strings.Contains(lower, "removable") {
		return "storage"
	}
	if strings.Contains(lower, "keyboard") ||
		strings.Contains(lower, "mouse") ||
		strings.Contains(lower, "hid") ||
		strings.EqualFold(pnpClass, "HIDClass") {
		return "hid"
	}
	return "other"
}
