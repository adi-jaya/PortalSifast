//go:build !windows

package collector

func collectCriticalSoftware() []CriticalSoftware {
	return []CriticalSoftware{
		{ID: "synology_drive", Name: "Synology Drive", Status: "missing"},
		{ID: "anydesk", Name: "AnyDesk", Status: "missing"},
		{ID: "radmin_server", Name: "Radmin Server", Status: "missing"},
	}
}

func collectUSBInventory() USBInventory {
	return USBInventory{
		Estimated: true,
		Note:      "USB inventory hanya didukung penuh di Windows",
		Devices:   []USBDevice{},
	}
}
