package sslcheck

import (
	"testing"
	"time"
)

func TestSSLAuditStructure(t *testing.T) {
	// Audit a known public HTTPS endpoint
	res := Audit("1.1.1.1", 443, 3*time.Second)

	if res.Domain != "1.1.1.1" {
		t.Errorf("expected domain 1.1.1.1, got %s", res.Domain)
	}

	if res.Status == "error" && res.ErrorMessage == "" {
		t.Errorf("error status must have an error message")
	}
}

func TestSSLAuditInvalidDomain(t *testing.T) {
	// Non-existent or invalid host
	res := Audit("invalid-domain-that-does-not-exist.local", 443, 1*time.Second)

	if res.Success {
		t.Errorf("expected failure for invalid domain, got success true")
	}

	if res.Status != "error" {
		t.Errorf("expected status 'error', got %s", res.Status)
	}
}
