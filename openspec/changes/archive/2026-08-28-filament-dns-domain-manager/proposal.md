## Why

Modern multi-tenant SaaS applications need reliable, fast, and secure custom domain management. Traditionally, connecting a custom domain requires non-technical tenants to navigate complex third-party registrar control panels (Cloudflare, GoDaddy, Namecheap) to manually configure A and CNAME records, resulting in configuration errors, support overhead, and high friction.

This change introduces the foundational architecture of the **Filament Multi-Tenancy Domain Manager** with the **Driver-Based DNS Connector Model**: combining high-speed native DNS/SSL validation in Go, automated DNS record provisioning via provider APIs (`libdns` for Cloudflare, GoDaddy, Route53, etc.) and Domain Connect OAuth, an automated Artisan binary installer, and a forward-compatible Filament Admin Panel interface spanning Filament v3 through v5.

## What Changes

* **Go Core Engine with DNS Provider Drivers (`dns-engine-binary`)**: A compiled, headless Go CLI tool that not only executes concurrent DNS validation, propagation checks, and SSL audits, but also implements **DNS Provider Drivers** (utilizing `libdns` for Cloudflare, GoDaddy, AWS Route53) to programmatically create, update, and remove DNS records without requiring users to log into external registrar dashboards.
* **Driver-Based DNS Connector & Smart CNAME**: A dual-mode connection architecture:
  * *Automated Mode*: Users supply an API token or use 1-click authorization, allowing the engine to provision required records directly into their DNS zone.
  * *Smart Manual Mode*: Fallback for registrars without APIs, presenting tailored CNAME/A instructions with instant multi-resolver verification.
* **PHP CLI Process Bridge**: A robust PHP service layer wrapping `Symfony\Component\Process\Process` to invoke the Go binary safely with JSON payloads over standard I/O.
* **Artisan Binary Installer (`artisan-binary-installer`)**: An interactive and scriptable command (`php artisan domain-manager:install-binary`) to detect host OS/Arch, install pre-compiled binaries, and verify executable permissions.
* **Filament Admin Panel Plugin (`filament-domain-management`)**: Filament resources and pages providing:
  * Connection mode switcher (1-Click Automated vs. Manual CNAME)
  * Provider credentials & integration settings (with encrypted token storage)
  * Real-time DNS verification and SSL health badges
  * Forward compatibility across Filament v3, v4, and v5
* **Documentation**: "Getting Started" guide covering package installation, binary deployment via Artisan, provider credentials setup, and panel registration.

## Capabilities

### New Capabilities
- `dns-engine-binary`: Headless compiled Go binary implementing concurrent DNS validation, SSL inspection, and automated DNS record provisioning via provider drivers (`libdns`) over CLI JSON commands.
- `artisan-binary-installer`: Artisan command to automatically detect environment architecture, download or install the appropriate binary, and verify executable permissions and checksums.
- `filament-domain-management`: Filament Admin Panel plugin components, domain resources, connection mode workflows (API-automated vs. manual CNAME), and status monitoring compatible from Filament v3 up to v5.

### Modified Capabilities
<!-- None: Greenfield project -->

## Impact

* **Dependencies**: Adds Go build pipeline with `libdns` provider modules; requires `symfony/process` and `filament/filament` (v3+) in PHP/Composer.
* **APIs**: Extends CLI JSON protocol between PHP bridge and Go binary (`validate-dns`, `provision-dns`, `remove-dns`, `audit-ssl`, `version`).
* **Systems**: Enables zero-touch, automated domain onboarding directly from Filament, eliminating the need for tenants or admins to open external registrar dashboards.
