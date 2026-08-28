package provider

import (
	"context"
	"testing"
)

func TestMockProviderProvisionAndRemove(t *testing.T) {
	ctx := context.Background()

	req := ProvisionRequest{
		Provider: "mock",
		Zone:     "example.com",
		Records: []RecordPayload{
			{Type: "CNAME", Name: "shop", Value: "ingress.mysaas.com", TTL: 300},
		},
	}

	res := Provision(ctx, req)
	if !res.Success {
		t.Fatalf("expected success, got error: %s", res.ErrorMessage)
	}

	if res.Status != "provisioned" {
		t.Errorf("expected status 'provisioned', got %s", res.Status)
	}

	if len(res.CreatedRecords) != 1 {
		t.Fatalf("expected 1 created record, got %d", len(res.CreatedRecords))
	}

	if res.CreatedRecords[0].Name != "shop" {
		t.Errorf("expected record name 'shop', got %s", res.CreatedRecords[0].Name)
	}

	// Test remove
	removeReq := RemoveRequest{
		Provider: "mock",
		Zone:     "example.com",
		Records:  res.CreatedRecords,
	}

	remRes := Remove(ctx, removeReq)
	if !remRes.Success {
		t.Fatalf("expected remove success, got error: %s", remRes.ErrorMessage)
	}

	if remRes.Status != "removed" {
		t.Errorf("expected status 'removed', got %s", remRes.Status)
	}
}

func TestUnsupportedProvider(t *testing.T) {
	ctx := context.Background()

	req := ProvisionRequest{
		Provider: "nonexistent-provider",
		Zone:     "example.com",
	}

	res := Provision(ctx, req)
	if res.Success {
		t.Errorf("expected failure for unsupported provider, got success true")
	}

	if res.Status != "error" {
		t.Errorf("expected status 'error', got %s", res.Status)
	}
}
