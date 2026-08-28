## Context

See `proposal.md` for motivation.

The project is evolving into a 3-tiered commercial ecosystem:
1. **Free OSS Core**: Open-source Filament plugin + Go DNS engine on GitHub/Packagist.
2. **Pro Self-Hosted**: Paid private package unlocking 1-click registrar API drivers, white-labeled instruction modals, and local queue polling.
3. **SaaS Companion**: Managed cloud uptime monitoring service with real-time multi-channel incident alerting (Slack, WhatsApp, SMS).

## Goals / Non-Goals

**Goals:**
* Define the technical architecture for the Pro extension loader within the open-source core.
* Design the `LicenseValidatorContract` supporting offline cryptographic signature validation (RSA/Ed25519) to prevent performance penalties.
* Design the `ProManager` extension registry allowing commercial registrar drivers to register seamlessly into the Filament UI.
* Specify the SaaS Companion API, telemetry synchronization endpoints, and HMAC SHA-256 incident webhook contracts.

**Non-Goals:**
* Implementing aggressive DRM or telemetry spyware into the open-source core (the free core remains 100% functional and MIT-compliant).

## Decisions

### 1. Pro Extension Architecture (`ProManager` & Registry)
* **Decision**: Define a lightweight `ProManager` singleton in the core package:
  ```php
  namespace ProjectMoon\FilamentDomainManager\Contracts;
  
  interface ProManagerContract
  {
      public function isPro(): bool;
      public function registerProviderDriver(string $id, ProviderDriverContract $driver): void;
      public function registerCustomModalView(string $viewPath): void;
      public function getSupportedProviders(): array;
  }
  ```
* **Rationale**: The core package remains completely standalone. When `project-moon/filament-domain-manager-pro` is installed via Composer, its service provider registers commercial drivers into `ProManager` automatically.

### 2. Offline Cryptographic License Validation
* **Decision**: Validate Pro licenses using Ed25519 public key signatures embedded within the license string.
* **Payload Structure**:
  ```json
  {
      "license_key": "PRO-MOON-XXXX-YYYY-ZZZZ",
      "customer_email": "dev@company.com",
      "tier": "pro_lifetime",
      "issued_at": 1756400000,
      "signature": "base64_signature_here"
  }
  ```
* **Rationale**: Eliminates network latency during panel boot; works seamlessly in offline / local development environments.

### 3. SaaS Companion API & Incident Webhooks
* **Endpoints**:
  * `POST /api/v1/monitors`: Ingests active custom domains and expected DNS records for 24/7 background edge polling.
  * `POST /api/v1/webhooks/dns-incident`: Dispatched by the companion cloud to the customer's Laravel app and external notification channels.
* **Incident Payload Example**:
  ```json
  {
      "event": "dns.drift_detected",
      "domain": "store.tenant-brand.com",
      "expected_cname": "ingress.mysaas.com",
      "observed_cname": "unknown-host.com",
      "propagation_rate": 25,
      "severity": "critical",
      "timestamp": "2026-08-28T20:00:00Z"
  }
  ```

## Risks / Trade-offs

* **[Risk] Code Piracy / Key Sharing** → **Mitigation**: Standard developer trust model (used by Spatie, Livewire, Filament Pro plugins). The value is in official updates, security patches, and the SaaS companion service.
