## Purpose

Defines the API telemetry protocol, webhook contracts, and edge ping formats for connecting local Filament Domain Manager installations to the external 24/7 SaaS Companion monitoring platform.

## ADDED Requirements

### Requirement: Companion Telemetry and Domain Sync Ingestion
The local package SHALL provide an outbound client to register custom domains with the Companion Cloud API (`POST /api/v1/monitors`), enabling continuous multi-region health and DNS drift auditing without burdening the host application's server resources.

#### Scenario: Syncing domain monitor to companion cloud
- **WHEN** a custom domain is verified in the local Filament panel and companion monitoring is enabled
- **THEN** the local client transmits the domain hostname, expected records, and ping frequency to the companion cloud API

### Requirement: Inbound Incident Webhook & Multi-Channel Alarms
The Companion Cloud SHALL generate signed HMAC SHA-256 webhooks to the local application and dispatch instant incident notifications via Slack, WhatsApp, SMS, and email whenever an external edge check detects DNS record mismatch, resolution timeouts, or SSL renewal failure.

#### Scenario: Detecting DNS drift or SSL renewal failure
- **WHEN** an external edge pinger detects that a customer's DNS record has been deleted or points to an unauthorized IP
- **THEN** the companion engine dispatches an instant emergency alert to configured team channels (Slack / WhatsApp) within 60 seconds
