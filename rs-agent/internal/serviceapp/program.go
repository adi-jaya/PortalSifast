package serviceapp

import (
	"fmt"
	"os"
	"os/signal"
	"path/filepath"
	"sync"
	"syscall"

	"github.com/kardianos/service"
	"github.com/portalsifast/rs-agent/internal/api"
	"github.com/portalsifast/rs-agent/internal/config"
	"github.com/portalsifast/rs-agent/internal/heartbeat"
	"github.com/portalsifast/rs-agent/internal/logger"
	"github.com/rs/zerolog"
)

const (
	ServiceName        = "PortalSifastAgent"
	ServiceDisplayName = "PortalSifast RS Agent"
	ServiceDescription = "Collects device metrics and sends heartbeats to PortalSifast"
)

type Program struct {
	ConfigPath   string
	AgentVersion string

	stop    chan struct{}
	stopped sync.WaitGroup
	log     zerolog.Logger
}

func (p *Program) Start(_ service.Service) error {
	p.stop = make(chan struct{})
	p.stopped.Add(1)
	go func() {
		defer p.stopped.Done()
		if err := p.run(); err != nil {
			fmt.Fprintf(os.Stderr, "rs-agent: %v\n", err)
		}
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

func (p *Program) run() error {
	cfg, err := config.Load(p.ConfigPath)
	if err != nil {
		return fmt.Errorf("load config: %w", err)
	}

	if cfg.UUID == "" {
		cfg.UUID = NewDeviceUUID()
		if err := cfg.SaveUUID(cfg.UUID); err != nil {
			return fmt.Errorf("save uuid: %w", err)
		}
	}

	logsDir := ResolveLogsDir(p.ConfigPath)
	log, cleanup, err := logger.Setup(logsDir, cfg.LogLevel, cfg.UUID)
	if err != nil {
		return fmt.Errorf("logger: %w", err)
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
	loop := heartbeat.New(cfg, client, log, p.AgentVersion)
	loop.Run(p.stop)

	log.Info().Str("event", "shutdown").Msg("rs-agent stopped")
	return nil
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

	workDir := WorkingDirectoryForConfig(absConfig)

	return service.New(prg, &service.Config{
		Name:             ServiceName,
		DisplayName:      ServiceDisplayName,
		Description:      ServiceDescription,
		Executable:       exePath,
		Arguments:        []string{"-config", absConfig},
		WorkingDirectory: workDir,
		Option: service.KeyValue{
			"StartType":              "automatic",
			"OnFailure":              "restart",
			"OnFailureDelayDuration": "5s",
			"OnFailureResetPeriod":   60,
		},
	})
}

func Control(s service.Service, action string) error {
	return service.Control(s, action)
}

func RunConsole(configPath, version string) error {
	absConfig, err := filepath.Abs(configPath)
	if err != nil {
		return err
	}

	prg := &Program{
		ConfigPath:   absConfig,
		AgentVersion: version,
		stop:         make(chan struct{}),
	}

	sigs := make(chan os.Signal, 1)
	signal.Notify(sigs, os.Interrupt, syscall.SIGTERM)
	go func() {
		<-sigs
		select {
		case <-prg.stop:
		default:
			close(prg.stop)
		}
	}()

	return prg.run()
}

func ResolveLogsDir(configPath string) string {
	baseDir := filepath.Dir(configPath)
	if filepath.Base(baseDir) == "configs" {
		return filepath.Clean(filepath.Join(baseDir, "..", "logs"))
	}
	return filepath.Join(baseDir, "logs")
}

func WorkingDirectoryForConfig(configPath string) string {
	workDir := filepath.Dir(configPath)
	if filepath.Base(workDir) == "configs" {
		return filepath.Dir(workDir)
	}
	return workDir
}
