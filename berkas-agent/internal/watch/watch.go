package watch

import (
	"fmt"
	"io"
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

// ListReadyFiles returns inbox files whose size stayed stable across one settle window.
// Settle sleeps once for the whole directory (not per file).
func ListReadyFiles(inboxDir string, settle time.Duration) ([]string, error) {
	entries, err := os.ReadDir(inboxDir)
	if err != nil {
		return nil, err
	}

	type candidate struct {
		path string
		size int64
	}
	var first []candidate
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
		info, err := os.Stat(full)
		if err != nil || info.Size() <= 0 {
			continue
		}
		first = append(first, candidate{path: full, size: info.Size()})
	}
	if len(first) == 0 {
		return nil, nil
	}

	if settle > 0 {
		time.Sleep(settle)
	}

	var ready []string
	for _, c := range first {
		info, err := os.Stat(c.path)
		if err != nil || info.Size() <= 0 {
			continue
		}
		if info.Size() == c.size {
			ready = append(ready, c.path)
		}
	}
	return ready, nil
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
// Falls back to copy+delete when os.Rename fails (e.g. cross-volume on Windows).
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
	if err := os.Rename(src, dest); err == nil {
		return dest, nil
	} else if err := copyRemove(src, dest); err != nil {
		return "", fmt.Errorf("move %s → %s: %w", src, dest, err)
	}
	return dest, nil
}

func copyRemove(src, dest string) error {
	in, err := os.Open(src)
	if err != nil {
		return err
	}
	defer in.Close()

	out, err := os.OpenFile(dest, os.O_CREATE|os.O_WRONLY|os.O_TRUNC, 0o644)
	if err != nil {
		return err
	}

	if _, err := io.Copy(out, in); err != nil {
		_ = out.Close()
		_ = os.Remove(dest)
		return err
	}
	if err := out.Close(); err != nil {
		_ = os.Remove(dest)
		return err
	}
	if err := os.Remove(src); err != nil {
		return fmt.Errorf("copied but failed to remove source: %w", err)
	}
	return nil
}
