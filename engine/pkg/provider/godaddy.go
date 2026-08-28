package provider

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"strings"
	"time"
)

type GoDaddyClient struct {
	APIKey     string
	APISecret  string
	BaseURL    string
	HTTPClient *http.Client
}

func NewGoDaddyClient(key, secret string) *GoDaddyClient {
	return &GoDaddyClient{
		APIKey:    strings.TrimSpace(key),
		APISecret: strings.TrimSpace(secret),
		BaseURL:   "https://api.godaddy.com/v1",
		HTTPClient: &http.Client{
			Timeout: 10 * time.Second,
		},
	}
}

func (g *GoDaddyClient) authHeader() string {
	if g.APISecret == "" {
		return "sso-key " + g.APIKey
	}
	return fmt.Sprintf("sso-key %s:%s", g.APIKey, g.APISecret)
}

func (g *GoDaddyClient) AddRecords(ctx context.Context, domain string, records []RecordPayload) ([]RecordPayload, error) {
	url := fmt.Sprintf("%s/domains/%s/records", g.BaseURL, domain)

	var payload []map[string]interface{}
	for _, r := range records {
		ttl := r.TTL
		if ttl <= 0 {
			ttl = 600
		}
		name := r.Name
		if name == "" {
			name = "@"
		}
		payload = append(payload, map[string]interface{}{
			"type": strings.ToUpper(r.Type),
			"name": name,
			"data": r.Value,
			"ttl":  ttl,
		})
	}

	bodyBytes, _ := json.Marshal(payload)
	req, err := http.NewRequestWithContext(ctx, http.MethodPatch, url, bytes.NewReader(bodyBytes))
	if err != nil {
		return nil, err
	}
	req.Header.Set("Authorization", g.authHeader())
	req.Header.Set("Content-Type", "application/json")

	resp, err := g.HTTPClient.Do(req)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()

	if resp.StatusCode < 200 || resp.StatusCode >= 300 {
		body, _ := io.ReadAll(resp.Body)
		return nil, fmt.Errorf("godaddy error (status %d): %s", resp.StatusCode, string(body))
	}

	return records, nil
}

func (g *GoDaddyClient) DeleteRecord(ctx context.Context, domain, recType, recName string) error {
	if recName == "" {
		recName = "@"
	}
	url := fmt.Sprintf("%s/domains/%s/records/%s/%s", g.BaseURL, domain, strings.ToUpper(recType), recName)
	req, err := http.NewRequestWithContext(ctx, http.MethodDelete, url, nil)
	if err != nil {
		return err
	}
	req.Header.Set("Authorization", g.authHeader())
	req.Header.Set("Content-Type", "application/json")

	resp, err := g.HTTPClient.Do(req)
	if err != nil {
		return err
	}
	defer resp.Body.Close()

	if resp.StatusCode < 200 || resp.StatusCode >= 300 {
		body, _ := io.ReadAll(resp.Body)
		return fmt.Errorf("godaddy error (status %d): %s", resp.StatusCode, string(body))
	}

	return nil
}
