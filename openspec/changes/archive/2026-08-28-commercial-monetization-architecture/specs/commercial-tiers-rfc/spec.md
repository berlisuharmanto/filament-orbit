## Purpose

Defines the official RFC specification for the 3-tier commercial model, detailing feature availability across Open-Source Free, Self-Hosted Pro, and SaaS Companion tiers.

## ADDED Requirements

### Requirement: Open-Source Free Tier Scope
The free tier SHALL be distributed under the MIT license and provide full manual custom domain management, sub-second Go DNS resolution, TLS certificate auditing, and standard Filament Admin Panel integration.

#### Scenario: Running on Free Open-Source tier
- **WHEN** a developer installs `project-moon/filament-domain-manager` without a Pro license key
- **THEN** manual CNAME/A domain management, multi-resolver propagation checks, and SSL inspection operate without restrictions or time limits

### Requirement: Self-Hosted Pro Tier Feature Gate
The Pro tier SHALL require a valid commercial license key to unlock automated registrar REST drivers (Cloudflare, GoDaddy, AWS Route53), white-labeled tenant setup modals, and automated background queue verification jobs.

#### Scenario: Unlocking Pro registrar automation
- **WHEN** a valid Pro license key is configured in `config/domain-manager.php`
- **THEN** the domain resource enables 1-click automated provider sync, custom branded modals, and background polling workers

### Requirement: SaaS Companion Tier Ingestion & Alarms
The SaaS Companion tier SHALL provide an external recurring subscription service that monitors client domains from multi-region edge locations, triggering real-time webhooks and multi-channel alarms (WhatsApp, Slack, SMS) upon DNS drift or certificate expiration.

#### Scenario: Ingesting domain telemetry into companion cloud
- **WHEN** a domain is connected and companion monitoring is enabled
- **THEN** the local plugin registers the domain with the companion API to begin external 24/7 health pings
