package heartbeat

import (
	"time"

	"github.com/portalsifast/rs-agent/internal/api"
	"github.com/portalsifast/rs-agent/internal/collector"
	"github.com/portalsifast/rs-agent/internal/config"
	"github.com/rs/zerolog"
)

type Loop struct {
	cfg    *config.Config
	client *api.Client
	log    zerolog.Logger
}

func New(cfg *config.Config, client *api.Client, log zerolog.Logger) *Loop {
	return &Loop{cfg: cfg, client: client, log: log}
}

func (l *Loop) Run(stop <-chan struct{}) {
	ticker := time.NewTicker(time.Duration(l.cfg.Interval) * time.Second)
	defer ticker.Stop()

	l.tick()

	for {
		select {
		case <-stop:
			return
		case <-ticker.C:
			l.tick()
		}
	}
}

func (l *Loop) tick() {
	defer func() {
		if r := recover(); r != nil {
			l.log.Error().Interface("panic", r).Str("event", "error").Msg("heartbeat panic recovered")
		}
	}()

	snap, err := collector.Collect(l.cfg.UUID, "0.1.0")
	if err != nil {
		l.log.Error().Err(err).Str("event", "error").Msg("collect failed")
		return
	}

	if l.cfg.APIKey == "" {
		l.log.Info().Str("event", "register").Msg("registering device")
		apiKey, requestID, err := l.client.Register(l.cfg.EnrollmentKey, snap)
		if err != nil {
			l.log.Error().Err(err).Str("event", "retry").Str("request_id", requestID).Msg("register failed")
			return
		}
		if err := l.cfg.SaveAPIKey(apiKey); err != nil {
			l.log.Error().Err(err).Str("event", "error").Msg("persist api key failed")
			return
		}
		l.log.Info().Str("event", "register").Str("request_id", requestID).Msg("registered")
	}

	requestID, err := l.client.Heartbeat(l.cfg.APIKey, snap)
	if err != nil {
		l.log.Error().Err(err).Str("event", "retry").Str("request_id", requestID).Msg("heartbeat failed")
		return
	}

	l.log.Debug().
		Str("event", "heartbeat").
		Str("request_id", requestID).
		Float64("cpu", snap.Metrics.CPUPercent).
		Float64("ram", snap.Metrics.RAMPercent).
		Float64("disk", snap.Metrics.DiskPercent).
		Msg("heartbeat ok")
}
