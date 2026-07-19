package serviceapp

import (
	"path/filepath"
	"testing"
)

func TestResolveLogsDir(t *testing.T) {
	t.Parallel()

	dev := ResolveLogsDir(filepath.FromSlash("C:/agent/configs/config.json"))
	if filepath.Base(dev) != "logs" {
		t.Fatalf("dev logs=%q", dev)
	}
	if filepath.Base(filepath.Dir(dev)) != "agent" {
		t.Fatalf("dev parent=%q", filepath.Dir(dev))
	}

	prod := ResolveLogsDir(filepath.FromSlash("C:/ProgramData/PortalSifast Agent/config.json"))
	want := filepath.FromSlash("C:/ProgramData/PortalSifast Agent/logs")
	if prod != want {
		t.Fatalf("prod logs=%q want %q", prod, want)
	}
}

func TestWorkingDirectoryForConfig(t *testing.T) {
	t.Parallel()

	dev := WorkingDirectoryForConfig(filepath.FromSlash("C:/agent/configs/config.json"))
	if filepath.Base(dev) != "agent" {
		t.Fatalf("working dir=%q", dev)
	}

	prod := WorkingDirectoryForConfig(filepath.FromSlash("C:/ProgramData/PortalSifast Agent/config.json"))
	want := filepath.FromSlash("C:/ProgramData/PortalSifast Agent")
	if prod != want {
		t.Fatalf("prod workdir=%q want %q", prod, want)
	}
}

func TestServiceConstants(t *testing.T) {
	t.Parallel()

	if ServiceName != "PortalSifastAgent" {
		t.Fatalf("ServiceName=%q", ServiceName)
	}
}
