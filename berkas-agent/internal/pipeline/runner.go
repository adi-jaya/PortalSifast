package pipeline

import (
	"fmt"
	"log"
	"path/filepath"
	"time"

	"github.com/portalsifast/berkas-agent/internal/api"
	"github.com/portalsifast/berkas-agent/internal/classify"
	"github.com/portalsifast/berkas-agent/internal/config"
	"github.com/portalsifast/berkas-agent/internal/ocr"
	"github.com/portalsifast/berkas-agent/internal/watch"
)

type Runner struct {
	Cfg    *config.Config
	OCR    *ocr.Engine
	Client *api.Client
	Log    *log.Logger
}

func (r *Runner) EnsureDirs() error {
	return watch.EnsureDirs(r.Cfg.InboxDir, r.Cfg.ProcessedDir, r.Cfg.ErrorDir)
}

func (r *Runner) Tick() error {
	files, err := watch.ListReadyFiles(r.Cfg.InboxDir, 800*time.Millisecond)
	if err != nil {
		return err
	}
	for _, path := range files {
		if err := r.processOne(path); err != nil {
			r.Log.Printf("process %s: %v", filepath.Base(path), err)
			if _, moveErr := watch.MoveUnique(path, r.Cfg.ErrorDir); moveErr != nil {
				r.Log.Printf("move to error: %v", moveErr)
			}
		}
	}
	return nil
}

func (r *Runner) processOne(path string) error {
	r.Log.Printf("processing %s", filepath.Base(path))

	text, ocrErr := r.OCR.Recognize(path)
	result := classify.Classify(text)
	if ocrErr != nil {
		r.Log.Printf("ocr warning %s: %v (continue with empty/partial text)", filepath.Base(path), ocrErr)
		if text == "" {
			result = classify.Classify("")
		}
	}

	if _, err := r.Client.PostInbox(path, result); err != nil {
		return fmt.Errorf("portal: %w", err)
	}

	if _, err := watch.MoveUnique(path, r.Cfg.ProcessedDir); err != nil {
		return fmt.Errorf("move processed: %w", err)
	}
	r.Log.Printf("ok %s → kode=%s conf=%.2f", filepath.Base(path), result.SuggestedKode, result.Confidence)
	return nil
}

func (r *Runner) RunLoop(stop <-chan struct{}) {
	ticker := time.NewTicker(time.Duration(r.Cfg.PollSeconds) * time.Second)
	defer ticker.Stop()

	r.Log.Printf("berkas-agent watching %s every %ds", r.Cfg.InboxDir, r.Cfg.PollSeconds)
	_ = r.Tick()

	for {
		select {
		case <-stop:
			r.Log.Printf("stopping")
			return
		case <-ticker.C:
			_ = r.Tick()
		}
	}
}
