## Purpose

Establishes the extension contracts, license verification interfaces, and provider registry hooks in the core open-source package to allow seamless loading of the Pro license package without codebase forks.

## ADDED Requirements

### Requirement: Pro License Verification Contract
The core package SHALL define a `LicenseValidatorContract` interface supporting cryptographically signed license keys (offline RSA/Ed25519 signature checks with cached remote validation) to verify Pro entitlements without impacting panel latency.

#### Scenario: Validating valid Pro license key
- **WHEN** a valid signed Pro license key is supplied in the host application
- **THEN** the validator confirms entitlements and unlocks Pro features across the Filament resource and Go engine

#### Scenario: Missing or invalid Pro license key
- **WHEN** an invalid or expired license key is supplied
- **THEN** the system logs an actionable warning and restricts operation cleanly to the Free tier features

### Requirement: Driver and UI Extension Registry
The core package SHALL provide an extensible driver registry (`ProManager::registerProvider()`, `ProManager::registerModalView()`, `ProManager::registerPollingJob()`) enabling the Pro package to inject automated registrar drivers and white-labeled UI components dynamically.

#### Scenario: Injecting commercial registrar drivers
- **WHEN** the Pro package boots during Laravel service provider registration
- **THEN** it registers Cloudflare, GoDaddy, and AWS Route53 automated drivers into the core connection mode selector
