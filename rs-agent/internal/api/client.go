package api

import (
	"fmt"
	"strings"
	"time"

	"github.com/go-resty/resty/v2"
	"github.com/portalsifast/rs-agent/internal/collector"
	"github.com/rs/zerolog"
)

type Client struct {
	http   *resty.Client
	server string
	log    zerolog.Logger
}

type registerResponse struct {
	Success bool `json:"success"`
	Data    struct {
		DeviceID  uint64 `json:"device_id"`
		UUID      string `json:"uuid"`
		APIKey    string `json:"api_key"`
		RequestID string `json:"request_id"`
	} `json:"data"`
	Error *struct {
		Code      string `json:"code"`
		Message   string `json:"message"`
		RequestID string `json:"request_id"`
	} `json:"error"`
}

type heartbeatResponse struct {
	Success bool `json:"success"`
	Data    struct {
		Status     string `json:"status"`
		LastSeenAt string `json:"last_seen_at"`
		RequestID  string `json:"request_id"`
	} `json:"data"`
	Error *struct {
		Code      string `json:"code"`
		Message   string `json:"message"`
		RequestID string `json:"request_id"`
	} `json:"error"`
}

func NewClient(server string, log zerolog.Logger) *Client {
	http := resty.New().
		SetTimeout(10 * time.Second).
		SetRetryCount(2).
		SetRetryWaitTime(500 * time.Millisecond).
		SetRetryMaxWaitTime(2 * time.Second)

	return &Client{
		http:   http,
		server: strings.TrimRight(server, "/"),
		log:    log,
	}
}

func (c *Client) Register(enrollmentKey string, snap collector.Snapshot) (apiKey string, requestID string, err error) {
	payload := map[string]any{
		"enrollment_key": enrollmentKey,
		"uuid":           snap.UUID,
		"hostname":       snap.Hostname,
		"computer_name":  snap.ComputerName,
		"ip_address":     snap.IPAddress,
		"mac_address":    snap.MACAddress,
		"agent_version":  snap.AgentVersion,
		"hardware": map[string]any{
			"os":            snap.Hardware.OS,
			"os_version":    snap.Hardware.OSVersion,
			"architecture":  snap.Hardware.Architecture,
			"cpu_model":     snap.Hardware.CPUModel,
			"cpu_cores":     snap.Hardware.CPUCores,
			"ram_total_mb":  snap.Hardware.RAMTotalMB,
			"disk_total_gb": snap.Hardware.DiskTotalGB,
			"manufacturer":  snap.Hardware.Manufacturer,
			"model":         snap.Hardware.Model,
			"serial_number": snap.Hardware.SerialNumber,
			"motherboard":   snap.Hardware.Motherboard,
			"bios":          snap.Hardware.BIOS,
			"boot_time":     snap.Hardware.BootTime.Format(time.RFC3339),
			"timezone":      snap.Hardware.Timezone,
			"domain":        snap.Hardware.Domain,
			"username":      snap.Hardware.Username,
		},
		"critical_software": criticalSoftwarePayload(snap),
		"usb":               usbPayload(snap),
		"sensors":           sensorsPayload(snap),
	}

	var body registerResponse
	resp, err := c.http.R().
		SetHeader("Content-Type", "application/json").
		SetBody(payload).
		SetResult(&body).
		Post(c.server + "/api/agent/register")
	if err != nil {
		return "", "", err
	}

	if resp.IsError() || !body.Success {
		msg := "register failed"
		rid := ""
		if body.Error != nil {
			msg = body.Error.Message
			rid = body.Error.RequestID
		}
		return "", rid, fmt.Errorf("%s (status %d)", msg, resp.StatusCode())
	}

	return body.Data.APIKey, body.Data.RequestID, nil
}

func (c *Client) Heartbeat(apiKey string, snap collector.Snapshot) (requestID string, err error) {
	payload := map[string]any{
		"cpu_percent":    snap.Metrics.CPUPercent,
		"ram_percent":    snap.Metrics.RAMPercent,
		"disk_percent":   snap.Metrics.DiskPercent,
		"uptime_seconds": snap.Metrics.UptimeSeconds,
		"hostname":       snap.Hostname,
		"computer_name":  snap.ComputerName,
		"ip_address":     snap.IPAddress,
		"mac_address":    snap.MACAddress,
		"agent_version":  snap.AgentVersion,
		"hardware": map[string]any{
			"manufacturer":  snap.Hardware.Manufacturer,
			"model":         snap.Hardware.Model,
			"serial_number": snap.Hardware.SerialNumber,
			"motherboard":   snap.Hardware.Motherboard,
			"bios":          snap.Hardware.BIOS,
			"domain":        snap.Hardware.Domain,
			"username":      snap.Hardware.Username,
		},
		"critical_software": criticalSoftwarePayload(snap),
		"usb":               usbPayload(snap),
		"sensors":           sensorsPayload(snap),
	}

	var body heartbeatResponse
	resp, err := c.http.R().
		SetHeader("Content-Type", "application/json").
		SetHeader("Authorization", "Bearer "+apiKey).
		SetBody(payload).
		SetResult(&body).
		Post(c.server + "/api/agent/heartbeat")
	if err != nil {
		return "", err
	}

	if resp.IsError() || !body.Success {
		msg := "heartbeat failed"
		rid := ""
		if body.Error != nil {
			msg = body.Error.Message
			rid = body.Error.RequestID
		}
		return rid, fmt.Errorf("%s (status %d)", msg, resp.StatusCode())
	}

	return body.Data.RequestID, nil
}

func criticalSoftwarePayload(snap collector.Snapshot) []map[string]any {
	out := make([]map[string]any, 0, len(snap.CriticalSoftware))
	for _, s := range snap.CriticalSoftware {
		out = append(out, map[string]any{
			"id":     s.ID,
			"name":   s.Name,
			"status": s.Status,
			"detail": s.Detail,
		})
	}
	return out
}

func sensorsPayload(snap collector.Snapshot) map[string]any {
	readings := make([]map[string]any, 0, len(snap.Sensors.Readings))
	for _, r := range snap.Sensors.Readings {
		readings = append(readings, map[string]any{
			"name":          r.Name,
			"temperature_c": r.TemperatureC,
		})
	}
	return map[string]any{
		"supported": snap.Sensors.Supported,
		"note":      snap.Sensors.Note,
		"readings":  readings,
	}
}

func usbPayload(snap collector.Snapshot) map[string]any {
	devices := make([]map[string]any, 0, len(snap.USB.Devices))
	for _, d := range snap.USB.Devices {
		devices = append(devices, map[string]any{
			"name":      d.Name,
			"kind":      d.Kind,
			"device_id": d.DeviceID,
		})
	}
	return map[string]any{
		"ports_total":             snap.USB.PortsTotal,
		"ports_used":              snap.USB.PortsUsed,
		"ports_empty":             snap.USB.PortsEmpty,
		"removable_storage_count": snap.USB.RemovableStorageCount,
		"has_removable_storage":   snap.USB.HasRemovableStorage,
		"printer_count":           snap.USB.PrinterCount,
		"estimated":               snap.USB.Estimated,
		"note":                    snap.USB.Note,
		"devices":                 devices,
	}
}
