package main

import (
	"flag"
	"fmt"
	"log"
	"os"
	"os/signal"
	"syscall"

	"github.com/portalsifast/berkas-agent/internal/api"
	"github.com/portalsifast/berkas-agent/internal/config"
	"github.com/portalsifast/berkas-agent/internal/ocr"
	"github.com/portalsifast/berkas-agent/internal/pipeline"
)

// Set via: go build -ldflags "-X main.version=0.1.0"
var version = "0.1.0-dev"

func main() {
	configPath := flag.String("config", "configs/config.json", "path to config.json")
	once := flag.Bool("once", false, "process inbox once and exit")
	showVersion := flag.Bool("version", false, "print version")
	flag.Parse()

	if *showVersion {
		fmt.Println(version)
		return
	}

	cfg, err := config.Load(*configPath)
	if err != nil {
		fmt.Fprintf(os.Stderr, "berkas-agent: %v\n", err)
		os.Exit(1)
	}

	logger := log.New(os.Stdout, "", log.LstdFlags|log.Lmsgprefix)
	logger.SetPrefix("berkas-agent ")

	runner := &pipeline.Runner{
		Cfg: cfg,
		OCR: &ocr.Engine{
			TesseractPath: cfg.TesseractPath,
			Languages:     cfg.Languages,
		},
		Client: api.New(cfg.PortalURL, cfg.APIToken, cfg.AgentLabel),
		Log:    logger,
	}

	if err := runner.EnsureDirs(); err != nil {
		fmt.Fprintf(os.Stderr, "berkas-agent dirs: %v\n", err)
		os.Exit(1)
	}

	if *once {
		if err := runner.Tick(); err != nil {
			fmt.Fprintf(os.Stderr, "berkas-agent: %v\n", err)
			os.Exit(1)
		}
		return
	}

	stop := make(chan struct{})
	sigCh := make(chan os.Signal, 1)
	signal.Notify(sigCh, os.Interrupt, syscall.SIGTERM)
	go func() {
		<-sigCh
		close(stop)
	}()

	runner.RunLoop(stop)
}
