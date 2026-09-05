package watch

import (
	"os"
	"path/filepath"
	"strings"
	"time"
)

var allowedExt = map[string]bool{
	".pdf":  true,
	".jpg":  true,
	".jpeg": true,
	".png":  true,
}

// ListReadyFiles returns inbox files whose size stayed stable across two checks.
func ListReadyFiles(inboxDir string, settle time.Duration) ([]string, error) {
	entries, err := os.ReadDir(inboxDir)
	if err != nil {
		return nil, err
	}

	var ready []string
	for _, ent := range entries {
		if ent.IsDir() {
			continue
		}
		name := ent.Name()
		if strings.HasPrefix(name, ".") || strings.HasPrefix(name, "~") {
			continue
		}
		ext := strings.ToLower(filepath.Ext(name))
		if !allowedExt[ext] {
			continue
		}
		full := filepath.Join(inboxDir, name)
		ok, err := isStable(full, settle)
		if err != nil || !ok {
			continue
		}
		ready = append(ready, full)
	}
	return ready, nil
}

func isStable(path string, settle time.Duration) (bool, error) {
	info1, err := os.Stat(path)
	if err != nil {
		return false, err
	}
	time.Sleep(settle)
	info2, err := os.Stat(path)
	if err != nil {
		return false, err
	}
	return info1.Size() == info2.Size() && info1.Size() > 0, nil
}

// EnsureDirs creates inbox/processed/error directories.
func EnsureDirs(dirs ...string) error {
	for _, d := range dirs {
		if err := os.MkdirAll(d, 0o755); err != nil {
			return err
		}
	}
	return nil
}

// MoveUnique moves src into destDir, avoiding name collisions.
func MoveUnique(src, destDir string) (string, error) {
	if err := os.MkdirAll(destDir, 0o755); err != nil {
		return "", err
	}
	base := filepath.Base(src)
	dest := filepath.Join(destDir, base)
	if _, err := os.Stat(dest); err == nil {
		ext := filepath.Ext(base)
		stem := strings.TrimSuffix(base, ext)
		dest = filepath.Join(destDir, stem+"_"+time.Now().Format("20060102_150405")+ext)
	}
	if err := os.Rename(src, dest); err != nil {
		return "", err
	}
	return dest, nil
}
