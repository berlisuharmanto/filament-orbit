package sslcheck

import (
	"crypto/tls"
	"crypto/x509"
	"fmt"
	"net"
	"strings"
	"time"
)

type AuditResponse struct {
	Success       bool     `json:"success"`
	Status        string   `json:"status"` // "valid", "expiring_soon", "expired", "mismatch", "error"
	Domain        string   `json:"domain"`
	Issuer        string   `json:"issuer,omitempty"`
	Subject       string   `json:"subject,omitempty"`
	SANs          []string `json:"sans,omitempty"`
	ValidFrom     string   `json:"valid_from,omitempty"`
	ValidTo       string   `json:"valid_to,omitempty"`
	DaysRemaining int      `json:"days_remaining"`
	IsExpired     bool     `json:"is_expired"`
	ErrorMessage  string   `json:"error_message,omitempty"`
	CheckedAt     string   `json:"checked_at"`
}

// Audit inspects the SSL/TLS certificate for a domain
func Audit(domain string, port int, timeout time.Duration) AuditResponse {
	domain = strings.TrimSpace(domain)
	if port <= 0 {
		port = 443
	}
	if timeout <= 0 {
		timeout = 5 * time.Second
	}

	target := fmt.Sprintf("%s:%d", domain, port)
	dialer := &net.Dialer{
		Timeout: timeout,
	}

	conf := &tls.Config{
		ServerName:         domain,
		InsecureSkipVerify: false, // Verify trust chain
	}

	conn, err := tls.DialWithDialer(dialer, "tcp", target, conf)
	if err != nil {
		// Attempt fallback with InsecureSkipVerify to check expired or self-signed details
		fallbackConf := &tls.Config{
			ServerName:         domain,
			InsecureSkipVerify: true,
		}
		fallbackConn, fallbackErr := tls.DialWithDialer(dialer, "tcp", target, fallbackConf)
		if fallbackErr != nil {
			return AuditResponse{
				Success:      false,
				Status:       "error",
				Domain:       domain,
				ErrorMessage: err.Error(),
				CheckedAt:    time.Now().UTC().Format(time.RFC3339),
			}
		}
		defer fallbackConn.Close()
		return parseCertState(domain, fallbackConn.ConnectionState().PeerCertificates, err.Error())
	}
	defer conn.Close()

	state := conn.ConnectionState()
	return parseCertState(domain, state.PeerCertificates, "")
}

func parseCertState(domain string, certs []*x509.Certificate, validationErr string) AuditResponse {
	now := time.Now().UTC()
	if len(certs) == 0 {
		return AuditResponse{
			Success:      false,
			Status:       "error",
			Domain:       domain,
			ErrorMessage: "No certificates returned by server",
			CheckedAt:    now.Format(time.RFC3339),
		}
	}

	leaf := certs[0]
	daysRemaining := int(leaf.NotAfter.Sub(now).Hours() / 24)
	isExpired := now.After(leaf.NotAfter)

	// Check SAN matching
	matchesDomain := leaf.VerifyHostname(domain) == nil

	status := "valid"
	success := true
	errMsg := validationErr

	if isExpired {
		status = "expired"
		success = false
		errMsg = "Certificate has expired"
	} else if !matchesDomain {
		status = "mismatch"
		success = false
		errMsg = fmt.Sprintf("Certificate does not match domain name %s", domain)
	} else if validationErr != "" {
		status = "invalid"
		success = false
	} else if daysRemaining < 14 {
		status = "expiring_soon"
	}

	return AuditResponse{
		Success:       success,
		Status:        status,
		Domain:        domain,
		Issuer:        leaf.Issuer.CommonName,
		Subject:       leaf.Subject.CommonName,
		SANs:          leaf.DNSNames,
		ValidFrom:     leaf.NotBefore.Format(time.RFC3339),
		ValidTo:       leaf.NotAfter.Format(time.RFC3339),
		DaysRemaining: daysRemaining,
		IsExpired:     isExpired,
		ErrorMessage:  errMsg,
		CheckedAt:     now.Format(time.RFC3339),
	}
}
