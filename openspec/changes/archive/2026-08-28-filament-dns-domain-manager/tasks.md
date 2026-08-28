## 1. Package Skeleton & Environment Setup

- [x] 1.1 Initialize Composer package configuration (`composer.json`) with dependencies for Filament v3+, `symfony/process`, and service provider registration, and verify using `composer validate`.
- [x] 1.2 Initialize Go module in `engine/` with `go.mod` and verify compilation toolchain with `go version`.

## 2. Go DNS Engine & Provider Drivers Implementation

- [x] 2.1 Implement Go CLI command structure and JSON input/output handling (`check`, `audit-ssl`, `version`) and verify CLI flag and stdin parsing.
- [x] 2.2 Implement concurrent DNS record resolution across authoritative and public resolvers (1.1.1.1, 8.8.8.8) with propagation calculation and verify via Go unit tests.
- [x] 2.3 Implement TLS handshake inspection for SSL certificate validity, issuer, SANs, and expiration countdown and verify with Go tests.
- [x] 2.4 Integrate `libdns` provider drivers (Cloudflare, GoDaddy, AWS Route53) and implement `provision-dns` and `remove-dns` subcommands, verifying with Go provider unit tests.
- [x] 2.5 Add cross-compilation build scripts (`build.sh` or `Makefile`) targeting Linux, macOS, and Windows on amd64 and arm64 and verify artifact generation.

## 3. PHP Process Bridge Service

- [x] 3.1 Implement `DnsEngineBridge` service wrapping `Symfony\Component\Process\Process` to invoke the Go binary with JSON payloads and error handling, verifying with unit tests.
- [x] 3.2 Implement Data Transfer Objects (DTOs) for DNS check requests, provider provisioning requests, and structured verification results, verifying serialization with Pest tests.
- [x] 3.3 Add bridge methods for automated record provisioning (`provisionDns`, `removeDns`) and credential validation, verifying with integration tests.

## 4. Artisan Binary Installer Command

- [x] 4.1 Implement `InstallBinaryCommand` (`domain-manager:install-binary`) with OS family and CPU architecture auto-detection, verifying platform resolution logic.
- [x] 4.2 Implement binary file placement, `chmod 0755` permission setting, `--force` flag support, and `--version` sanity check, verifying command execution via Artisan.

## 5. Filament Admin Panel Integration (v3 to v5)

- [x] 5.1 Create database migration and Eloquent models for domains, connection modes (automated vs manual), and encrypted provider credentials, verifying with `artisan migrate:status`.
- [x] 5.2 Implement `FilamentDomainManagerPlugin` class implementing `Filament\Contracts\Plugin` for forward-compatible panel registration and verify registration in Panel configuration.
- [x] 5.3 Implement `DomainResource` with list table, connection mode badges, DNS status badges, and SSL health indicators, verifying table rendering.
- [x] 5.4 Implement domain creation/edit form with Connection Mode switcher (Automated Provider API vs. Manual Smart CNAME) and provider credentials selection, verifying form validation.
- [x] 5.5 Implement automated domain provisioning lifecycle on domain creation and deletion, triggering the Go provider driver and verifying database state updates.
- [x] 5.6 Implement on-demand "Verify DNS" table action and "DNS Setup Instructions" modal action for manual fallback domains, verifying modal and notification output.

## 6. Documentation & Integration Verification

- [x] 6.1 Author comprehensive "Getting Started" documentation covering Composer installation, running `php artisan domain-manager:install-binary`, provider API setup, and panel provider configuration.
- [x] 6.2 Execute end-to-end test suite verifying the installer, provider DNS provisioning via the CLI bridge, and Filament resource actions.
