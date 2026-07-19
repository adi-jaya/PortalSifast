package config

import (
	"encoding/json"
	"fmt"
	"os"
	"path/filepath"
	"sync"
)

type Config struct {
	Server        string `json:"server"`
	EnrollmentKey string `json:"enrollment_key"`
	APIKey        string `json:"api_key"`
	Interval      int    `json:"interval"`
	LogLevel      string `json:"log_level"`
	UUID          string `json:"uuid"`
	path          string
	mu            sync.Mutex
}

func Load(path string) (*Config, error) {
	data, err := os.ReadFile(path)
	if err != nil {
		return nil, fmt.Errorf("read config: %w", err)
	}

	var cfg Config
	if err := json.Unmarshal(data, &cfg); err != nil {
		return nil, fmt.Errorf("parse config: %w", err)
	}

	if cfg.Server == "" {
		return nil, fmt.Errorf("config.server is required")
	}
	if cfg.Interval <= 0 {
		cfg.Interval = 30
	}
	if cfg.LogLevel == "" {
		cfg.LogLevel = "info"
	}

	cfg.path = path
	return &cfg, nil
}

func (c *Config) SaveAPIKey(apiKey string) error {
	c.mu.Lock()
	defer c.mu.Unlock()

	c.APIKey = apiKey
	return c.persistLocked()
}

func (c *Config) SaveUUID(uuid string) error {
	c.mu.Lock()
	defer c.mu.Unlock()

	c.UUID = uuid
	return c.persistLocked()
}

func (c *Config) ClearEnrollmentKey() error {
	c.mu.Lock()
	defer c.mu.Unlock()

	c.EnrollmentKey = ""
	return c.persistLocked()
}

func (c *Config) persistLocked() error {
	data, err := json.MarshalIndent(struct {
		Server        string `json:"server"`
		EnrollmentKey string `json:"enrollment_key"`
		APIKey        string `json:"api_key"`
		Interval      int    `json:"interval"`
		LogLevel      string `json:"log_level"`
		UUID          string `json:"uuid"`
	}{
		Server:        c.Server,
		EnrollmentKey: c.EnrollmentKey,
		APIKey:        c.APIKey,
		Interval:      c.Interval,
		LogLevel:      c.LogLevel,
		UUID:          c.UUID,
	}, "", "  ")
	if err != nil {
		return err
	}

	dir := filepath.Dir(c.path)
	tmp, err := os.CreateTemp(dir, "config-*.tmp")
	if err != nil {
		return fmt.Errorf("create temp config: %w", err)
	}
	tmpName := tmp.Name()

	if _, err := tmp.Write(append(data, '\n')); err != nil {
		_ = tmp.Close()
		_ = os.Remove(tmpName)
		return fmt.Errorf("write temp config: %w", err)
	}
	if err := tmp.Close(); err != nil {
		_ = os.Remove(tmpName)
		return fmt.Errorf("close temp config: %w", err)
	}
	if err := os.Chmod(tmpName, 0o600); err != nil {
		// Best-effort on Windows where chmod may be a no-op.
		_ = err
	}
	if err := os.Rename(tmpName, c.path); err != nil {
		_ = os.Remove(tmpName)
		return fmt.Errorf("replace config: %w", err)
	}

	return nil
}
