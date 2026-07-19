package collector

import (
	"net"
	"testing"
)

func TestIsCGNAT(t *testing.T) {
	t.Parallel()

	if !isCGNAT(net.ParseIP("100.125.5.64").To4()) {
		t.Fatal("expected Tailscale CGNAT")
	}
	if isCGNAT(net.ParseIP("192.168.1.10").To4()) {
		t.Fatal("LAN should not be CGNAT")
	}
}

func TestIsVirtualIface(t *testing.T) {
	t.Parallel()

	if !isVirtualIface("tailscale0") {
		t.Fatal("expected tailscale virtual")
	}
	if isVirtualIface("ethernet") {
		t.Fatal("ethernet should be physical")
	}
}
