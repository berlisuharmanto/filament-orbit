# Project Moon: Filament Multi-Tenancy Custom Domain & DNS Manager
> **Comprehensive System Architecture, Technical Deep-Dive, and Strategic Evaluation**
> *Prepared for Google NotebookLM Ingestion, Architecture Reviews, and Technical Documentation*

---

## 1. Executive Summary

### 1.1 The Core Problem
In multi-tenant SaaS applications (e-commerce platforms, website builders, portals), allowing customers to connect their own custom domains (e.g., `store.tenant-brand.com` or `apexbrand.com`) is a critical feature. Historically, developers faced major challenges:
1. **The "Registrar Hopping" Problem**: Non-technical customers struggle to configure DNS records (A, CNAME, TXT) inside complex registrar dashboards (GoDaddy, Cloudflare, Namecheap), resulting in high support ticket volumes and abandoned onboarding.
2. **The "Port 53 Hosting" Trap**: Running an authoritative DNS nameserver inside a web app requires binding raw UDP port 53 with root privileges—an impossible requirement in standard web hosting, container clusters, or serverless environments.
3. **PHP Socket Bottlenecks**: PHP's built-in `dns_get_record()` and `fsockopen()` are single-threaded and blocking. Querying multiple global DNS resolvers to check propagation can take 4–8 seconds per domain, freezing HTTP requests.
4. **PHP FFI Instability**: Using Foreign Function Interface (FFI) to link C/Go shared libraries directly into PHP-FPM can cause segmentation faults, memory leaks, and worker crashes.

### 1.2 The Solution
**Project Moon (`filament-domain-manager`)** solves this with a **Hybrid Go + PHP Architecture** based on the **Driver-Based DNS Connector Model**:
* **Automated 1-Click Connection**: Integrates directly with DNS provider REST APIs (Cloudflare, GoDaddy) to create, update, and delete DNS records programmatically with zero registrar-hopping.
* **Smart Manual CNAME Fallback**: Generates dynamic, tailored DNS setup instructions for tenants whose registrars lack API automation.
* **Native Compiled Go DNS Engine**: A headless Go binary executed via an isolated CLI subprocess (`Symfony\Component\Process\Process`) that performs concurrent, non-blocking DNS queries across 4 global resolvers (`1.1.1.1`, `8.8.8.8`, `9.9.9.9`, `208.67.222.222`) in < 50ms and audits TLS/SSL certificate health.
* **Forward-Compatible Filament Plugin**: Integrates into the Filament Admin Panel (compatible across Filament v3, v4, and v5) with live status badges, on-demand verification, and instruction modals.

---

## 2. System Architecture & Data Flow

```
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                              FILAMENT ADMIN PANEL (PHP 8.2+)                           │
├────────────────────────────────────────────────────────────────────────────────────────┤
│  • DomainResource: Data table with DNS & SSL health badges                             │
│  • Connection Mode Switcher: "Automated (Provider API)" vs "Manual (Smart CNAME)"     │
│  • Actions: "Verify DNS" (Instant check), "DNS Setup" (Instruction Modal)              │
│  • Security: AES-256 encrypted provider credential casting in database                 │
└───────────────────────────────────────────┬────────────────────────────────────────────┘
                                            │
                             Symfony CLI Process Bridge (JSON)
                             `bin/dns-manager check|audit-ssl|provision-dns`
                                            │
                                            ▼
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                           COMPILED GO ENGINE (`bin/dns-manager`)                       │
├────────────────────────────────────────────────────────────────────────────────────────┤
│  ┌───────────────────────────────────┐    ┌─────────────────────────────────────────┐  │
│  │ Concurrent Multi-Resolver Engine  │    │ DNS Provider REST Drivers               │  │
│  │ (UDP Goroutines: 1.1.1.1, 8.8.8.8)│    │ (Cloudflare, GoDaddy Direct REST)       │  │
│  └───────────────────────────────────┘    └─────────────────────────────────────────┘  │
│  ┌───────────────────────────────────┐    ┌─────────────────────────────────────────┐  │
│  │ TLS / SSL Certificate Inspector   │    │ Multi-Platform Static Binaries          │  │
│  │ (Port 443 SNI, SAN, Expiration)   │    │ (Linux, macOS, Windows / amd64, arm64)  │  │
│  └───────────────────────────────────┘    └─────────────────────────────────────────┘  │
└────────────────────────────────────────────────────────────────────────────────────────┘
```

### 2.1 The Two Connection Strategies

1. **Automated Strategy (1-Click Direct Connect)**:
   * Tenant selects provider (Cloudflare / GoDaddy) and provides credentials.
   * Go binary calls the provider's REST API, checks zone existence, and injects the target CNAME/A record.
   * Domain DNS status is set immediately to `verified`.
2. **Manual Strategy (Smart CNAME & A Fallback)**:
   * Tenant enters domain (e.g. `shop.client.com` or apex `client.com`).
   * System generates targeted instructions (Apex $\rightarrow$ A record to Ingress IP; Subdomain $\rightarrow$ CNAME to Ingress Target).
   * Status is set to `pending`. Tenant can click **"Verify DNS"** at any time to run an instant multi-resolver propagation check.

---

## 3. Deep-Dive Component Breakdown

### 3.1 The Go DNS Engine (`engine/`)
* **`pkg/dnscheck`**:
  * Uses `github.com/miekg/dns` for direct DNS packet crafting.
  * Dispatches parallel goroutines to query authoritative resolvers and global public nameservers (`1.1.1.1`, `8.8.8.8`, `9.9.9.9`, `208.67.222.222`).
  * Matches A, AAAA, CNAME, and TXT records and computes exact propagation percentage ($0\%$ to $100\%$).
* **`pkg/sslcheck`**:
  * Establishes a TLS connection to port 443 with Server Name Indication (SNI).
  * Validates the certificate chain, extracts Subject Alternative Names (SAN), issuer identity, validity window, and computes remaining days until expiration.
* **`pkg/provider`**:
  * Direct REST client implementations for **Cloudflare** (`/client/v4/zones/.../dns_records`) and **GoDaddy** (`/v1/domains/.../records`).
  * Includes a zero-network mock provider driver for unit and feature testing.
* **Cross-Compilation (`engine/build.sh`)**:
  * Cross-compiles static binaries targeting 5 platforms: `linux-amd64`, `linux-arm64`, `darwin-amd64`, `darwin-arm64`, and `windows-amd64`.

### 3.2 The PHP Subprocess Bridge (`src/`)
* **`DnsEngineBridge`**:
  * Locates the compiled binary via configuration or OS lookup.
  * Executes the binary using `Symfony\Component\Process\Process`, passing structured JSON payloads over CLI flags or STDIN.
  * Parses JSON STDOUT into strongly-typed Data Transfer Objects (DTOs).
* **Typed DTOs**:
  * `DnsCheckRequest` / `DnsCheckResult`: Carries domain expectations, observed records, and propagation metrics.
  * `SslAuditResult`: Carries certificate validity, issuer, SANs, and expiration countdown.
  * `ProviderProvisionRequest` / `ProviderProvisionResult`: Encapsulates provider record provisioning and removal.
* **`DomainManager` Facade**:
  * Static facade providing clean developer access: `DomainManager::checkDns(...)`, `DomainManager::auditSsl(...)`, `DomainManager::provisionDns(...)`.

### 3.3 The Artisan Binary Installer (`src/Commands/InstallBinaryCommand.php`)
* Command: `php artisan domain-manager:install-binary`
* Automatically inspects `PHP_OS_FAMILY` (Linux, Darwin, Windows) and `php_uname('m')` (x86_64 $\rightarrow$ amd64, aarch64 $\rightarrow$ arm64).
* Copies the matching pre-compiled binary from `bin/dist/` into `bin/dns-manager`, assigns `0755` executable permissions, and runs a sanity test (`--version`).

### 3.4 Filament Admin Panel Integration (`src/Resources/DomainResource.php`)
* **`FilamentDomainManagerPlugin`**: Standard Filament `Plugin` contract.
* **`Domain` Eloquent Model**:
  * Uses encrypted attribute casting (`encrypted:array`) for `provider_credentials` to protect API keys in the database.
  * Helper methods: `$domain->verifyDns()`, `$domain->auditSsl()`, `$domain->provisionWithProvider()`, `$domain->removeWithProvider()`.
* **Resource UI**:
  * Searchable and sortable list table with DNS status badges (`Verified`, `Pending`, `Failed`) and SSL badges (`Valid`, `Expiring Soon`, `Expired`).
  * Actions: "Verify DNS" (instant notification), "DNS Setup Instructions" (modal with copyable records).
  * Auto-provisions records via provider API on record creation and deletes records on deletion.

### 3.5 Embedded Developer Playground (`playground/`)
* A full **Laravel 11** sandbox application located in `playground/`.
* Configured with a Composer `"path"` repository pointing to `../` (`"symlink": true`), providing real-time code reload.
* Includes pre-configured database migrations, seeders with an initial admin user (`admin@example.com` / `password`), and automated feature tests.
* Run with: `composer playground:serve`.

---

## 4. OpenSpec Specifications Matrix

| Specification | Capability Area | Key Requirements | Status |
| :--- | :--- | :--- | :---: |
| **`dns-engine-binary`** | Go Engine | Multi-resolver concurrent DNS checks, TLS handshake SSL auditing, Cloudflare/GoDaddy REST drivers. | **Archived & Synced** |
| **`artisan-binary-installer`** | CLI Tooling | OS/Architecture auto-detection, `chmod 0755` permissions, `--force` flag, `--version` health test. | **Archived & Synced** |
| **`filament-domain-management`** | Filament UI | `DomainResource`, connection mode switch, encrypted credentials, verify actions, setup modal. | **Archived & Synced** |
| **`embedded-playground`** | Developer Sandbox | Laravel 11 app in `playground/`, path repo symlink, seeder, feature test suite. | **Archived & Synced** |
| **`package-export-rules`** | Distribution Cleanliness | `.gitignore` runtime filters, `.gitattributes` `export-ignore` rules for small package footprint. | **Archived & Synced** |

---

## 5. The Good Side: Core Strengths & Architectural Wins

1. **⚡ Sub-Second Non-Blocking Performance**:
   * Resolving DNS across multiple global resolvers in Go goroutines takes **< 50ms**, compared to 4,000ms+ in sequential PHP sockets.
2. **🛡️ Complete Process & Memory Isolation**:
   * Subprocess execution completely protects PHP-FPM from memory corruption or crashes. If the Go binary encounters an unexpected packet, only that subprocess exits; the web server remains 100% operational.
3. **🚀 Frictionless Onboarding (Zero Registrar Hopping)**:
   * End-users using Cloudflare or GoDaddy don't need to manually configure DNS zones. The plugin does it automatically via API.
4. **🔄 Filament v3, v4, and v5 Forward Compatibility**:
   * Uses standard `Plugin` interfaces, preventing deprecation issues across major Filament releases.
5. **📦 Zero-Configuration Developer Experience**:
   * Cross-compiled binaries are included out of the box in `bin/dist/`. No local Go compiler is required for end users.
   * `composer playground:serve` allows immediate local testing in a real browser.
6. **🧹 Minimal Package Footprint**:
   * `.gitattributes` excludes tests, Go engine source, and the playground when installed via `composer require`.

---

## 6. The Bad Side: Limitations, Technical Gaps & Edge Cases

1. **⚠️ Host Binary Execution Dependency (`proc_open`)**:
   * *Limitation*: Highly restricted shared hosting (cPanel with `proc_open`/`exec` disabled in `php.ini`) or strict serverless environments (without custom binary layers) cannot execute the Go binary.
   * *Mitigation*: Implement an optional pure-PHP fallback resolver using `dns_get_record()` for restricted environments.
2. **⚠️ Lack of Automated Background Queue Polling**:
   * *Limitation*: For manual DNS setups, propagation takes time. Verification currently requires clicking "Verify DNS" manually.
   * *Mitigation*: Add an optional scheduled queue job (`PollPendingDomainsJob`) that checks pending domains every 5 minutes until resolved and fires a `DomainVerifiedEvent`.
3. **⚠️ SSL Auditing vs. SSL Issuance (No ACME Client)**:
   * *Limitation*: The plugin *audits* existing certificates (expiry, validity, SAN), but *does not issue* Let's Encrypt certificates directly. It assumes hosting infrastructure (Caddy On-Demand TLS, Laravel Forge, Traefik, or Certbot) handles issuance once DNS is pointing to the ingress IP.
   * *Mitigation*: Provide documented integration webhooks for Caddy (`/ask` endpoint) and Laravel Forge.
4. **⚠️ API Token UX Friction for Non-Technical Tenants**:
   * *Limitation*: In automated mode, tenants must manually copy and paste API tokens from their registrar dashboard.
   * *Mitigation*: Implement OAuth 2.0 Connect popup flows (e.g. Cloudflare Apps / GoDaddy OAuth).
5. **⚠️ Limited Initial Provider Ecosystem**:
   * *Limitation*: Currently supports Cloudflare, GoDaddy, and Mock. AWS Route53, Google Cloud DNS, DigitalOcean, and Namecheap are not yet built.
   * *Mitigation*: Expand `engine/pkg/provider/` with additional cloud provider drivers.

---

## 7. The 3-Tier Commercial & Monetization Architecture

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ TIER 1: THE "FREEMIUM" GITHUB HOOK (100% Free & Open Source - MIT)          │
├─────────────────────────────────────────────────────────────────────────────┤
│ • Goal: Maximum viral distribution, GitHub stars, developer trust & loyalty │
│ • Features: Filament DomainResource + Compiled Go DNS Subprocess Engine     │
│ • Experience: Manual Smart CNAME/A flow + Multi-Resolver Propagation Checks │
└──────────────────────────────────────┬──────────────────────────────────────┘
                                       │ 15-20% Upgrade to Pro
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│ TIER 2: THE "PRO" SELF-HOSTED LICENSE ($49 - $99 One-Time / Lifetime)       │
├─────────────────────────────────────────────────────────────────────────────┤
│ • Target: SaaS founders & indie hackers building custom domain portals      │
│ • Unlocks: 1-Click Registrar Automation (Cloudflare/GoDaddy/Route53 APIs)   │
│            White-labeled tenant modals + Background Queue Polling Jobs      │
└──────────────────────────────────────┬──────────────────────────────────────┘
                                       │ 5-10% Subscribe to SaaS
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│ TIER 3: THE SAAS COMPANION MONITOR ($9 - $29/month Recurring MRR)           │
├─────────────────────────────────────────────────────────────────────────────┤
│ • Target: Digital agencies, high-traffic SaaS & multi-tenant platforms     │
│ • Unlocks: 24/7 External Global Edge Monitoring (Zero host server load)     │
│            Instant WhatsApp / Slack / Discord / SMS alerts when DNS/SSL dies│
└─────────────────────────────────────────────────────────────────────────────┘
```

### 7.1 Pro Extension Contract & Offline License Verification
* **Extension Mechanism (`ProManager`)**: Core package defines `ProManagerContract`. When the private `filament-domain-manager-pro` package is installed, its service provider registers commercial drivers and white-labeled views dynamically into the panel.
* **Cryptographic Offline License Key**: Uses Ed25519 public key signatures inside the license key string to verify entitlements locally without adding network latency to panel requests.

### 7.2 SaaS Companion Cloud API & Inbound Webhooks
* **Telemetry Sync**: `POST /api/v1/monitors` registers custom domains with the Companion Cloud for 24/7 edge health polling across 10+ global locations.
* **Incident Webhooks**: `POST /api/v1/webhooks/dns-incident` (signed with HMAC SHA-256) fires immediate notifications to agency Slack, WhatsApp, and Discord channels when DNS drift or certificate expiration is detected.

---

## 8. Summary Table for Quick Reference

| Dimension | Implementation Details |
| :--- | :--- |
| **Binary Engine** | Go 1.25, `miekg/dns`, `crypto/tls`, REST JSON protocol |
| **PHP Bridge** | `Symfony\Component\Process\Process`, strongly typed DTOs |
| **UI Framework** | Filament v3, v4, v5 compatible `Plugin` and `DomainResource` |
| **Supported Modes** | Automated (Cloudflare, GoDaddy REST APIs) + Manual (Smart CNAME / A) |
| **Commercial Model**| Free MIT OSS Core + $49 Pro Extension + $9/mo SaaS Companion Monitor |
| **Storage Security** | AES-256 encrypted credential casting in database (`encrypted:array`) |
| **Distribution** | Composer package + Artisan binary installer (`domain-manager:install-binary`) |
| **Test Coverage** | 9 Pest unit/feature tests (36 assertions) + 3 Playground tests + Go test suite |
| **Sandbox** | Embedded Laravel 11 app in `playground/` with live symlink |

