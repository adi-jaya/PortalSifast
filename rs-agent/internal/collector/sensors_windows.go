//go:build windows

package collector

import "github.com/yusufpapurcu/wmi"

type msAcpiThermalZoneTemperature struct {
	InstanceName       string
	CurrentTemperature uint32
}

// collectSensors reads ACPI thermal zone temperature via WMI (root\WMI
// namespace). This is best-effort: many OEM motherboards/laptops do not
// populate MSAcpi_ThermalZoneTemperature, so an empty/unsupported result is
// common and does not indicate an agent problem.
func collectSensors() Sensors {
	sensors := Sensors{Readings: []SensorReading{}}

	var zones []msAcpiThermalZoneTemperature
	err := wmi.QueryNamespace(
		"SELECT InstanceName, CurrentTemperature FROM MSAcpi_ThermalZoneTemperature",
		&zones,
		`root\WMI`,
	)
	if err != nil || len(zones) == 0 {
		sensors.Note = "Sensor suhu ACPI tidak tersedia di perangkat ini (umum pada board tanpa dukungan WMI thermal zone)."
		return sensors
	}

	for _, z := range zones {
		if z.CurrentTemperature == 0 {
			continue
		}
		celsius := kelvinTenthsToCelsius(z.CurrentTemperature)
		if celsius <= -50 || celsius >= 150 {
			// Guard against bogus firmware values seen on some boards.
			continue
		}
		sensors.Readings = append(sensors.Readings, SensorReading{
			Name:         firstNonEmpty(cleanWMI(z.InstanceName), "ThermalZone"),
			TemperatureC: celsius,
		})
	}

	if len(sensors.Readings) == 0 {
		sensors.Note = "Zona termal ACPI terdeteksi tapi nilainya tidak valid/nol."
		return sensors
	}

	sensors.Supported = true
	return sensors
}
