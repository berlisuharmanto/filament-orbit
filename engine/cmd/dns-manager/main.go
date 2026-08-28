package main

import (
	"context"
	"encoding/json"
	"flag"
	"fmt"
	"io"
	"os"
	"runtime"
	"strings"
	"time"

	"project-moon/dns-engine/pkg/dnscheck"
	"project-moon/dns-engine/pkg/provider"
	"project-moon/dns-engine/pkg/sslcheck"
)

var (
	Version   = "1.0.0"
	BuildDate = "2026-08-28"
)

type VersionResponse struct {
	Success   bool   `json:"success"`
	Version   string `json:"version"`
	OS        string `json:"os"`
	Arch      string `json:"arch"`
	BuildDate string `json:"build_date"`
}

type CheckInput struct {
	Domain          string                   `json:"domain"`
	ExpectedRecords []dnscheck.ExpectedRecord `json:"expected_records"`
	Resolvers       []string                 `json:"resolvers,omitempty"`
	TimeoutMs       int                      `json:"timeout_ms,omitempty"`
}

type SSLAuditInput struct {
	Domain    string `json:"domain"`
	Port      int    `json:"port,omitempty"`
	TimeoutMs int    `json:"timeout_ms,omitempty"`
}

func main() {
	if len(os.Args) < 2 {
		printUsage()
		os.Exit(1)
	}

	command := strings.ToLower(os.Args[1])

	// Handle standard flag cases
	if command == "--version" || command == "-v" || command == "version" {
		outputJSON(VersionResponse{
			Success:   true,
			Version:   Version,
			OS:        runtime.GOOS,
			Arch:      runtime.GOARCH,
			BuildDate: BuildDate,
		})
		return
	}

	subCmd := flag.NewFlagSet(command, flag.ExitOnError)
	inputFlag := subCmd.String("input", "", "JSON input payload (or pipe via STDIN)")
	timeoutFlag := subCmd.Int("timeout", 5000, "Timeout in milliseconds")

	if len(os.Args) > 2 {
		_ = subCmd.Parse(os.Args[2:])
	}

	rawInput := *inputFlag
	if rawInput == "" {
		// Attempt to read from STDIN if pipe is available
		stat, _ := os.Stdin.Stat()
		if (stat.Mode() & os.ModeCharDevice) == 0 {
			stdinBytes, err := io.ReadAll(os.Stdin)
			if err == nil && len(stdinBytes) > 0 {
				rawInput = string(stdinBytes)
			}
		}
	}

	ctx, cancel := context.WithTimeout(context.Background(), time.Duration(*timeoutFlag)*time.Millisecond)
	defer cancel()

	switch command {
	case "check", "validate-dns":
		handleCheck(ctx, rawInput)
	case "audit-ssl":
		handleSSLAudit(ctx, rawInput)
	case "provision-dns":
		handleProvision(ctx, rawInput)
	case "remove-dns":
		handleRemove(ctx, rawInput)
	default:
		outputError(fmt.Sprintf("Unknown command '%s'", command))
		os.Exit(1)
	}
}

func handleCheck(ctx context.Context, rawInput string) {
	if rawInput == "" {
		outputError("Missing input payload for check command")
		os.Exit(1)
	}

	var in CheckInput
	if err := json.Unmarshal([]byte(rawInput), &in); err != nil {
		outputError(fmt.Sprintf("Invalid JSON payload: %v", err))
		os.Exit(1)
	}

	timeout := 3 * time.Second
	if in.TimeoutMs > 0 {
		timeout = time.Duration(in.TimeoutMs) * time.Millisecond
	}

	res := dnscheck.Check(ctx, in.Domain, in.ExpectedRecords, in.Resolvers, timeout)
	outputJSON(res)
}

func handleSSLAudit(ctx context.Context, rawInput string) {
	if rawInput == "" {
		outputError("Missing input payload for audit-ssl command")
		os.Exit(1)
	}

	var in SSLAuditInput
	if err := json.Unmarshal([]byte(rawInput), &in); err != nil {
		outputError(fmt.Sprintf("Invalid JSON payload: %v", err))
		os.Exit(1)
	}

	port := 443
	if in.Port > 0 {
		port = in.Port
	}

	timeout := 5 * time.Second
	if in.TimeoutMs > 0 {
		timeout = time.Duration(in.TimeoutMs) * time.Millisecond
	}

	res := sslcheck.Audit(in.Domain, port, timeout)
	outputJSON(res)
}

func handleProvision(ctx context.Context, rawInput string) {
	if rawInput == "" {
		outputError("Missing input payload for provision-dns command")
		os.Exit(1)
	}

	var req provider.ProvisionRequest
	if err := json.Unmarshal([]byte(rawInput), &req); err != nil {
		outputError(fmt.Sprintf("Invalid JSON payload: %v", err))
		os.Exit(1)
	}

	res := provider.Provision(ctx, req)
	outputJSON(res)
}

func handleRemove(ctx context.Context, rawInput string) {
	if rawInput == "" {
		outputError("Missing input payload for remove-dns command")
		os.Exit(1)
	}

	var req provider.RemoveRequest
	if err := json.Unmarshal([]byte(rawInput), &req); err != nil {
		outputError(fmt.Sprintf("Invalid JSON payload: %v", err))
		os.Exit(1)
	}

	res := provider.Remove(ctx, req)
	outputJSON(res)
}

func outputJSON(v interface{}) {
	enc := json.NewEncoder(os.Stdout)
	enc.SetIndent("", "  ")
	_ = enc.Encode(v)
}

func outputError(msg string) {
	outputJSON(map[string]interface{}{
		"success":       false,
		"status":        "error",
		"error_message": msg,
		"timestamp":     time.Now().UTC().Format(time.RFC3339),
	})
}

func printUsage() {
	fmt.Fprintf(os.Stderr, "Usage: dns-manager <command> [--input='<json>']\n")
	fmt.Fprintf(os.Stderr, "Commands:\n")
	fmt.Fprintf(os.Stderr, "  version        Show version and architecture info\n")
	fmt.Fprintf(os.Stderr, "  check          Validate DNS records against expectations\n")
	fmt.Fprintf(os.Stderr, "  audit-ssl      Inspect domain TLS certificate status\n")
	fmt.Fprintf(os.Stderr, "  provision-dns  Create DNS records via provider driver (libdns)\n")
	fmt.Fprintf(os.Stderr, "  remove-dns     Delete DNS records via provider driver (libdns)\n")
}
