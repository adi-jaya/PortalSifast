package config

import (
	"encoding/json"
	"fmt"
	"os"
	"sync"
)

type Config struct {
	Server         string `json:"server"`
	EnrollmentKey  string `json:"enrollment_key"`
	APIKey         string `json:"api_key"`
	Interval       int    `json:"interval"`
	LogLevel       string `json:"log_level"`
	UUID           string `json:"uuid"`
	path           string
	mu             sync.Mutex
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

	return os.WriteFile(c.path, append(data, '\n'), 0o600)
}
