package heartbeat

import (
	"encoding/json"
	"time"

	"github.com/portalsifast/rs-agent/internal/api"
	"github.com/portalsifast/rs-agent/internal/collector"
	"github.com/portalsifast/rs-agent/internal/commands"
	"github.com/portalsifast/rs-agent/internal/config"
	"github.com/rs/zerolog"
)

const (
	DefaultInterval   = 10 * time.Second
	DefaultMaxBackoff = 15 * time.Minute
)

// API is the subset of the HTTP client used by the heartbeat loop.
type API interface {
	Register(enrollmentKey string, snap collector.Snapshot) (apiKey string, requestID string, err error)
	Heartbeat(apiKey string, snap collector.Snapshot) (api.HeartbeatResult, error)
	ReportCommand(apiKey string, commandID uint64, status string, result map[string]any) error
}

type CommandRunner interface {
	Run(cmd commands.Command) (status string, result map[string]any)
}

type Loop struct {
	cfg     *config.Config
	client  API
	log     zerolog.Logger
	version string

	// Overridable for tests.
	Collect    func(deviceUUID, agentVersion string) (collector.Snapshot, error)
	Commands   CommandRunner
	Interval   time.Duration
	MaxBackoff time.Duration
}

func New(cfg *config.Config, client API, log zerolog.Logger, version string) *Loop {
	interval := time.Duration(cfg.Interval) * time.Second
	if interval <= 0 {
		interval = DefaultInterval
	}
	if version == "" {
		version = "0.0.0"
	}
	return &Loop{
		cfg:        cfg,
		client:     client,
		log:        log,
		version:    version,
		Collect:    collector.Collect,
		Commands:   commands.NewRunner(),
		Interval:   interval,
		MaxBackoff: DefaultMaxBackoff,
	}
}

func NextBackoff(current, base, max time.Duration) time.Duration {
	if current <= 0 {
		current = base
	}
	next := current * 2
	if next > max {
		return max
	}
	if next < base {
		return base
	}
	return next
}

func (l *Loop) Run(stop <-chan struct{}) {
	wait := l.Interval
	timer := time.NewTimer(0)
	defer timer.Stop()

	for {
		select {
		case <-stop:
			return
		case <-timer.C:
			ok := l.tick()
			if ok {
				wait = l.Interval
			} else {
				wait = NextBackoff(wait, l.Interval, l.MaxBackoff)
			}
			timer.Reset(wait)
		}
	}
}

func (l *Loop) tick() bool {
	defer func() {
		if r := recover(); r != nil {
			l.log.Error().Interface("panic", r).Str("event", "error").Msg("heartbeat panic recovered")
		}
	}()

	snap, err := l.Collect(l.cfg.UUID, l.version)
	if err != nil {
		l.log.Error().Err(err).Str("event", "error").Msg("collect failed")
		return false
	}

	if l.cfg.APIKey == "" {
		l.log.Info().Str("event", "register").Msg("registering device")
		apiKey, requestID, err := l.client.Register(l.cfg.EnrollmentKey, snap)
		if err != nil {
			l.log.Error().Err(err).Str("event", "retry").Str("request_id", requestID).Msg("register failed")
			return false
		}
		if err := l.cfg.SaveAPIKey(apiKey); err != nil {
			l.log.Error().Err(err).Str("event", "error").Msg("persist api key failed")
			return false
		}
		if err := l.cfg.ClearEnrollmentKey(); err != nil {
			l.log.Error().Err(err).Str("event", "error").Msg("clear enrollment key failed")
			return false
		}
		l.log.Info().Str("event", "register").Str("request_id", requestID).Msg("registered")
	}

	hb, err := l.client.Heartbeat(l.cfg.APIKey, snap)
	if err != nil {
		l.log.Error().Err(err).Str("event", "retry").Str("request_id", hb.RequestID).Msg("heartbeat failed")
		return false
	}

	l.runPendingCommands(hb.Commands)

	l.log.Debug().
		Str("event", "heartbeat").
		Str("request_id", hb.RequestID).
		Int("commands", len(hb.Commands)).
		Float64("cpu", snap.Metrics.CPUPercent).
		Float64("ram", snap.Metrics.RAMPercent).
		Float64("disk", snap.Metrics.DiskPercent).
		Msg("heartbeat ok")
	return true
}

func (l *Loop) runPendingCommands(pending []api.PendingCommand) {
	if l.Commands == nil || len(pending) == 0 {
		return
	}

	for _, item := range pending {
		payload := map[string]any{}
		if len(item.Payload) > 0 && string(item.Payload) != "null" && string(item.Payload) != "[]" {
			_ = json.Unmarshal(item.Payload, &payload)
		}
		cmd := commands.Command{
			ID:      item.ID,
			Type:    item.Type,
			Payload: payload,
		}
		status, result := l.Commands.Run(cmd)
		if err := l.client.ReportCommand(l.cfg.APIKey, item.ID, status, result); err != nil {
			l.log.Error().
				Err(err).
				Uint64("command_id", item.ID).
				Str("type", item.Type).
				Str("event", "error").
				Msg("report command failed")
			continue
		}
		l.log.Info().
			Uint64("command_id", item.ID).
			Str("type", item.Type).
			Str("status", status).
			Str("event", "command").
			Msg("command finished")
	}
}
