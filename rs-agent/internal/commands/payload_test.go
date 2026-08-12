package commands

import "testing"

func TestPayloadPID(t *testing.T) {
	t.Parallel()

	pid, ok := PayloadPID(map[string]any{"pid": float64(4242)})
	if !ok || pid != 4242 {
		t.Fatalf("float pid=%d ok=%v", pid, ok)
	}

	pid, ok = PayloadPID(map[string]any{"pid": "100"})
	if !ok || pid != 100 {
		t.Fatalf("string pid=%d ok=%v", pid, ok)
	}

	if _, ok := PayloadPID(map[string]any{"pid": 0}); ok {
		t.Fatal("pid 0 should be invalid")
	}

	if _, ok := PayloadPID(map[string]any{}); ok {
		t.Fatal("missing pid should be invalid")
	}
}
