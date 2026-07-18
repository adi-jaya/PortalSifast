# RS Agent (Phase 1)

Lightweight Go agent for PortalSifast monitoring.

Canonical contract: [`../docs/monorepo/rs-agent-phase1.md`](../docs/monorepo/rs-agent-phase1.md)

## Phase 1 only

- Register device
- Heartbeat (CPU / RAM / Disk)
- Daily JSON logs (`logs/YYYY-MM-DD.log`)

No auto-update, remote jobs, Windows/systemd installer, or offline buffer.

## NFR

| Target | Value |
|--------|-------|
| RAM | < 50 MB |
| Idle CPU | < 2% |
| Interval | 30s default |
| Server heartbeat | ≤ 200 ms (Laravel side) |

## Setup

1. Copy config:

```bash
cp configs/config.example.json configs/config.json
```

2. Set `server` and `enrollment_key` (must match Laravel `AGENT_ENROLLMENT_KEY`).

3. Build (requires Go 1.22+):

```bash
cd rs-agent
go mod tidy
go build -o rs-agent ./cmd/rs-agent
```

4. Run:

```bash
./rs-agent -config configs/config.json
```

On first run the agent registers, stores `api_key` + `uuid` in `config.json`, then heartbeats every `interval` seconds.

## Debugging

- Agent logs: `rs-agent/logs/YYYY-MM-DD.log`
- Laravel logs: `storage/logs/agent-YYYY-MM-DD.log`
- Correlate with `request_id` in both logs and API error JSON

## Libraries

- `gopsutil` — metrics
- `resty` — HTTP client (reused)
- `zerolog` — structured logging
