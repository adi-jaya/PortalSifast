//go:build !windows

package commands

import "fmt"

type DesktopCapture struct {
	Format     string
	Width      int
	Height     int
	SizeBytes  int
	ImageBytes []byte
}

func captureDesktop() (DesktopCapture, error) {
	return DesktopCapture{}, fmt.Errorf("desktop capture only supported on Windows")
}

func desktopCaptureResult(cap DesktopCapture) map[string]any {
	return map[string]any{
		"format":       cap.Format,
		"width":        cap.Width,
		"height":       cap.Height,
		"size_bytes":   cap.SizeBytes,
		"image_base64": "",
	}
}
