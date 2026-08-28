package provider

import (
	"context"
	"fmt"
	"strings"
	"time"
)

type RecordPayload struct {
	ID    string `json:"id,omitempty"`
	Type  string `json:"type"`  // A, CNAME, TXT, etc.
	Name  string `json:"name"`  // e.g. "sub" or "@"
	Value string `json:"value"` // e.g. "ingress.mysaas.com" or "1.2.3.4"
	TTL   int    `json:"ttl,omitempty"`
}

type ProvisionRequest struct {
	Provider   string          `json:"provider"` // "cloudflare", "godaddy", "mock"
	AuthToken  string          `json:"auth_token,omitempty"`
	AuthSecret string          `json:"auth_secret,omitempty"` // For GoDaddy Secret
	ZoneID     string          `json:"zone_id,omitempty"`     // For Cloudflare Zone ID (optional, auto-discovered if empty)
	Zone       string          `json:"zone"`                  // e.g. "example.com"
	Records    []RecordPayload `json:"records"`
}

type ProvisionResponse struct {
	Success        bool            `json:"success"`
	Status         string          `json:"status"` // "provisioned", "error"
	Provider       string          `json:"provider"`
	Zone           string          `json:"zone"`
	CreatedRecords []RecordPayload `json:"created_records,omitempty"`
	ErrorMessage   string          `json:"error_message,omitempty"`
	ProcessedAt    string          `json:"processed_at"`
}

type RemoveRequest struct {
	Provider   string          `json:"provider"`
	AuthToken  string          `json:"auth_token,omitempty"`
	AuthSecret string          `json:"auth_secret,omitempty"`
	ZoneID     string          `json:"zone_id,omitempty"`
	Zone       string          `json:"zone"`
	Records    []RecordPayload `json:"records"`
}

type RemoveResponse struct {
	Success        bool            `json:"success"`
	Status         string          `json:"status"` // "removed", "error"
	Provider       string          `json:"provider"`
	Zone           string          `json:"zone"`
	DeletedRecords []RecordPayload `json:"deleted_records,omitempty"`
	ErrorMessage   string          `json:"error_message,omitempty"`
	ProcessedAt    string          `json:"processed_at"`
}

// Provision creates or updates DNS records in the designated provider zone
func Provision(ctx context.Context, req ProvisionRequest) ProvisionResponse {
	now := time.Now().UTC().Format(time.RFC3339)
	provName := strings.ToLower(strings.TrimSpace(req.Provider))
	zone := strings.TrimSuffix(strings.TrimSpace(req.Zone), ".")

	if zone == "" {
		return ProvisionResponse{
			Success:      false,
			Status:       "error",
			Provider:     provName,
			ErrorMessage: "Zone domain is required",
			ProcessedAt:  now,
		}
	}

	var created []RecordPayload

	switch provName {
	case "mock":
		for _, r := range req.Records {
			r.ID = fmt.Sprintf("mock_%d", time.Now().UnixNano())
			created = append(created, r)
		}
	case "cloudflare":
		cf := NewCloudflareClient(req.AuthToken)
		zoneID := strings.TrimSpace(req.ZoneID)
		if zoneID == "" {
			var err error
			zoneID, err = cf.GetZoneID(ctx, zone)
			if err != nil {
				return ProvisionResponse{
					Success:      false,
					Status:       "error",
					Provider:     provName,
					Zone:         zone,
					ErrorMessage: fmt.Sprintf("Failed to resolve Cloudflare Zone ID: %v", err),
					ProcessedAt:  now,
				}
			}
		}

		for _, r := range req.Records {
			rec, err := cf.CreateRecord(ctx, zoneID, r)
			if err != nil {
				return ProvisionResponse{
					Success:      false,
					Status:       "error",
					Provider:     provName,
					Zone:         zone,
					ErrorMessage: fmt.Sprintf("Cloudflare record creation failed: %v", err),
					ProcessedAt:  now,
				}
			}
			created = append(created, rec)
		}

	case "godaddy":
		gd := NewGoDaddyClient(req.AuthToken, req.AuthSecret)
		recs, err := gd.AddRecords(ctx, zone, req.Records)
		if err != nil {
			return ProvisionResponse{
				Success:      false,
				Status:       "error",
				Provider:     provName,
				Zone:         zone,
				ErrorMessage: fmt.Sprintf("GoDaddy record provisioning failed: %v", err),
				ProcessedAt:  now,
			}
		}
		created = recs

	default:
		return ProvisionResponse{
			Success:      false,
			Status:       "error",
			Provider:     provName,
			Zone:         zone,
			ErrorMessage: fmt.Sprintf("Unsupported DNS provider '%s'", provName),
			ProcessedAt:  now,
		}
	}

	return ProvisionResponse{
		Success:        true,
		Status:         "provisioned",
		Provider:       provName,
		Zone:           zone,
		CreatedRecords: created,
		ProcessedAt:    now,
	}
}

// Remove deletes DNS records from the designated provider zone
func Remove(ctx context.Context, req RemoveRequest) RemoveResponse {
	now := time.Now().UTC().Format(time.RFC3339)
	provName := strings.ToLower(strings.TrimSpace(req.Provider))
	zone := strings.TrimSuffix(strings.TrimSpace(req.Zone), ".")

	switch provName {
	case "mock":
		// Mock remove succeeds

	case "cloudflare":
		cf := NewCloudflareClient(req.AuthToken)
		zoneID := strings.TrimSpace(req.ZoneID)
		if zoneID == "" {
			var err error
			zoneID, err = cf.GetZoneID(ctx, zone)
			if err != nil {
				return RemoveResponse{
					Success:      false,
					Status:       "error",
					Provider:     provName,
					Zone:         zone,
					ErrorMessage: err.Error(),
					ProcessedAt:  now,
				}
			}
		}

		for _, r := range req.Records {
			if r.ID != "" {
				_ = cf.DeleteRecord(ctx, zoneID, r.ID)
			}
		}

	case "godaddy":
		gd := NewGoDaddyClient(req.AuthToken, req.AuthSecret)
		for _, r := range req.Records {
			_ = gd.DeleteRecord(ctx, zone, r.Type, r.Name)
		}

	default:
		return RemoveResponse{
			Success:      false,
			Status:       "error",
			Provider:     provName,
			Zone:         zone,
			ErrorMessage: fmt.Sprintf("Unsupported DNS provider '%s'", provName),
			ProcessedAt:  now,
		}
	}

	return RemoveResponse{
		Success:        true,
		Status:         "removed",
		Provider:       provName,
		Zone:           zone,
		DeletedRecords: req.Records,
		ProcessedAt:    now,
	}
}
