package config

import (
	"os"
	"path/filepath"
	"testing"
)

func TestLoadValidConfig(t *testing.T) {
	dir := t.TempDir()
	path := filepath.Join(dir, "config.json")
	content := `{
  "portal_url": "https://example.test/",
  "api_token": "secret",
  "inbox_dir": "C:\\inbox",
  "processed_dir": "C:\\processed",
  "error_dir": "C:\\error",
  "poll_seconds": 0
}`
	if err := os.WriteFile(path, []byte(content), 0o644); err != nil {
		t.Fatal(err)
	}

	cfg, err := Load(path)
	if err != nil {
		t.Fatalf("Load: %v", err)
	}
	if cfg.PortalURL != "https://example.test" {
		t.Fatalf("PortalURL trimmed = %q", cfg.PortalURL)
	}
	if cfg.PollSeconds != 3 {
		t.Fatalf("PollSeconds default = %d", cfg.PollSeconds)
	}
	if cfg.Languages != "ind+eng" {
		t.Fatalf("Languages default = %q", cfg.Languages)
	}
}

func TestLoadRequiresPortalURL(t *testing.T) {
	dir := t.TempDir()
	path := filepath.Join(dir, "config.json")
	content := `{"api_token":"x","inbox_dir":"a","processed_dir":"b","error_dir":"c"}`
	if err := os.WriteFile(path, []byte(content), 0o644); err != nil {
		t.Fatal(err)
	}
	if _, err := Load(path); err == nil {
		t.Fatal("expected error")
	}
}
