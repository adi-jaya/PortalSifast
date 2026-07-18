package logger

import (
	"fmt"
	"io"
	"os"
	"path/filepath"
	"strings"
	"time"

	"github.com/rs/zerolog"
)

func Setup(logsDir, level, deviceUUID string) (zerolog.Logger, func(), error) {
	if err := os.MkdirAll(logsDir, 0o755); err != nil {
		return zerolog.Logger{}, nil, fmt.Errorf("mkdir logs: %w", err)
	}

	filename := filepath.Join(logsDir, time.Now().Format("2006-01-02")+".log")
	file, err := os.OpenFile(filename, os.O_CREATE|os.O_APPEND|os.O_WRONLY, 0o644)
	if err != nil {
		return zerolog.Logger{}, nil, fmt.Errorf("open log file: %w", err)
	}

	multi := io.MultiWriter(file, os.Stderr)
	lvl, err := zerolog.ParseLevel(strings.ToLower(level))
	if err != nil {
		lvl = zerolog.InfoLevel
	}

	base := zerolog.New(multi).Level(lvl).With().
		Timestamp().
		Str("component", "rs-agent").
		Str("device_uuid", deviceUUID).
		Logger()

	cleanup := func() {
		_ = file.Close()
	}

	return base, cleanup, nil
}
