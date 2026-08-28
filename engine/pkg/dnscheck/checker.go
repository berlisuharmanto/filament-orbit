package dnscheck

import (
	"context"
	"fmt"
	"net"
	"strings"
	"sync"
	"time"

	"github.com/miekg/dns"
)

type ExpectedRecord struct {
	Type   string `json:"type"`   // A, AAAA, CNAME, TXT
	Target string `json:"target"` // expected IP or target hostname
}

type RecordResult struct {
	Type     string   `json:"type"`
	Target   string   `json:"target"`
	Matched  bool     `json:"matched"`
	Resolved []string `json:"resolved"`
	Message  string   `json:"message,omitempty"`
}

type ResolverResult struct {
	Resolver string   `json:"resolver"`
	Matched  bool     `json:"matched"`
	Resolved []string `json:"resolved"`
	Error    string   `json:"error,omitempty"`
}

type PropagationResult struct {
	Percentage       float64          `json:"percentage"`
	ResolversChecked int              `json:"resolvers_checked"`
	ResolversMatched int              `json:"resolvers_matched"`
	Details          []ResolverResult `json:"details"`
}

type CheckResponse struct {
	Success     bool              `json:"success"`
	Status      string            `json:"status"` // "verified", "pending", "failed"
	Domain      string            `json:"domain"`
	Records     []RecordResult    `json:"records"`
	Propagation PropagationResult `json:"propagation"`
	CheckedAt   string            `json:"checked_at"`
}

// Default public resolvers used for propagation evaluation
var DefaultResolvers = []string{
	"1.1.1.1:53",         // Cloudflare
	"8.8.8.8:53",         // Google
	"9.9.9.9:53",         // Quad9
	"208.67.222.222:53",  // OpenDNS
}

// Check evaluates a domain's DNS records against expected targets
func Check(ctx context.Context, domain string, expected []ExpectedRecord, customResolvers []string, timeout time.Duration) CheckResponse {
	domain = strings.TrimSuffix(strings.TrimSpace(domain), ".")
	resolvers := DefaultResolvers
	if len(customResolvers) > 0 {
		resolvers = customResolvers
	}

	if timeout <= 0 {
		timeout = 3 * time.Second
	}

	recordResults := make([]RecordResult, 0, len(expected))
	allMatched := true

	// Check each expected record
	for _, exp := range expected {
		recType := strings.ToUpper(strings.TrimSpace(exp.Type))
		target := strings.TrimSuffix(strings.TrimSpace(exp.Target), ".")

		resolvedValues, err := queryDirect(ctx, domain, recType, resolvers[0], timeout)
		if err != nil && len(resolvedValues) == 0 {
			// Try fallback resolver
			if len(resolvers) > 1 {
				resolvedValues, _ = queryDirect(ctx, domain, recType, resolvers[1], timeout)
			}
		}

		matched := false
		for _, val := range resolvedValues {
			cleanVal := strings.TrimSuffix(strings.TrimSpace(val), ".")
			if strings.EqualFold(cleanVal, target) {
				matched = true
				break
			}
		}

		if !matched {
			allMatched = false
		}

		msg := ""
		if !matched {
			if len(resolvedValues) == 0 {
				msg = "Record not found"
			} else {
				msg = fmt.Sprintf("Expected %s, but resolved %s", target, strings.Join(resolvedValues, ", "))
			}
		}

		recordResults = append(recordResults, RecordResult{
			Type:     recType,
			Target:   target,
			Matched:  matched,
			Resolved: resolvedValues,
			Message:  msg,
		})
	}

	// Calculate multi-resolver propagation
	propagation := checkPropagation(ctx, domain, expected, resolvers, timeout)

	status := "failed"
	if allMatched && propagation.Percentage >= 100.0 {
		status = "verified"
	} else if allMatched || propagation.Percentage > 0.0 {
		status = "pending"
	}

	return CheckResponse{
		Success:     allMatched,
		Status:      status,
		Domain:      domain,
		Records:     recordResults,
		Propagation: propagation,
		CheckedAt:   time.Now().UTC().Format(time.RFC3339),
	}
}

func checkPropagation(ctx context.Context, domain string, expected []ExpectedRecord, resolvers []string, timeout time.Duration) PropagationResult {
	if len(expected) == 0 {
		return PropagationResult{Percentage: 100.0}
	}

	primaryExp := expected[0]
	primaryType := strings.ToUpper(strings.TrimSpace(primaryExp.Type))
	primaryTarget := strings.TrimSuffix(strings.TrimSpace(primaryExp.Target), ".")

	var wg sync.WaitGroup
	var mu sync.Mutex

	details := make([]ResolverResult, len(resolvers))
	matchedCount := 0

	for i, resolver := range resolvers {
		wg.Add(1)
		go func(idx int, resAddr string) {
			defer wg.Done()

			resolved, err := queryDirect(ctx, domain, primaryType, resAddr, timeout)
			resResult := ResolverResult{
				Resolver: resAddr,
				Resolved: resolved,
			}

			if err != nil {
				resResult.Error = err.Error()
			} else {
				for _, val := range resolved {
					if strings.EqualFold(strings.TrimSuffix(strings.TrimSpace(val), "."), primaryTarget) {
						resResult.Matched = true
						break
					}
				}
			}

			mu.Lock()
			details[idx] = resResult
			if resResult.Matched {
				matchedCount++
			}
			mu.Unlock()
		}(i, resolver)
	}

	wg.Wait()

	pct := 0.0
	if len(resolvers) > 0 {
		pct = (float64(matchedCount) / float64(len(resolvers))) * 100.0
	}

	return PropagationResult{
		Percentage:       pct,
		ResolversChecked: len(resolvers),
		ResolversMatched: matchedCount,
		Details:          details,
	}
}

func queryDirect(ctx context.Context, domain, recordType, server string, timeout time.Duration) ([]string, error) {
	c := new(dns.Client)
	c.Timeout = timeout

	m := new(dns.Msg)
	dnsType := dns.TypeA
	switch recordType {
	case "AAAA":
		dnsType = dns.TypeAAAA
	case "CNAME":
		dnsType = dns.TypeCNAME
	case "TXT":
		dnsType = dns.TypeTXT
	case "MX":
		dnsType = dns.TypeMX
	case "NS":
		dnsType = dns.TypeNS
	}

	fqdn := dns.Fqdn(domain)
	m.SetQuestion(fqdn, dnsType)
	m.RecursionDesired = true

	r, _, err := c.ExchangeContext(ctx, m, server)
	if err != nil {
		// Fallback to standard Go net resolver if miekg fails
		return fallbackLookup(ctx, domain, recordType)
	}

	if r == nil || r.Rcode != dns.RcodeSuccess {
		return []string{}, nil
	}

	var results []string
	for _, ans := range r.Answer {
		switch rec := ans.(type) {
		case *dns.A:
			results = append(results, rec.A.String())
		case *dns.AAAA:
			results = append(results, rec.AAAA.String())
		case *dns.CNAME:
			results = append(results, strings.TrimSuffix(rec.Target, "."))
		case *dns.TXT:
			results = append(results, strings.Join(rec.Txt, ""))
		case *dns.MX:
			results = append(results, strings.TrimSuffix(rec.Mx, "."))
		case *dns.NS:
			results = append(results, strings.TrimSuffix(rec.Ns, "."))
		}
	}

	return results, nil
}

func fallbackLookup(ctx context.Context, domain, recordType string) ([]string, error) {
	var r net.Resolver
	var results []string

	switch recordType {
	case "A", "AAAA":
		ips, err := r.LookupHost(ctx, domain)
		if err != nil {
			return nil, err
		}
		for _, ip := range ips {
			parsed := net.ParseIP(ip)
			if parsed != nil {
				if recordType == "A" && parsed.To4() != nil {
					results = append(results, ip)
				} else if recordType == "AAAA" && parsed.To4() == nil {
					results = append(results, ip)
				}
			}
		}
	case "CNAME":
		cname, err := r.LookupCNAME(ctx, domain)
		if err != nil {
			return nil, err
		}
		results = append(results, strings.TrimSuffix(cname, "."))
	case "TXT":
		txts, err := r.LookupTXT(ctx, domain)
		if err != nil {
			return nil, err
		}
		results = append(results, txts...)
	}

	return results, nil
}
