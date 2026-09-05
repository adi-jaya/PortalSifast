package api

import (
	"bytes"
	"encoding/json"
	"fmt"
	"io"
	"mime/multipart"
	"net/http"
	"os"
	"path/filepath"
	"strconv"
	"time"

	"github.com/portalsifast/berkas-agent/internal/classify"
)

type Client struct {
	BaseURL    string
	Token      string
	AgentLabel string
	HTTP       *http.Client
}

type InboxResponse struct {
	Success bool   `json:"success"`
	ID      uint   `json:"id"`
	Message string `json:"message"`
}

func New(baseURL, token, agentLabel string) *Client {
	return &Client{
		BaseURL:    baseURL,
		Token:      token,
		AgentLabel: agentLabel,
		HTTP:       &http.Client{Timeout: 120 * time.Second},
	}
}

// PostInbox uploads a scanned file + classification to Portal.
func (c *Client) PostInbox(filePath string, result classify.Result) (*InboxResponse, error) {
	f, err := os.Open(filePath)
	if err != nil {
		return nil, err
	}
	defer f.Close()

	var body bytes.Buffer
	w := multipart.NewWriter(&body)

	part, err := w.CreateFormFile("dokumen", filepath.Base(filePath))
	if err != nil {
		return nil, err
	}
	if _, err := io.Copy(part, f); err != nil {
		return nil, err
	}

	_ = w.WriteField("suggested_kode", result.SuggestedKode)
	_ = w.WriteField("suggested_label", result.SuggestedLabel)
	_ = w.WriteField("confidence", strconv.FormatFloat(result.Confidence, 'f', 4, 64))
	_ = w.WriteField("ocr_excerpt", result.OCRExcerpt)
	_ = w.WriteField("agent_label", c.AgentLabel)
	_ = w.WriteField("original_filename", filepath.Base(filePath))
	if err := w.Close(); err != nil {
		return nil, err
	}

	url := c.BaseURL + "/api/berkas-scan/inbox"
	req, err := http.NewRequest(http.MethodPost, url, &body)
	if err != nil {
		return nil, err
	}
	req.Header.Set("Content-Type", w.FormDataContentType())
	req.Header.Set("Authorization", "Bearer "+c.Token)
	req.Header.Set("X-Berkas-Agent-Token", c.Token)

	res, err := c.HTTP.Do(req)
	if err != nil {
		return nil, err
	}
	defer res.Body.Close()

	raw, _ := io.ReadAll(res.Body)
	var parsed InboxResponse
	_ = json.Unmarshal(raw, &parsed)

	if res.StatusCode < 200 || res.StatusCode >= 300 || !parsed.Success {
		msg := parsed.Message
		if msg == "" {
			msg = string(raw)
		}
		if msg == "" {
			msg = res.Status
		}
		return nil, fmt.Errorf("portal inbox HTTP %d: %s", res.StatusCode, msg)
	}
	return &parsed, nil
}
