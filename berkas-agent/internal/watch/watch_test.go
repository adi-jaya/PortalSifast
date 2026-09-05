package watch

import (
	"os"
	"path/filepath"
	"testing"
	"time"
)

func TestListReadyFilesStable(t *testing.T) {
	dir := t.TempDir()
	path := filepath.Join(dir, "scan.jpg")
	if err := os.WriteFile(path, []byte("hello"), 0o644); err != nil {
		t.Fatal(err)
	}
	files, err := ListReadyFiles(dir, 20*time.Millisecond)
	if err != nil {
		t.Fatal(err)
	}
	if len(files) != 1 {
		t.Fatalf("got %d files", len(files))
	}
}

func TestListReadyFilesSkipsUnsupported(t *testing.T) {
	dir := t.TempDir()
	if err := os.WriteFile(filepath.Join(dir, "note.txt"), []byte("x"), 0o644); err != nil {
		t.Fatal(err)
	}
	files, err := ListReadyFiles(dir, 10*time.Millisecond)
	if err != nil {
		t.Fatal(err)
	}
	if len(files) != 0 {
		t.Fatalf("expected 0, got %v", files)
	}
}
