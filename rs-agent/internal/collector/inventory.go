package collector

import "strings"

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
