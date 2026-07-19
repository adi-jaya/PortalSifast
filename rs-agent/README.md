# RS Agent (Phase 1 + Ops)

Lightweight Go agent for PortalSifast monitoring.

- Contract: [`../docs/monorepo/rs-agent-phase1.md`](../docs/monorepo/rs-agent-phase1.md)
- Production + Windows Service: [`../docs/monorepo/rs-agent-ops.md`](../docs/monorepo/rs-agent-ops.md)

## Features

- Register device + heartbeat (CPU / RAM / Disk)
- Daily JSON logs (`logs/YYYY-MM-DD.log`)
- Windows/Linux service via `-service install|start|stop|restart|status|uninstall`

## NFR

| Target | Value |
|--------|-------|
| RAM | < 50 MB |
| Idle CPU | < 2% |
| Interval | 30s default |

## Build

```bash
cd rs-agent
cp configs/config.example.json configs/config.json
# edit server + enrollment_key
go mod tidy
go build -o rs-agent.exe ./cmd/rs-agent   # Windows
# go build -o rs-agent ./cmd/rs-agent    # Linux
```

## Run (console)

```bat
rs-agent.exe -config configs\config.json
```

## Run (Windows Service — Admin)

Cara mudah: `scripts\install-service.bat` (Run as administrator).

Manual:

```bat
rs-agent.exe -config C:\full\path\rs-agent\configs\config.json -service install
rs-agent.exe -config C:\full\path\rs-agent\configs\config.json -service start
```

Uninstall: `scripts\uninstall-service.bat`

## Debugging

- Agent: `rs-agent/logs/YYYY-MM-DD.log`
- Laravel: `storage/logs/agent-YYYY-MM-DD.log`
- Correlate with `request_id`

## Libraries

- `gopsutil` — metrics
- `resty` — HTTP
- `zerolog` — logs
- `kardianos/service` — Windows/Linux service
