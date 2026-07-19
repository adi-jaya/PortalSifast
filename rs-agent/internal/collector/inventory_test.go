package collector

import "testing"

func TestClassifyUSB(t *testing.T) {
	t.Parallel()

	cases := []struct {
		lower, pnp, want string
	}{
		{"usb mass storage device", "", "storage"},
		{"hp laserjet", "Printer", "printer"},
		{"generic usb hub", "", "hub"},
		{"hid-compliant mouse", "HIDClass", "hid"},
		{"webcam", "Camera", "other"},
	}
	for _, tc := range cases {
		if got := classifyUSB(tc.lower, tc.pnp); got != tc.want {
			t.Fatalf("classifyUSB(%q,%q)=%q want %q", tc.lower, tc.pnp, got, tc.want)
		}
	}
}
