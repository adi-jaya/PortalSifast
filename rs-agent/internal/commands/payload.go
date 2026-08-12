package commands

import (
	"encoding/json"
	"strconv"
)

func PayloadPID(payload map[string]any) (uint32, bool) {
	if payload == nil {
		return 0, false
	}
	raw, ok := payload["pid"]
	if !ok || raw == nil {
		return 0, false
	}

	switch v := raw.(type) {
	case float64:
		if v < 1 {
			return 0, false
		}
		return uint32(v), true
	case float32:
		if v < 1 {
			return 0, false
		}
		return uint32(v), true
	case int:
		if v < 1 {
			return 0, false
		}
		return uint32(v), true
	case int64:
		if v < 1 {
			return 0, false
		}
		return uint32(v), true
	case json.Number:
		n, err := v.Int64()
		if err != nil || n < 1 {
			return 0, false
		}
		return uint32(n), true
	case string:
		n, err := strconv.ParseUint(v, 10, 32)
		if err != nil || n < 1 {
			return 0, false
		}
		return uint32(n), true
	default:
		return 0, false
	}
}

func PayloadExe(payload map[string]any) string {
	if payload == nil {
		return ""
	}
	raw, _ := payload["exe"].(string)
	return raw
}
