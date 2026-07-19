package serviceapp

import (
	"fmt"
	"os"
	"path/filepath"
	"sync"

	"github.com/kardianos/service"
	"github.com/portalsifast/rs-agent/internal/api"
	"github.com/portalsifast/rs-agent/internal/config"
	"github.com/portalsifast/rs-agent/internal/heartbeat"
	"github.com/portalsifast/rs-agent/internal/logger"
	"github.com/rs/zerolog"
)

const (
	ServiceName        = "PortalSifastRSAgent"
	ServiceDisplayName = "PortalSifast RS Agent"
	ServiceDescription = "Collects device metrics and sends heartbeats to PortalSifast"
)

type Program struct {
	ConfigPath   string
	AgentVersion string

	stop    chan struct{}
	stopped sync.WaitGroup
	log     zerolog.Logger
	cleanup func()
}

func (p *Program) Start(_ service.Service) error {
	p.stop = make(chan struct{})
	p.stopped.Add(1)
	go func() {
		defer p.stopped.Done()
		p.run()
	}()
	return nil
}

func (p *Program) Stop(_ service.Service) error {
	if p.stop != nil {
		select {
		case <-p.stop:
		default:
			close(p.stop)
		}
	}
	p.stopped.Wait()
	return nil
}

func (p *Program) run() {
	cfg, err := config.Load(p.ConfigPath)
	if err != nil {
		fmt.Fprintf(os.Stderr, "load config: %v\n", err)
		return
	}

	if cfg.UUID == "" {
		cfg.UUID = NewDeviceUUID()
		if err := cfg.SaveUUID(cfg.UUID); err != nil {
			fmt.Fprintf(os.Stderr, "save uuid: %v\n", err)
			return
		}
	}

	logsDir := resolveLogsDir(p.ConfigPath)
	log, cleanup, err := logger.Setup(logsDir, cfg.LogLevel, cfg.UUID)
	if err != nil {
		fmt.Fprintf(os.Stderr, "logger: %v\n", err)
		return
	}
	defer cleanup()

	p.log = log

	log.Info().
		Str("event", "startup").
		Str("version", p.AgentVersion).
		Str("server", cfg.Server).
		Int("interval", cfg.Interval).
		Msg("rs-agent starting")

	client := api.NewClient(cfg.Server, log)
	loop := heartbeat.New(cfg, client, log)
	loop.Run(p.stop)

	log.Info().Str("event", "shutdown").Msg("rs-agent stopped")
}

func NewService(prg *Program) (service.Service, error) {
	absConfig, err := filepath.Abs(prg.ConfigPath)
	if err != nil {
		return nil, err
	}
	prg.ConfigPath = absConfig

	exePath, err := os.Executable()
	if err != nil {
		return nil, err
	}
	exePath, err = filepath.Abs(exePath)
	if err != nil {
		return nil, err
	}

	workDir := filepath.Dir(absConfig)
	if filepath.Base(workDir) == "configs" {
		workDir = filepath.Dir(workDir)
	}

	return service.New(prg, &service.Config{
		Name:             ServiceName,
		DisplayName:      ServiceDisplayName,
		Description:      ServiceDescription,
		Executable:       exePath,
		Arguments:        []string{"-config", absConfig},
		WorkingDirectory: workDir,
		Option: service.KeyValue{
			"StartType": "automatic",
		},
	})
}

func Control(s service.Service, action string) error {
	return service.Control(s, action)
}

func resolveLogsDir(configPath string) string {
	baseDir := filepath.Dir(configPath)
	if filepath.Base(baseDir) == "configs" {
		return filepath.Clean(filepath.Join(baseDir, "..", "logs"))
	}
	return filepath.Join(filepath.Dir(configPath), "logs")
}
