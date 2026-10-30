package watch

import (
	"os"
	"path/filepath"
	"strconv"
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

func TestListReadyFilesSettleOnceForMany(t *testing.T) {
	dir := t.TempDir()
	for i := 0; i < 5; i++ {
		name := filepath.Join(dir, "scan"+strconv.Itoa(i)+".jpg")
		if err := os.WriteFile(name, []byte("hello"), 0o644); err != nil {
			t.Fatal(err)
		}
	}
	start := time.Now()
	files, err := ListReadyFiles(dir, 50*time.Millisecond)
	elapsed := time.Since(start)
	if err != nil {
		t.Fatal(err)
	}
	if len(files) != 5 {
		t.Fatalf("got %d files", len(files))
	}
	// One settle window (~50ms), not 5×50ms.
	if elapsed > 200*time.Millisecond {
		t.Fatalf("settle too slow: %s (likely per-file sleep)", elapsed)
	}
}

func TestMoveUniqueCopyFallbackSameDir(t *testing.T) {
	srcDir := t.TempDir()
	destDir := t.TempDir()
	src := filepath.Join(srcDir, "a.jpg")
	if err := os.WriteFile(src, []byte("payload"), 0o644); err != nil {
		t.Fatal(err)
	}
	dest, err := MoveUnique(src, destDir)
	if err != nil {
		t.Fatal(err)
	}
	if _, err := os.Stat(src); !os.IsNotExist(err) {
		t.Fatalf("source should be gone, err=%v", err)
	}
	data, err := os.ReadFile(dest)
	if err != nil {
		t.Fatal(err)
	}
	if string(data) != "payload" {
		t.Fatalf("dest content=%q", data)
	}
}
