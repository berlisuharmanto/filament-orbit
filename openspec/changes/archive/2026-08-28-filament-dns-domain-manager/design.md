## Context

See `proposal.md` for motivation.

The project implements a domain management ecosystem divided into two distinct components:
1. **Core Go Engine**: A compiled, headless CLI binary providing high-speed DNS record validation, propagation checks, SSL inspection, and automated DNS provisioning via provider drivers (`libdns`).
2. **Filament Plugin & PHP Bridge**: A Laravel package providing an Admin Panel UI across Filament v3 through v5, communicating with the Go binary via a subprocess (`Symfony\Component\Process\Process`).

## Goals / Non-Goals

**Goals:**
* Provide a single, self-contained Go CLI binary supporting cross-compilation (`linux-amd64`, `linux-arm64`, `darwin-amd64`, `darwin-arm64`, `windows-amd64`).
* Implement the **Driver-Based DNS Connector Model**:
  * Programmatic DNS record provisioning via Go `libdns` drivers (Cloudflare, GoDaddy, AWS Route53).
  * Smart CNAME / A record fallback with instant multi-resolver propagation checks.
* Provide a robust PHP bridge using `Symfony\Component\Process\Process` with JSON input/output over STDIN/STDOUT to isolate Go execution from PHP-FPM workers.
* Deliver an Artisan installer command (`php artisan domain-manager:install-binary`) to detect OS/Arch and download/install the appropriate binary asset.
* Provide a clean, forward-compatible Filament Admin Panel view (resources, tables, connection mode toggles, encrypted credential settings, setup modals) adhering to Filament `Plugin` contracts across versions 3, 4, and 5.
* Include comprehensive "Getting Started" documentation outlining installation, setup, and binary deployment.

**Non-Goals:**
* Running an embedded Authoritative DNS server on port 53 (rejected because web application servers cannot bind port 53 without root/capabilities, and server maintenance/reboots would cause global DNS outages for tenants).
* Building an in-process PHP FFI bridge (rejected due to memory instability and hosting restrictions).
* Compiling Go on the target hosting machine (the installer downloads pre-built binaries, requiring no Go toolchain on PHP servers).
* Implementing custom ACME/CA server logic (the engine inspects and audits TLS certificates; actual cert provisioning relies on standard ACME providers or reverse proxy webhooks).
* Tenant-side self-service portals (scoped strictly to Admin Panel View in this change).

## Decisions

### 1. CLI Process Subprocess instead of PHP FFI
* **Decision**: Communicate with the Go binary using `Symfony\Component\Process\Process`, passing structured parameters via CLI flags or STDIN and parsing JSON from STDOUT.
* **Rationale**: FFI requires `ffi.enable=true` in `php.ini`, which is disabled in many managed production environments. Furthermore, Go's runtime and garbage collector running inside PHP-FPM thread memory can lead to signal conflicts and segfaults. CLI subprocesses are safe, isolated, and crash-resilient.
* **Alternatives Considered**:
  * *PHP FFI*: Lower invocation overhead, but unsafe and prohibited on many servers.
  * *Local HTTP/gRPC Daemon*: Eliminates process spawn overhead, but requires managing a persistent background daemon/service on host servers.

### 2. Driver-Based DNS Connector Architecture (`libdns` in Go)
* **Decision**: Embed modular provider drivers using Go's `libdns` library ecosystem (`libdns/cloudflare`, `libdns/godaddy`, `libdns/route53`).
* **Rationale**: `libdns` is the battle-tested DNS abstraction powering Caddy Server. It provides uniform methods (`AppendRecords`, `DeleteRecords`, `SetRecords`) across dozens of DNS providers. This enables tenants or administrators to supply an API token and have custom domains auto-configured with zero manual registrar dashboard navigation.
* **Alternatives Considered**:
  * *Writing custom API clients in PHP*: Reinventing provider APIs in PHP is high maintenance and slow; Go's existing `libdns` modules are actively maintained.
  * *Port 53 Authoritative DNS*: Unreliable and permission-heavy on standard Laravel hosting environments.

### 3. Artisan Installer Command (`domain-manager:install-binary`)
* **Decision**: Deliver the compiled binary through an Artisan command that inspects `PHP_OS_FAMILY` and architecture (`php_uname('m')`), downloads or unpacks the binary to a configured directory (default `vendor/bin` or `storage/app/bin/`), applies `chmod 0755`, and validates it via `--version`.
* **Rationale**: Distributing pre-compiled binaries for 5+ platforms inside the Composer package directly causes severe repository bloat. Compiling on the client requires Go installed on production web servers. An Artisan command is idiomatic, scriptable in deployment pipelines (e.g. `composer post-install-cmd`), and easily documented.

### 4. Forward-Compatible Filament Plugin Architecture (v3 to v5)
* **Decision**: Implement the plugin as a strict `Filament\Contracts\Plugin` class. Use standard Filament table column types (TextColumn, BadgeColumn), action modals, encrypted credential fields, and avoid referencing internal methods that change between major versions.
* **Rationale**: Filament's plugin registration pattern (`Panel::plugin()`) is consistent from v3 through v4/v5. By adhering strictly to published contracts and modular resource definitions, the plugin functions smoothly across versions.

### 5. CLI JSON Exchange Protocol
* **Decision**: The Go binary accepts subcommands with JSON payloads:
  * **Validation**:
    * Input: `dns-manager check --input='{"domain":"tenant.example.com","expected":[{"type":"CNAME","target":"ingress.mysaas.com"}]}'`
    * Output:
      ```json
      {
        "success": true,
        "status": "verified",
        "records": [{"type": "CNAME", "target": "ingress.mysaas.com", "matched": true}],
        "propagation": {"percentage": 100, "resolvers_checked": 4, "resolvers_matched": 4}
      }
      ```
  * **Automated Provisioning**:
    * Input:
      ```json
      {
        "command": "provision-dns",
        "provider": "cloudflare",
        "auth_token": "secret-token",
        "zone": "example.com",
        "records": [{"type": "CNAME", "name": "shop", "value": "ingress.mysaas.com", "ttl": 300}]
      }
      ```
    * Output:
      ```json
      {
        "success": true,
        "status": "provisioned",
        "provider": "cloudflare",
        "created_records": [{"id": "rec_12345", "type": "CNAME", "name": "shop.example.com"}]
      }
      ```

## Risks / Trade-offs

* **[Risk] Provider API rate limits or downtime** → **Mitigation**: The Go engine returns granular HTTP/API error codes. Filament displays actionable notices (e.g. "Rate limited by Cloudflare, retrying in 30s") while preserving the manual CNAME instructions as an instant fallback.
* **[Risk] `proc_open` disabled on restrictive hosting** → **Mitigation**: The installer command and health check probe for `proc_open` / `Symfony\Process` support during registration and emit actionable troubleshooting steps.
* **[Risk] Secure credential storage** → **Mitigation**: All provider API keys and tokens entered in Filament are encrypted via Laravel's `Illuminate\Support\Facades\Crypt` before database persistence and decrypted only in memory when spawning the Go process.
