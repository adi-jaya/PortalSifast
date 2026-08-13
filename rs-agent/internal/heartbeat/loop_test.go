package heartbeat

import (
	"errors"
	"os"
	"sync"
	"sync/atomic"
	"testing"
	"time"

	"github.com/portalsifast/rs-agent/internal/api"
	"github.com/portalsifast/rs-agent/internal/collector"
	"github.com/portalsifast/rs-agent/internal/commands"
	"github.com/portalsifast/rs-agent/internal/config"
	"github.com/rs/zerolog"
)

type stubAPI struct {
	registerCalls  atomic.Int32
	heartbeatCalls atomic.Int32
	reportCalls    atomic.Int32
	registerErr    error
	heartbeatErr   error
	reportErr      error
	apiKey         string
	commands       []api.PendingCommand

	mu      sync.Mutex
	reports []reportCall
}

type reportCall struct {
	id     uint64
	status string
	result map[string]any
}

func (s *stubAPI) Register(_ string, _ collector.Snapshot) (string, string, error) {
	s.registerCalls.Add(1)
	if s.registerErr != nil {
		return "", "req-reg", s.registerErr
	}
	return s.apiKey, "req-reg", nil
}

func (s *stubAPI) Heartbeat(_ string, _ collector.Snapshot) (api.HeartbeatResult, error) {
	s.heartbeatCalls.Add(1)
	if s.heartbeatErr != nil {
		return api.HeartbeatResult{RequestID: "req-hb"}, s.heartbeatErr
	}
	return api.HeartbeatResult{RequestID: "req-hb", Commands: s.commands}, nil
}

func (s *stubAPI) ReportCommand(_ string, commandID uint64, status string, result map[string]any) error {
	s.reportCalls.Add(1)
	s.mu.Lock()
	s.reports = append(s.reports, reportCall{id: commandID, status: status, result: result})
	s.mu.Unlock()
	return s.reportErr
}

func TestNextBackoff(t *testing.T) {
	t.Parallel()

	cases := []struct {
		current time.Duration
		want    time.Duration
	}{
		{30 * time.Second, 60 * time.Second},
		{60 * time.Second, 120 * time.Second},
		{8 * time.Minute, 15 * time.Minute},
		{15 * time.Minute, 15 * time.Minute},
	}
	for _, tc := range cases {
		got := NextBackoff(tc.current, 30*time.Second, 15*time.Minute)
		if got != tc.want {
			t.Fatalf("NextBackoff(%v)=%v want %v", tc.current, got, tc.want)
		}
	}
}

func TestLoopStopsCleanly(t *testing.T) {
	t.Parallel()

	dir := t.TempDir()
	path := dir + "/config.json"
	writeTestConfig(t, path, "", "rsag_ready")

	cfg, err := config.Load(path)
	if err != nil {
		t.Fatal(err)
	}

	api := &stubAPI{apiKey: "rsag_ready"}
	loop := New(cfg, api, zerolog.Nop(), "0.2.0-test")
	loop.Collect = func(uuid, version string) (collector.Snapshot, error) {
		return collector.Snapshot{UUID: uuid, AgentVersion: version}, nil
	}
	loop.Interval = 50 * time.Millisecond
	loop.MaxBackoff = 200 * time.Millisecond

	stop := make(chan struct{})
	done := make(chan struct{})
	go func() {
		defer close(done)
		loop.Run(stop)
	}()

	time.Sleep(120 * time.Millisecond)
	close(stop)

	select {
	case <-done:
	case <-time.After(2 * time.Second):
		t.Fatal("loop did not stop")
	}

	if api.heartbeatCalls.Load() == 0 {
		t.Fatal("expected at least one heartbeat")
	}
	if api.registerCalls.Load() != 0 {
		t.Fatalf("unexpected register calls: %d", api.registerCalls.Load())
	}
}

func TestLoopClearsEnrollmentAfterRegister(t *testing.T) {
	t.Parallel()

	dir := t.TempDir()
	path := dir + "/config.json"
	writeTestConfig(t, path, "enroll-secret", "")

	cfg, err := config.Load(path)
	if err != nil {
		t.Fatal(err)
	}

	api := &stubAPI{apiKey: "rsag_new"}
	loop := New(cfg, api, zerolog.Nop(), "0.2.0-test")
	loop.Collect = func(uuid, version string) (collector.Snapshot, error) {
		return collector.Snapshot{UUID: uuid, AgentVersion: version}, nil
	}
	loop.Interval = 50 * time.Millisecond
	loop.MaxBackoff = 200 * time.Millisecond

	stop := make(chan struct{})
	done := make(chan struct{})
	go func() {
		defer close(done)
		loop.Run(stop)
	}()

	deadline := time.Now().Add(2 * time.Second)
	for time.Now().Before(deadline) {
		if cfg.APIKey == "rsag_new" && cfg.EnrollmentKey == "" {
			break
		}
		time.Sleep(20 * time.Millisecond)
	}
	close(stop)
	<-done

	if cfg.APIKey != "rsag_new" {
		t.Fatalf("api_key=%q", cfg.APIKey)
	}
	if cfg.EnrollmentKey != "" {
		t.Fatalf("enrollment_key still set: %q", cfg.EnrollmentKey)
	}
	if api.registerCalls.Load() == 0 {
		t.Fatal("expected register call")
	}
}

func TestLoopBacksOffOnFailure(t *testing.T) {
	t.Parallel()

	dir := t.TempDir()
	path := dir + "/config.json"
	writeTestConfig(t, path, "", "rsag_ready")

	cfg, err := config.Load(path)
	if err != nil {
		t.Fatal(err)
	}

	api := &stubAPI{heartbeatErr: errors.New("down")}
	loop := New(cfg, api, zerolog.Nop(), "0.2.0-test")
	loop.Collect = func(uuid, version string) (collector.Snapshot, error) {
		return collector.Snapshot{UUID: uuid, AgentVersion: version}, nil
	}
	loop.Interval = 40 * time.Millisecond
	loop.MaxBackoff = 160 * time.Millisecond

	stop := make(chan struct{})
	done := make(chan struct{})
	go func() {
		defer close(done)
		loop.Run(stop)
	}()

	time.Sleep(220 * time.Millisecond)
	close(stop)
	<-done

	calls := api.heartbeatCalls.Load()
	if calls < 2 {
		t.Fatalf("expected multiple retries, got %d", calls)
	}
	if calls > 6 {
		t.Fatalf("backoff too aggressive / too many calls: %d", calls)
	}
}

func TestLoopRunsPendingCommands(t *testing.T) {
	t.Parallel()

	dir := t.TempDir()
	path := dir + "/config.json"
	writeTestConfig(t, path, "", "rsag_ready")

	cfg, err := config.Load(path)
	if err != nil {
		t.Fatal(err)
	}

	apiClient := &stubAPI{
		apiKey: "rsag_ready",
		commands: []api.PendingCommand{
			{ID: 9, Type: commands.TypeListWindows, Payload: []byte(`{}`)},
		},
	}
	loop := New(cfg, apiClient, zerolog.Nop(), "0.3.0-test")
	loop.Collect = func(uuid, version string) (collector.Snapshot, error) {
		return collector.Snapshot{UUID: uuid, AgentVersion: version}, nil
	}
	loop.Commands = &commands.Runner{
		ListWindows: func() ([]commands.Window, error) {
			return []commands.Window{{PID: 11, Exe: "notepad.exe", Title: "Untitled"}}, nil
		},
	}
	loop.Interval = 40 * time.Millisecond
	loop.MaxBackoff = 120 * time.Millisecond

	stop := make(chan struct{})
	done := make(chan struct{})
	go func() {
		defer close(done)
		loop.Run(stop)
	}()

	deadline := time.Now().Add(2 * time.Second)
	for time.Now().Before(deadline) {
		if apiClient.reportCalls.Load() > 0 {
			break
		}
		time.Sleep(20 * time.Millisecond)
	}
	close(stop)
	<-done

	if apiClient.reportCalls.Load() == 0 {
		t.Fatal("expected command result report")
	}
	apiClient.mu.Lock()
	defer apiClient.mu.Unlock()
	if apiClient.reports[0].id != 9 || apiClient.reports[0].status != commands.StatusSucceeded {
		t.Fatalf("report=%+v", apiClient.reports[0])
	}
}

func writeTestConfig(t *testing.T, path, enrollment, apiKey string) {
	t.Helper()
	content := `{
  "server": "https://example.com",
  "enrollment_key": "` + enrollment + `",
  "api_key": "` + apiKey + `",
  "interval": 30,
  "log_level": "error",
  "uuid": "11111111-1111-4111-8111-111111111111"
}`
	if err := os.WriteFile(path, []byte(content), 0o600); err != nil {
		t.Fatal(err)
	}
}
