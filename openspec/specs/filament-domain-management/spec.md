## Purpose

Delivers Filament Admin Panel resources, tables, settings, and actions for domain management, supporting both automated provider-driven DNS configuration and manual smart CNAME setups compatible across Filament v3 through v5.

## Requirements

### Requirement: Domain Resource and Status Table
The plugin SHALL provide a `DomainResource` in the Filament Admin Panel showing registered domains, associated tenant, connection mode (Automated vs. Manual), current DNS verification badge, SSL certificate health, and timestamp of the last verification.

#### Scenario: Viewing domain records with status badges
- **WHEN** an administrator views the domain list table in the Filament Admin Panel
- **THEN** the table displays each domain alongside badges for connection mode, DNS verification status (Verified, Pending, Failed), and SSL status (Valid, Expired, Missing)

#### Scenario: Filtering by verification status
- **WHEN** an administrator applies a filter for "Unverified" or "Failed" domains
- **THEN** the table updates to display only domains matching the selected verification criteria

### Requirement: Connection Mode Selection and Automated Provisioning
The domain creation and editing forms SHALL support two distinct connection modes: "Automated (Provider API / Connect)" and "Manual (Smart CNAME / A Records)".

#### Scenario: Provisioning domain via automated provider API
- **WHEN** the user selects "Automated", chooses a supported provider (e.g. Cloudflare), supplies or selects credentials, and saves
- **THEN** the system triggers the Go binary to provision records in the provider's zone, verifies immediate status, and displays a success notification

#### Scenario: Provisioning domain via manual smart CNAME
- **WHEN** the user selects "Manual", specifies the custom domain name, and saves
- **THEN** the system creates the pending domain record and renders targeted CNAME and A record instructions for the user

### Requirement: Encrypted DNS Provider Credentials Configuration
The plugin SHALL provide a secure settings page or panel configuration to manage and store encrypted API tokens and keys for supported DNS providers (Cloudflare, GoDaddy, AWS Route53).

#### Scenario: Saving encrypted provider credentials
- **WHEN** an administrator enters an API token for a DNS provider in the plugin settings
- **THEN** the token is encrypted using Laravel's encryption engine and stored securely in the database

#### Scenario: Verifying provider credentials connectivity
- **WHEN** an administrator clicks "Test Connection" for a configured provider
- **THEN** the system executes a health check via the Go binary and returns an active/authorized status

### Requirement: On-Demand Verification Actions
The domain table and edit pages SHALL provide actions allowing administrators to trigger on-demand DNS verification and SSL auditing via the Go binary bridge.

#### Scenario: Executing on-demand DNS check
- **WHEN** the administrator clicks the "Verify DNS" action on a domain row
- **THEN** the system executes the Go binary check, updates the domain record in the database, and displays a Filament notification indicating success or failure details

#### Scenario: DNS record mismatch notification
- **WHEN** the on-demand check detects mismatched or missing records
- **THEN** the notification reports the detected records versus expected records and updates the status badge to Failed

### Requirement: DNS Record Configuration Modal
The domain resource SHALL provide an action that displays a modal showing the exact DNS records (e.g. A record, CNAME target, TXT verification code) required for the domain to resolve correctly.

#### Scenario: Viewing DNS setup instructions
- **WHEN** the administrator clicks the "DNS Instructions" action
- **THEN** a modal appears displaying the required hostnames, record types, target values, and copy-to-clipboard buttons

### Requirement: Forward-Compatible Filament Plugin Registration
The plugin SHALL register with Filament panels using standard `Plugin` contracts compatible with Filament v3, v4, and v5 panel providers without relying on deprecated methods or version-restricted hooks.

#### Scenario: Registering plugin in panel configuration
- **WHEN** the plugin is added via `->plugin(new FilamentDomainManagerPlugin())` in a panel provider
- **THEN** the domain resources and navigation items are registered cleanly into the panel navigation
