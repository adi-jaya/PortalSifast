package api

import (
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"os"
	"path/filepath"
	"testing"

	"github.com/portalsifast/berkas-agent/internal/classify"
)

func TestPostInboxSuccess(t *testing.T) {
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.URL.Path != "/api/berkas-scan/inbox" {
			t.Fatalf("path=%s", r.URL.Path)
		}
		if r.Header.Get("X-Berkas-Agent-Token") != "tok" {
			t.Fatalf("missing token")
		}
		if err := r.ParseMultipartForm(10 << 20); err != nil {
			t.Fatal(err)
		}
		if r.FormValue("suggested_kode") != "STR" {
			t.Fatalf("kode=%q", r.FormValue("suggested_kode"))
		}
		_ = json.NewEncoder(w).Encode(map[string]any{"success": true, "id": 7})
	}))
	defer srv.Close()

	dir := t.TempDir()
	path := filepath.Join(dir, "a.jpg")
	if err := os.WriteFile(path, []byte("img"), 0o644); err != nil {
		t.Fatal(err)
	}

	c := New(srv.URL, "tok", "hr-1")
	res, err := c.PostInbox(path, classify.Result{
		SuggestedKode:  "STR",
		SuggestedLabel: "Surat Tanda Registrasi",
		Confidence:     0.8,
		OCRExcerpt:     "STR contoh",
	})
	if err != nil {
		t.Fatal(err)
	}
	if res.ID != 7 {
		t.Fatalf("id=%d", res.ID)
	}
}
