package config

import (
	"bytes"
	"encoding/json"
	"fmt"
	"os"
	"strings"
)

type Config struct {
	PortalURL     string `json:"portal_url"`
	APIToken      string `json:"api_token"`
	InboxDir      string `json:"inbox_dir"`
	ProcessedDir  string `json:"processed_dir"`
	ErrorDir      string `json:"error_dir"`
	TesseractPath string `json:"tesseract_path"`
	Languages     string `json:"languages"`
	PollSeconds   int    `json:"poll_seconds"`
	AgentLabel    string `json:"agent_label"`
}

func Load(path string) (*Config, error) {
	data, err := os.ReadFile(path)
	if err != nil {
		return nil, fmt.Errorf("read config: %w", err)
	}
	data = bytes.TrimPrefix(data, []byte{0xEF, 0xBB, 0xBF})

	var cfg Config
	if err := json.Unmarshal(data, &cfg); err != nil {
		return nil, fmt.Errorf("parse config: %w", err)
	}
	if err := cfg.Validate(); err != nil {
		return nil, err
	}
	return &cfg, nil
}

func (c *Config) Validate() error {
	if strings.TrimSpace(c.PortalURL) == "" {
		return fmt.Errorf("config.portal_url is required")
	}
	if strings.TrimSpace(c.APIToken) == "" {
		return fmt.Errorf("config.api_token is required")
	}
	if strings.TrimSpace(c.InboxDir) == "" {
		return fmt.Errorf("config.inbox_dir is required")
	}
	if strings.TrimSpace(c.ProcessedDir) == "" {
		return fmt.Errorf("config.processed_dir is required")
	}
	if strings.TrimSpace(c.ErrorDir) == "" {
		return fmt.Errorf("config.error_dir is required")
	}
	if strings.TrimSpace(c.TesseractPath) == "" {
		c.TesseractPath = `C:\Program Files\Tesseract-OCR\tesseract.exe`
	}
	if strings.TrimSpace(c.Languages) == "" {
		c.Languages = "ind+eng"
	}
	if c.PollSeconds <= 0 {
		c.PollSeconds = 3
	}
	if strings.TrimSpace(c.AgentLabel) == "" {
		c.AgentLabel = "berkas-agent"
	}
	c.PortalURL = strings.TrimRight(strings.TrimSpace(c.PortalURL), "/")
	return nil
}
