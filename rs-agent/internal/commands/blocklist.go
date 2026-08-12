package commands

import (
	"path/filepath"
	"strings"
)

var blockedExe = map[string]struct{}{
	"csrss.exe":       {},
	"dwm.exe":         {},
	"explorer.exe":    {},
	"fontdrvhost.exe": {},
	"lsass.exe":       {},
	"lsm.exe":         {},
	"registry":        {},
	"rs-agent.exe":    {},
	"services.exe":    {},
	"smss.exe":        {},
	"svchost.exe":     {},
	"system":          {},
	"taskmgr.exe":     {},
	"wininit.exe":     {},
	"winlogon.exe":    {},
}

func NormalizedExe(path string) string {
	cleaned := strings.ReplaceAll(strings.TrimSpace(path), "\\", "/")
	return strings.ToLower(filepath.Base(cleaned))
}

func IsBlocked(exe string) bool {
	name := NormalizedExe(exe)
	if name == "" {
		return false
	}
	_, ok := blockedExe[name]
	return ok
}
