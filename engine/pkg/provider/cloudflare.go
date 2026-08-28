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

type CloudflareClient struct {
	APIToken   string
	BaseURL    string
	HTTPClient *http.Client
}

func NewCloudflareClient(token string) *CloudflareClient {
	return &CloudflareClient{
		APIToken: strings.TrimSpace(token),
		BaseURL:  "https://api.cloudflare.com/client/v4",
		HTTPClient: &http.Client{
			Timeout: 10 * time.Second,
		},
	}
}

type cfZoneResponse struct {
	Success bool `json:"success"`
	Result  []struct {
		ID   string `json:"id"`
		Name string `json:"name"`
	} `json:"result"`
	Errors []struct {
		Code    int    `json:"code"`
		Message string `json:"message"`
	} `json:"errors"`
}

type cfRecordResponse struct {
	Success bool `json:"success"`
	Result  struct {
		ID      string `json:"id"`
		Type    string `json:"type"`
		Name    string `json:"name"`
		Content string `json:"content"`
		TTL     int    `json:"ttl"`
	} `json:"result"`
	Errors []struct {
		Code    int    `json:"code"`
		Message string `json:"message"`
	} `json:"errors"`
}

func (c *CloudflareClient) GetZoneID(ctx context.Context, zoneName string) (string, error) {
	url := fmt.Sprintf("%s/zones?name=%s", c.BaseURL, zoneName)
	req, err := http.NewRequestWithContext(ctx, http.MethodGet, url, nil)
	if err != nil {
		return "", err
	}
	req.Header.Set("Authorization", "Bearer "+c.APIToken)
	req.Header.Set("Content-Type", "application/json")

	resp, err := c.HTTPClient.Do(req)
	if err != nil {
		return "", err
	}
	defer resp.Body.Close()

	body, _ := io.ReadAll(resp.Body)
	var zResp cfZoneResponse
	if err := json.Unmarshal(body, &zResp); err != nil {
		return "", err
	}

	if !zResp.Success || len(zResp.Result) == 0 {
		errMsg := "Zone not found"
		if len(zResp.Errors) > 0 {
			errMsg = zResp.Errors[0].Message
		}
		return "", fmt.Errorf("cloudflare error: %s", errMsg)
	}

	return zResp.Result[0].ID, nil
}

func (c *CloudflareClient) CreateRecord(ctx context.Context, zoneID string, r RecordPayload) (RecordPayload, error) {
	url := fmt.Sprintf("%s/zones/%s/dns_records", c.BaseURL, zoneID)

	ttl := r.TTL
	if ttl <= 0 {
		ttl = 1 // Cloudflare auto TTL
	}

	payload := map[string]interface{}{
		"type":    strings.ToUpper(r.Type),
		"name":    r.Name,
		"content": r.Value,
		"ttl":     ttl,
	}

	bodyBytes, _ := json.Marshal(payload)
	req, err := http.NewRequestWithContext(ctx, http.MethodPost, url, bytes.NewReader(bodyBytes))
	if err != nil {
		return r, err
	}
	req.Header.Set("Authorization", "Bearer "+c.APIToken)
	req.Header.Set("Content-Type", "application/json")

	resp, err := c.HTTPClient.Do(req)
	if err != nil {
		return r, err
	}
	defer resp.Body.Close()

	respBody, _ := io.ReadAll(resp.Body)
	var recResp cfRecordResponse
	if err := json.Unmarshal(respBody, &recResp); err != nil {
		return r, err
	}

	if !recResp.Success {
		errMsg := "Failed to create DNS record"
		if len(recResp.Errors) > 0 {
			errMsg = recResp.Errors[0].Message
		}
		return r, fmt.Errorf("cloudflare error: %s", errMsg)
	}

	return RecordPayload{
		ID:    recResp.Result.ID,
		Type:  recResp.Result.Type,
		Name:  recResp.Result.Name,
		Value: recResp.Result.Content,
		TTL:   recResp.Result.TTL,
	}, nil
}

func (c *CloudflareClient) DeleteRecord(ctx context.Context, zoneID, recordID string) error {
	url := fmt.Sprintf("%s/zones/%s/dns_records/%s", c.BaseURL, zoneID, recordID)
	req, err := http.NewRequestWithContext(ctx, http.MethodDelete, url, nil)
	if err != nil {
		return err
	}
	req.Header.Set("Authorization", "Bearer "+c.APIToken)
	req.Header.Set("Content-Type", "application/json")

	resp, err := c.HTTPClient.Do(req)
	if err != nil {
		return err
	}
	defer resp.Body.Close()

	if resp.StatusCode < 200 || resp.StatusCode >= 300 {
		body, _ := io.ReadAll(resp.Body)
		return fmt.Errorf("cloudflare delete error: %s", string(body))
	}

	return nil
}
