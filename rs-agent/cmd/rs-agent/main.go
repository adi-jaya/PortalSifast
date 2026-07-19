package main

import (
	"flag"
	"fmt"
	"os"
	"strings"

	"github.com/portalsifast/rs-agent/internal/serviceapp"
)

// Set via: go build -ldflags "-X main.version=0.2.0"
var version = "0.2.1-dev"

func main() {
	configPath := flag.String("config", "configs/config.json", "path to config.json")
	serviceAction := flag.String("service", "", "service control: install|start|stop|restart|status|uninstall")
	flag.Parse()

	action := strings.TrimSpace(strings.ToLower(*serviceAction))
	if action != "" {
		if err := runServiceControl(*configPath, action); err != nil {
			fmt.Fprintf(os.Stderr, "service %s: %v\n", action, err)
			os.Exit(1)
		}
		return
	}

	if err := serviceapp.RunConsole(*configPath, version); err != nil {
		fmt.Fprintf(os.Stderr, "rs-agent: %v\n", err)
		os.Exit(1)
	}
}

func runServiceControl(configPath, action string) error {
	prg := &serviceapp.Program{
		ConfigPath:   configPath,
		AgentVersion: version,
	}
	s, err := serviceapp.NewService(prg)
	if err != nil {
		return err
	}

	switch action {
	case "install", "uninstall", "start", "stop", "restart":
		return serviceapp.Control(s, action)
	case "status":
		status, err := s.Status()
		if err != nil {
			return err
		}
		fmt.Printf("service=%s status=%v\n", serviceapp.ServiceName, status)
		return nil
	default:
		return fmt.Errorf("unknown action %q (use install|start|stop|restart|status|uninstall)", action)
	}
}
