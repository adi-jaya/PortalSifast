package serviceapp

import (
	"regexp"
	"testing"
)

func TestNewDeviceUUIDFormatAndUnique(t *testing.T) {
	t.Parallel()

	re := regexp.MustCompile(`(?i)^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$`)
	a := NewDeviceUUID()
	b := NewDeviceUUID()
	if !re.MatchString(a) {
		t.Fatalf("invalid uuid: %q", a)
	}
	if !re.MatchString(b) {
		t.Fatalf("invalid uuid: %q", b)
	}
	if a == b {
		t.Fatal("expected unique uuids")
	}
}
