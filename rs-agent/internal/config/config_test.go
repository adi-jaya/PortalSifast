package config

import (
	"encoding/json"
	"os"
	"path/filepath"
	"testing"
)

func TestLoadDefaults(t *testing.T) {
	t.Parallel()

	dir := t.TempDir()
	path := filepath.Join(dir, "config.json")
	if err := os.WriteFile(path, []byte(`{"server":"https://example.com"}`), 0o600); err != nil {
		t.Fatal(err)
	}

	cfg, err := Load(path)
	if err != nil {
		t.Fatal(err)
	}
	if cfg.Interval != 30 {
		t.Fatalf("interval=%d want 30", cfg.Interval)
	}
	if cfg.LogLevel != "info" {
		t.Fatalf("log_level=%q want info", cfg.LogLevel)
	}
}

func TestLoadStripsUTF8BOM(t *testing.T) {
	t.Parallel()

	dir := t.TempDir()
	path := filepath.Join(dir, "config.json")
	payload := append([]byte{0xEF, 0xBB, 0xBF}, []byte(`{"server":"https://example.com"}`)...)
	if err := os.WriteFile(path, payload, 0o600); err != nil {
		t.Fatal(err)
	}

	cfg, err := Load(path)
	if err != nil {
		t.Fatal(err)
	}
	if cfg.Server != "https://example.com" {
		t.Fatalf("server=%q", cfg.Server)
	}
}

func TestLoadRequiresServer(t *testing.T) {
	t.Parallel()

	dir := t.TempDir()
	path := filepath.Join(dir, "config.json")
	if err := os.WriteFile(path, []byte(`{"server":""}`), 0o600); err != nil {
		t.Fatal(err)
	}
	if _, err := Load(path); err == nil {
		t.Fatal("expected error for empty server")
	}
}

func TestSaveUUIDAndAPIKeyRoundTrip(t *testing.T) {
	t.Parallel()

	dir := t.TempDir()
	path := filepath.Join(dir, "config.json")
	if err := os.WriteFile(path, []byte(`{
  "server": "https://example.com",
  "enrollment_key": "secret",
  "api_key": "",
  "interval": 30,
  "log_level": "info",
  "uuid": ""
}`), 0o600); err != nil {
		t.Fatal(err)
	}

	cfg, err := Load(path)
	if err != nil {
		t.Fatal(err)
	}
	if err := cfg.SaveUUID("11111111-1111-4111-8111-111111111111"); err != nil {
		t.Fatal(err)
	}
	if err := cfg.SaveAPIKey("rsag_test"); err != nil {
		t.Fatal(err)
	}

	reloaded, err := Load(path)
	if err != nil {
		t.Fatal(err)
	}
	if reloaded.UUID != "11111111-1111-4111-8111-111111111111" {
		t.Fatalf("uuid=%q", reloaded.UUID)
	}
	if reloaded.APIKey != "rsag_test" {
		t.Fatalf("api_key=%q", reloaded.APIKey)
	}
}

func TestClearEnrollmentKey(t *testing.T) {
	t.Parallel()

	dir := t.TempDir()
	path := filepath.Join(dir, "config.json")
	if err := os.WriteFile(path, []byte(`{
  "server": "https://example.com",
  "enrollment_key": "secret",
  "api_key": "rsag_x",
  "interval": 30,
  "log_level": "info",
  "uuid": "11111111-1111-4111-8111-111111111111"
}`), 0o600); err != nil {
		t.Fatal(err)
	}

	cfg, err := Load(path)
	if err != nil {
		t.Fatal(err)
	}
	if err := cfg.ClearEnrollmentKey(); err != nil {
		t.Fatal(err)
	}
	if cfg.EnrollmentKey != "" {
		t.Fatalf("enrollment key still in memory: %q", cfg.EnrollmentKey)
	}

	raw, err := os.ReadFile(path)
	if err != nil {
		t.Fatal(err)
	}
	var stored map[string]any
	if err := json.Unmarshal(raw, &stored); err != nil {
		t.Fatal(err)
	}
	if v, _ := stored["enrollment_key"].(string); v != "" {
		t.Fatalf("enrollment_key still on disk: %q", v)
	}
	if v, _ := stored["api_key"].(string); v != "rsag_x" {
		t.Fatalf("api_key lost: %v", stored["api_key"])
	}
}
