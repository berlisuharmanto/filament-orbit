package dnscheck

import (
	"context"
	"testing"
	"time"
)

func TestDnsCheckStructure(t *testing.T) {
	ctx := context.Background()

	// Test checking a known public domain (e.g. one.one.one.one)
	expected := []ExpectedRecord{
		{Type: "A", Target: "1.1.1.1"},
	}

	res := Check(ctx, "one.one.one.one", expected, []string{"1.1.1.1:53", "8.8.8.8:53"}, 2*time.Second)

	if res.Domain != "one.one.one.one" {
		t.Errorf("expected domain one.one.one.one, got %s", res.Domain)
	}

	if len(res.Records) == 0 {
		t.Fatalf("expected records, got 0")
	}

	if !res.Records[0].Matched {
		t.Logf("one.one.one.one A record check returned resolved: %v (note: may depend on network)", res.Records[0].Resolved)
	}
}

func TestExpectedRecordMismatch(t *testing.T) {
	ctx := context.Background()

	expected := []ExpectedRecord{
		{Type: "A", Target: "192.0.2.1"}, // documentation IP, will not match
	}

	res := Check(ctx, "google.com", expected, []string{"8.8.8.8:53"}, 2*time.Second)

	if res.Success {
		t.Errorf("expected mismatch, but got success true")
	}

	if res.Status == "verified" {
		t.Errorf("expected status not verified, got %s", res.Status)
	}
}
