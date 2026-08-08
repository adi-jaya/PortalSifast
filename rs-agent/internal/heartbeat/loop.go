package heartbeat

import (
	"time"

	"github.com/portalsifast/rs-agent/internal/collector"
	"github.com/portalsifast/rs-agent/internal/config"
	"github.com/rs/zerolog"
)

const (
	DefaultInterval   = 30 * time.Second
	DefaultMaxBackoff = 15 * time.Minute
)

// API is the subset of the HTTP client used by the heartbeat loop.
type API interface {
	Register(enrollmentKey string, snap collector.Snapshot) (apiKey string, requestID string, err error)
	Heartbeat(apiKey string, snap collector.Snapshot) (requestID string, err error)
}

type Loop struct {
	cfg     *config.Config
	client  API
	log     zerolog.Logger
	version string

	// Overridable for tests.
	Collect    func(deviceUUID, agentVersion string) (collector.Snapshot, error)
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

	requestID, err := l.client.Heartbeat(l.cfg.APIKey, snap)
	if err != nil {
		l.log.Error().Err(err).Str("event", "retry").Str("request_id", requestID).Msg("heartbeat failed")
		return false
	}

	l.log.Debug().
		Str("event", "heartbeat").
		Str("request_id", requestID).
		Float64("cpu", snap.Metrics.CPUPercent).
		Float64("ram", snap.Metrics.RAMPercent).
		Float64("disk", snap.Metrics.DiskPercent).
		Msg("heartbeat ok")
	return true
}
