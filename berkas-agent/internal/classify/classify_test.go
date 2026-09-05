package classify

import (
	"strings"
	"testing"
)

func TestClassifySTR(t *testing.T) {
	r := Classify("SURAT TANDA REGISTRASI TENAGA KESEHATAN STR nomor 123")
	if r.SuggestedKode != "STR" {
		t.Fatalf("kode=%q want STR", r.SuggestedKode)
	}
	if r.Confidence < 0.5 {
		t.Fatalf("confidence too low: %v", r.Confidence)
	}
}

func TestClassifySIP(t *testing.T) {
	r := Classify("SURAT IZIN PRAKTIK SIP dokter umum")
	if r.SuggestedKode != "SIP" {
		t.Fatalf("kode=%q want SIP", r.SuggestedKode)
	}
}

func TestClassifyIjazah(t *testing.T) {
	r := Classify("IJAZAH DAN TRANSKRIP NILAI wisuda sarjana")
	if r.SuggestedKode != "IJAZAH" {
		t.Fatalf("kode=%q want IJAZAH", r.SuggestedKode)
	}
}

func TestClassifyUnknown(t *testing.T) {
	r := Classify("lorem ipsum dokumen acak tanpa kata kunci")
	if r.SuggestedKode != "" {
		t.Fatalf("expected empty kode, got %q", r.SuggestedKode)
	}
	if r.Confidence != 0 {
		t.Fatalf("confidence=%v", r.Confidence)
	}
}

func TestClassifyDoesNotMarkOfficialDocAsKTP(t *testing.T) {
	r := Classify("REPUBLIK INDONESIA SURAT TANDA REGISTRASI TENAGA KESEHATAN NIK 1234567890")
	if r.SuggestedKode != "STR" {
		t.Fatalf("kode=%q want STR (not KTP false positive)", r.SuggestedKode)
	}
}

func TestClassifyKTP(t *testing.T) {
	r := Classify("KARTU TANDA PENDUDUK Republik Indonesia NIK berlaku hingga 2030")
	if r.SuggestedKode != "KTP" {
		t.Fatalf("kode=%q want KTP", r.SuggestedKode)
	}
}

func TestExcerptTruncated(t *testing.T) {
	long := strings.Repeat("kata ", 200)
	r := Classify(long + " STR ")
	if len(r.OCRExcerpt) > 500 {
		t.Fatalf("excerpt too long: %d", len(r.OCRExcerpt))
	}
	if r.SuggestedKode != "STR" {
		t.Fatalf("kode=%q", r.SuggestedKode)
	}
}
