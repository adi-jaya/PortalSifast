package classify

import (
	"strings"
	"unicode"
)

// Result is the keyword-classification output for one OCR text blob.
type Result struct {
	SuggestedKode  string  `json:"suggested_kode"`
	SuggestedLabel string  `json:"suggested_label"`
	Confidence     float64 `json:"confidence"`
	OCRExcerpt     string  `json:"ocr_excerpt"`
	OCRFailed      bool    `json:"ocr_failed"`
}

type rule struct {
	Kode     string
	Label    string
	Keywords []string
}

// Default rules map OCR keywords to local suggestion codes.
// Portal/HR may remap to master_berkas_pegawai.kode on confirm.
var defaultRules = []rule{
	{Kode: "STR", Label: "Surat Tanda Registrasi", Keywords: []string{
		"surat tanda registrasi", "tanda registrasi tenaga", " str ",
	}},
	{Kode: "SIP", Label: "Surat Izin Praktik", Keywords: []string{
		"surat izin praktik", "izin praktik", " sip ",
	}},
	{Kode: "IJAZAH", Label: "Ijazah", Keywords: []string{
		"ijazah", "transkrip nilai", "wisuda",
	}},
	{Kode: "KTP", Label: "KTP", Keywords: []string{
		"kartu tanda penduduk", "nik berlaku hingga", "gol darah",
	}},
	{Kode: "NPWP", Label: "NPWP", Keywords: []string{
		"npwp", "nomor pokok wajib pajak", "direktorat jenderal pajak",
	}},
	{Kode: "BPJS", Label: "Kartu BPJS", Keywords: []string{
		"bpjs kesehatan", "badan penyelenggara jaminan sosial", "bpjs ketenagakerjaan",
	}},
}

// Classify returns the best matching document type for OCR text.
func Classify(ocrText string) Result {
	excerpt := truncate(strings.TrimSpace(ocrText), 400)
	norm := normalize(ocrText)
	if norm == "" {
		return Result{OCRExcerpt: excerpt}
	}

	bestKode := ""
	bestLabel := ""
	bestHits := 0
	bestWeight := 0

	for _, r := range defaultRules {
		hits := 0
		weight := 0
		for _, kw := range r.Keywords {
			kwNorm := normalize(kw)
			if kwNorm == "" {
				continue
			}
			if strings.Contains(norm, kwNorm) {
				hits++
				// Longer phrases weigh more than short tokens.
				weight += len([]rune(kwNorm))
			}
		}
		if hits == 0 {
			continue
		}
		if weight > bestWeight || (weight == bestWeight && hits > bestHits) {
			bestWeight = weight
			bestHits = hits
			bestKode = r.Kode
			bestLabel = r.Label
		}
	}

	if bestKode == "" {
		return Result{OCRExcerpt: excerpt}
	}

	conf := float64(bestWeight) / 40.0
	if conf > 1 {
		conf = 1
	}
	if conf < 0.35 {
		conf = 0.35
	}

	return Result{
		SuggestedKode:  bestKode,
		SuggestedLabel: bestLabel,
		Confidence:     conf,
		OCRExcerpt:     excerpt,
	}
}

func normalize(s string) string {
	s = strings.ToLower(s)
	var b strings.Builder
	b.Grow(len(s) + 2)
	b.WriteByte(' ')
	prevSpace := true
	for _, r := range s {
		if unicode.IsLetter(r) || unicode.IsDigit(r) {
			b.WriteRune(r)
			prevSpace = false
			continue
		}
		if !prevSpace {
			b.WriteByte(' ')
			prevSpace = true
		}
	}
	if !prevSpace {
		b.WriteByte(' ')
	}
	return b.String()
}

func truncate(s string, max int) string {
	runes := []rune(s)
	if len(runes) <= max {
		return s
	}
	return string(runes[:max]) + "…"
}
