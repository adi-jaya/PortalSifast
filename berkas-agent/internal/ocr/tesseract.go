package ocr

import (
	"bytes"
	"context"
	"fmt"
	"os"
	"os/exec"
	"path/filepath"
	"strings"
	"time"
)

const DefaultTimeout = 60 * time.Second

// Engine runs Tesseract CLI against image (or PDF if build supports it).
type Engine struct {
	TesseractPath string
	Languages     string
	Timeout       time.Duration
}

func (e *Engine) Recognize(filePath string) (string, error) {
	if strings.TrimSpace(e.TesseractPath) == "" {
		return "", fmt.Errorf("tesseract_path is empty")
	}
	if _, err := os.Stat(e.TesseractPath); err != nil {
		return "", fmt.Errorf("tesseract not found: %w", err)
	}
	if _, err := os.Stat(filePath); err != nil {
		return "", fmt.Errorf("input file: %w", err)
	}

	ext := strings.ToLower(filepath.Ext(filePath))
	switch ext {
	case ".jpg", ".jpeg", ".png", ".tif", ".tiff", ".bmp", ".pdf":
		// ok — PDF support depends on local Tesseract/Leptonica build
	default:
		return "", fmt.Errorf("unsupported extension %q", ext)
	}

	langs := e.Languages
	if langs == "" {
		langs = "ind+eng"
	}
	timeout := e.Timeout
	if timeout <= 0 {
		timeout = DefaultTimeout
	}

	ctx, cancel := context.WithTimeout(context.Background(), timeout)
	defer cancel()

	cmd := exec.CommandContext(ctx, e.TesseractPath, filePath, "stdout", "-l", langs, "--psm", "3")
	var stdout, stderr bytes.Buffer
	cmd.Stdout = &stdout
	cmd.Stderr = &stderr
	if err := cmd.Run(); err != nil {
		if ctx.Err() == context.DeadlineExceeded {
			return "", fmt.Errorf("tesseract timed out after %s", timeout)
		}
		msg := strings.TrimSpace(stderr.String())
		if msg == "" {
			msg = err.Error()
		}
		return "", fmt.Errorf("tesseract: %s", msg)
	}
	return stdout.String(), nil
}
