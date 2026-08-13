//go:build windows

package commands

import (
	"os"
	"testing"
)

func TestListWindowsLiveSession(t *testing.T) {
	if os.Getenv("RUN_LIVE_WINDOWS") == "" {
		t.Skip("set RUN_LIVE_WINDOWS=1 to run")
	}

	wins, err := listWindows()
	if err != nil {
		t.Fatalf("listWindows: %v", err)
	}
	if len(wins) == 0 {
		t.Fatal("expected at least one visible window in active user session")
	}
	t.Logf("found %d windows, first=%+v", len(wins), wins[0])
}
