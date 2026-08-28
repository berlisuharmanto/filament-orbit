## Why

To transform Project Moon from an open-source library into a sustainable, revenue-generating commercial product ecosystem, the project requires an official architectural blueprint defining its 3-tier business and technical model:

1. **The "Freemium" Open-Source Hook (Tier 1)**: Maximizes developer adoption, GitHub stars, and community trust with a robust free Filament plugin for manual custom domain management and sub-second Go DNS resolution.
2. **The "Pro" Self-Hosted Commercial License (Tier 2)**: Monetizes SaaS builders via a one-time purchase ($49–$99) that unlocks 1-click registrar automation (Cloudflare, GoDaddy, AWS Route53), white-labeled tenant instruction modals, and local queue background polling.
3. **The SaaS Companion Uptime Engine (Tier 3)**: Converts agencies and high-traffic platforms into recurring MRR ($9–$29/month) by providing 24/7 external edge monitoring and multi-channel incident alarms (WhatsApp, Slack, SMS) when client DNS or SSL renewal fails.

## What Changes

* **RFC: Commercial Tier Definition (`commercial-tiers-rfc`)**: Formalizes the feature gate boundaries between Free Open-Source, Self-Hosted Pro, and SaaS Companion tiers.
* **Pro Extension Contract (`pro-extension-contract`)**: Establishes the plugin extension interfaces, license key validation hooks, and driver registration points allowing the free package to load Pro capabilities seamlessly without hard-forking.
* **SaaS Companion API Protocol (`saas-companion-api`)**: Drafts the webhook ingestion protocol, edge health ping telemetry, and incident dispatch specifications connecting local Filament installations to a remote 24/7 monitoring engine.

## Capabilities

### New Capabilities
- `commercial-tiers-rfc`: Product tier matrix, feature gates, and commercial packaging specifications for Free, Pro, and SaaS Companion offerings.
- `pro-extension-contract`: Plugin contract in the open-source package for verifying Pro license keys and unlocking registrar automation, custom modal branding, and queue jobs.
- `saas-companion-api`: API and webhook protocol specification for external 24/7 edge uptime pings, SSL failure alarms, and multi-channel incident notifications.

### Modified Capabilities
<!-- None -->

## Impact

* **Architecture**: Introduces clean extension hooks (`ProManager`, `LicenseValidatorContract`, `CompanionTelemetryClient`) into the core package.
* **Licensing**: Clear separation between MIT Open Source Core, Proprietary Pro Extension, and Managed Cloud Service.
