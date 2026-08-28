## Purpose

Provides high-speed, compiled native DNS record validation, multi-resolver propagation checks, SSL certificate auditing, and automated DNS record provisioning via provider drivers (`libdns`) over standard CLI JSON commands.

## ADDED Requirements

### Requirement: Validate DNS Records against Expected Targets
The DNS engine SHALL accept target domain names and expected record sets (A, AAAA, CNAME, TXT) and verify whether the resolved records match expectations. Output MUST be formatted as a structured JSON response to STDOUT.

#### Scenario: Exact A-record match
- **WHEN** the engine is invoked with a domain and an expected IPv4 address that matches the resolved A record
- **THEN** the engine returns a JSON payload with `status: "verified"` and detailed record comparison entries

#### Scenario: CNAME target match
- **WHEN** the engine is invoked with a domain and an expected canonical target hostname matching the resolved CNAME record
- **THEN** the engine returns a JSON payload with `status: "verified"`

#### Scenario: Missing or mismatched record
- **WHEN** the engine is invoked with a domain whose resolved records do not match the expected target
- **THEN** the engine returns a JSON payload with `status: "failed"` and specifies the observed versus expected values

### Requirement: Concurrent Multi-Resolver Propagation Verification
The DNS engine SHALL query a configurable list of public and authoritative nameservers concurrently to measure global DNS propagation status and return individual resolver outcomes.

#### Scenario: Global DNS propagation verified
- **WHEN** all queried global resolvers return the expected record value
- **THEN** the engine returns `propagation_percentage: 100` and lists each resolver status as `resolved`

#### Scenario: Partial DNS propagation
- **WHEN** only a subset of queried resolvers return the expected record value within the timeout threshold
- **THEN** the engine returns the calculated propagation percentage and marks unpropagated resolvers as `pending`

### Requirement: SSL and TLS Certificate Inspection
The engine SHALL perform a TLS handshake against the target domain on port 443, inspect the active certificate chain, expiration timestamps, issuer details, and Subject Alternative Names (SAN).

#### Scenario: Valid SSL certificate inspection
- **WHEN** the engine audits a domain with an active, unexpired certificate matching the domain name
- **THEN** the engine returns `ssl_status: "valid"`, days until expiration, issuer name, and SAN list

#### Scenario: Expired or hostname-mismatched certificate
- **WHEN** the engine audits a domain whose certificate is expired or does not cover the host domain
- **THEN** the engine returns `ssl_status: "invalid"` with the exact failure reason

### Requirement: Automated DNS Record Provisioning via Provider Drivers
The DNS engine SHALL accept provider credentials (e.g. Cloudflare API token, GoDaddy Key/Secret, AWS Route53 credentials) and invoke provider drivers (via `libdns`) to automatically append, update, or remove DNS records in the provider's zone.

#### Scenario: Successfully provisioning records via provider API
- **WHEN** the engine receives a `provision-dns` command with valid provider credentials, target zone, and record payload (A or CNAME)
- **THEN** the engine interacts with the provider API, creates the record, and returns a JSON response with `status: "provisioned"` and the provider record IDs

#### Scenario: Provider API authentication or permission error
- **WHEN** the engine attempts to provision records with an expired or unauthorized API token
- **THEN** the engine returns a JSON error response with `status: "error"` detailing the provider error code and message

#### Scenario: Removing records on domain disconnection
- **WHEN** the engine receives a `remove-dns` command for a previously provisioned domain
- **THEN** the engine instructs the provider API to delete the associated records and returns `status: "removed"`
