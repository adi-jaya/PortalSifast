//go:build !windows

package collector

func collectSensors() Sensors {
	return Sensors{
		Note:     "Sensor suhu hanya didukung di Windows.",
		Readings: []SensorReading{},
	}
}
