//go:build windows

package commands

import (
	"encoding/base64"
	"fmt"
	"os"
	"path/filepath"
	"strings"
	"unsafe"

	"golang.org/x/sys/windows"
)

type DesktopCapture struct {
	Format     string
	Width      int
	Height     int
	SizeBytes  int
	ImageBytes []byte
}

func captureDesktop() (DesktopCapture, error) {
	sessionIDs, err := activeUserSessionIDs()
	if err != nil {
		return DesktopCapture{}, err
	}
	if len(sessionIDs) == 0 {
		return DesktopCapture{}, fmt.Errorf("no active user session for desktop capture")
	}

	var lastErr error
	for _, sessionID := range sessionIDs {
		cap, err := captureDesktopInUserSession(sessionID)
		if err == nil {
			return cap, nil
		}
		lastErr = err
	}
	if lastErr == nil {
		lastErr = fmt.Errorf("desktop capture failed")
	}
	return DesktopCapture{}, lastErr
}

func captureDesktopInUserSession(sessionID uint32) (DesktopCapture, error) {
	var userToken windows.Token
	r, _, err := procWTSQueryUserToken.Call(uintptr(sessionID), uintptr(unsafe.Pointer(&userToken)))
	if r == 0 {
		return DesktopCapture{}, fmt.Errorf("WTSQueryUserToken: %w", err)
	}
	defer userToken.Close()

	primary, err := duplicatePrimaryToken(userToken)
	if err != nil {
		return DesktopCapture{}, err
	}
	defer primary.Close()

	publicDir := os.Getenv("PUBLIC")
	if publicDir == "" {
		publicDir = `C:\Users\Public`
	}
	outPath := filepath.Join(publicDir, fmt.Sprintf("portalsifast-desktop-%d.jpg", sessionID))
	metaPath := filepath.Join(publicDir, fmt.Sprintf("portalsifast-desktop-%d.txt", sessionID))
	_ = os.Remove(outPath)
	_ = os.Remove(metaPath)
	defer os.Remove(outPath)
	defer os.Remove(metaPath)

	ps := windowsPowerShellPath()
	script := fmt.Sprintf(
		`$ErrorActionPreference='Stop'; `+
			`Add-Type -AssemblyName System.Windows.Forms; `+
			`Add-Type -AssemblyName System.Drawing; `+
			`$bounds = [System.Windows.Forms.Screen]::PrimaryScreen.Bounds; `+
			`$bmp = New-Object System.Drawing.Bitmap $bounds.Width, $bounds.Height; `+
			`$g = [System.Drawing.Graphics]::FromImage($bmp); `+
			`$g.CopyFromScreen($bounds.Location, [System.Drawing.Point]::Empty, $bounds.Size); `+
			`$g.Dispose(); `+
			`$maxW = 1280; `+
			`if ($bmp.Width -gt $maxW) { `+
			`  $ratio = $maxW / [double]$bmp.Width; `+
			`  $nw = $maxW; $nh = [int]([math]::Round($bmp.Height * $ratio)); `+
			`  $scaled = New-Object System.Drawing.Bitmap $nw, $nh; `+
			`  $sg = [System.Drawing.Graphics]::FromImage($scaled); `+
			`  $sg.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic; `+
			`  $sg.DrawImage($bmp, 0, 0, $nw, $nh); $sg.Dispose(); $bmp.Dispose(); $bmp = $scaled; `+
			`} `+
			`$encoder = [System.Drawing.Imaging.ImageCodecInfo]::GetImageEncoders() | Where-Object { $_.MimeType -eq 'image/jpeg' }; `+
			`$ep = New-Object System.Drawing.Imaging.EncoderParameters 1; `+
			`$ep.Param[0] = New-Object System.Drawing.Imaging.EncoderParameter ([System.Drawing.Imaging.Encoder]::Quality, 70L); `+
			`$bmp.Save('%s', $encoder, $ep); `+
			`$len = (Get-Item -LiteralPath '%s').Length; `+
			`("$len|$($bmp.Width)|$($bmp.Height)") | Set-Content -LiteralPath '%s' -Encoding ASCII; `+
			`$bmp.Dispose()`,
		escapePSPath(outPath),
		escapePSPath(outPath),
		escapePSPath(metaPath),
	)

	cmdLine, err := windows.UTF16PtrFromString(fmt.Sprintf(
		`"%s" -NoProfile -NonInteractive -ExecutionPolicy Bypass -Command %s`,
		ps,
		quoteForCmd(script),
	))
	if err != nil {
		return DesktopCapture{}, err
	}

	var env *uint16
	if er, _, e := procCreateEnvironmentBlock.Call(uintptr(unsafe.Pointer(&env)), uintptr(primary), 0); er == 0 {
		return DesktopCapture{}, fmt.Errorf("CreateEnvironmentBlock: %w", e)
	}
	defer procDestroyEnvironmentBlock.Call(uintptr(unsafe.Pointer(env)))

	var si windows.StartupInfo
	si.Cb = uint32(unsafe.Sizeof(si))
	si.Flags = windows.STARTF_USESHOWWINDOW
	si.ShowWindow = windows.SW_HIDE
	desktop, _ := windows.UTF16PtrFromString(`winsta0\default`)
	si.Desktop = desktop

	var pi windows.ProcessInformation
	flags := uint32(createUnicodeEnvironment | createNoWindow)
	err = windows.CreateProcessAsUser(primary, nil, cmdLine, nil, nil, false, flags, env, nil, &si, &pi)
	if err != nil {
		flags = uint32(createUnicodeEnvironment | createNewConsole)
		err = windows.CreateProcessAsUser(primary, nil, cmdLine, nil, nil, false, flags, env, nil, &si, &pi)
		if err != nil {
			return DesktopCapture{}, fmt.Errorf("CreateProcessAsUser: %w", err)
		}
	}
	defer windows.CloseHandle(pi.Thread)
	defer windows.CloseHandle(pi.Process)

	wait, err := windows.WaitForSingleObject(pi.Process, 20000)
	if err != nil {
		return DesktopCapture{}, fmt.Errorf("wait capture helper: %w", err)
	}
	if wait != windows.WAIT_OBJECT_0 {
		_ = windows.TerminateProcess(pi.Process, 1)
		return DesktopCapture{}, fmt.Errorf("capture helper timed out")
	}

	raw, err := os.ReadFile(outPath)
	if err != nil {
		return DesktopCapture{}, fmt.Errorf("read capture jpeg: %w", err)
	}
	if len(raw) < 100 {
		return DesktopCapture{}, fmt.Errorf("capture jpeg too small")
	}

	width, height := 0, 0
	if meta, mErr := os.ReadFile(metaPath); mErr == nil {
		parts := strings.Split(strings.TrimSpace(string(meta)), "|")
		if len(parts) >= 3 {
			fmt.Sscanf(parts[1], "%d", &width)
			fmt.Sscanf(parts[2], "%d", &height)
		}
	}

	return DesktopCapture{
		Format:     "jpeg",
		Width:      width,
		Height:     height,
		SizeBytes:  len(raw),
		ImageBytes: raw,
	}, nil
}

func desktopCaptureResult(cap DesktopCapture) map[string]any {
	return map[string]any{
		"format":       cap.Format,
		"width":        cap.Width,
		"height":       cap.Height,
		"size_bytes":   cap.SizeBytes,
		"image_base64": base64.StdEncoding.EncodeToString(cap.ImageBytes),
	}
}
