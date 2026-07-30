package collector

import "testing"

func TestKelvinTenthsToCelsius(t *testing.T) {
	t.Parallel()

	// 3131 tenths-Kelvin ≈ 313.1K, a typical idle CPU thermal zone reading.
	got := kelvinTenthsToCelsius(3131)
	if diff := got - 39.9; diff < -0.15 || diff > 0.15 {
		t.Fatalf("kelvinTenthsToCelsius(3131) = %v, want ~39.9", got)
	}

	// 0 raw (absolute zero) should not be treated as a plausible reading by
	// callers, but the conversion itself must not panic and stays in range.
	if got := kelvinTenthsToCelsius(0); got < -273.2 || got > -273.1 {
		t.Fatalf("kelvinTenthsToCelsius(0) = %v, want ~-273.15", got)
	}
}
