# Filament Multi-Tenancy Domain Manager

[![Latest Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/project-moon/filament-domain-manager)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE.md)
[![Filament](https://img.shields.io/badge/Filament-v3%20|%20v4%20|%20v5-orange.svg)](https://filamentphp.com)

A high-performance custom domain and DNS management plugin for **Filament Admin Panels** powered by a native compiled **Go DNS Engine** and the **Driver-Based DNS Connector Model**.

Allows SaaS platforms and multi-tenant applications to connect, automate, and verify custom domains with zero registrar-hopping for end-users, or via smart CNAME fallback with instant multi-resolver propagation checks.

---

## Key Features

* **Dual Connection Strategies**:
  * **Automated 1-Click Mode**: Integrates directly with DNS provider APIs (**Cloudflare**, **GoDaddy**, **AWS Route53**) to create, update, and remove CNAME/A records without opening registrar dashboards.
  * **Smart Manual Mode**: Tailored DNS instructions (CNAME / A records) for tenants with copyable instructions.
* **Native Compiled Go DNS Engine**:
  * Concurrent, sub-second DNS propagation verification across multiple global resolvers (`1.1.1.1`, `8.8.8.8`, `9.9.9.9`, `208.67.222.222`).
  * Live TLS/SSL certificate health auditing (chain verification, issuer, SANs, days remaining).
* **Safe CLI Process Bridge**:
  * Communicates via `Symfony\Component\Process\Process` over standard JSON I/O.
  * Zero PHP-FPM memory leaks, no required FFI extensions, runs anywhere standard PHP/Laravel runs.
* **Artisan Binary Installer**:
  * Automated `php artisan domain-manager:install-binary` command detecting OS family (Linux, macOS, Windows) and architecture (amd64, arm64).
* **Forward-Compatible Filament Plugin**:
  * Clean `FilamentDomainManagerPlugin` adhering to published Filament plugin standards across Filament v3, v4, and v5.

---

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                 Filament Admin Panel (PHP)                  │
├─────────────────────────────────────────────────────────────┤
│  • DomainResource: Table with DNS & SSL health badges       │
│  • Connection Mode Switcher: Automated (API) vs Manual CNAME│
│  • Instant Actions: "Verify DNS", "DNS Setup Instructions"  │
└──────────────────────────────┬──────────────────────────────┘
                               │
                      Symfony Process (JSON)
                               │
                               ▼
┌─────────────────────────────────────────────────────────────┐
│                 Compiled Go Engine (Headless)               │
├─────────────────────────────────────────────────────────────┤
│  ┌───────────────────────┐  ┌─────────────────────────────┐ │
│  │ Multi-Resolver Engine │  │ DNS Provider Drivers        │ │
│  │ (Concurrent UDP/TCP)  │  │ (Cloudflare, GoDaddy, etc.) │ │
│  └───────────────────────┘  └─────────────────────────────┘ │
│  ┌───────────────────────┐  ┌─────────────────────────────┐ │
│  │ TLS / SSL Inspector   │  │ Cross-Platform Distribution │ │
│  │ (SAN, Expiry Auditing)│  │ (Linux, macOS, Win / arm64) │ │
│  └───────────────────────┘  └─────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

---

## Quick Start Guide

### 1. Installation via Composer

```bash
composer require project-moon/filament-domain-manager
```

### 2. Install the Native Go Binary

Run the Artisan installer to detect your server architecture and place the executable:

```bash
php artisan domain-manager:install-binary
```

*(Optional: Add `php artisan domain-manager:install-binary` to your `composer.json` `post-autoload-dump` or deployment pipeline).*

### 3. Run Migrations & Publish Configuration

```bash
php artisan vendor:publish --tag="filament-domain-manager-config"
php artisan migrate
```

### 4. Register Plugin in Filament Panel Provider

In your panel provider (e.g. `app/Providers/Filament/AdminPanelProvider.php`):

```php
use ProjectMoon\FilamentDomainManager\FilamentDomainManagerPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->default()
        ->id('admin')
        ->path('admin')
        ->plugin(FilamentDomainManagerPlugin::make());
}
```

---

## Configuration (`config/domain-manager.php`)

```php
return [
    // Ingress CNAME target presented to tenants for manual setup
    'ingress_target' => env('DOMAIN_MANAGER_INGRESS_TARGET', 'ingress.mysaas.com'),
    
    // Ingress server IP presented for root apex domains
    'ingress_ip' => env('DOMAIN_MANAGER_INGRESS_IP', '198.51.100.1'),
    
    // Global resolvers for propagation checks
    'resolvers' => [
        '1.1.1.1:53',
        '8.8.8.8:53',
        '9.9.9.9:53',
        '208.67.222.222:53',
    ],
    
    // Auto audit SSL when DNS is verified
    'auto_ssl_audit' => true,
    
    // Global Provider Credentials (optional)
    'providers' => [
        'cloudflare' => [
            'api_token' => env('CLOUDFLARE_DNS_API_TOKEN', ''),
        ],
        'godaddy' => [
            'key' => env('GODADDY_API_KEY', ''),
            'secret' => env('GODADDY_API_SECRET', ''),
        ],
    ],
];
```

---

## Programmatic Usage via Facade

```php
use ProjectMoon\FilamentDomainManager\Facades\DomainManager;
use ProjectMoon\FilamentDomainManager\DTOs\DnsCheckRequest;
use ProjectMoon\FilamentDomainManager\DTOs\RecordExpectation;

// Check DNS Records & Propagation
$result = DomainManager::checkDns(new DnsCheckRequest(
    domain: 'shop.tenant.com',
    expectedRecords: [
        RecordExpectation::cname('ingress.mysaas.com'),
    ]
));

if ($result->isVerified()) {
    // 100% propagated and matched!
}

// Audit TLS / SSL Certificate
$ssl = DomainManager::auditSsl('shop.tenant.com');
echo "SSL is {$ssl->status}, valid for {$ssl->daysRemaining} days.";
```

---

## Compiling from Source

If you want to build the Go binary from source:

```bash
cd engine
./build.sh
```

Compiled binaries will be generated for `linux-amd64`, `linux-arm64`, `darwin-amd64`, `darwin-arm64`, and `windows-amd64` in `bin/dist/`.

---

## License

The MIT License (MIT). Please see [LICENSE.md](LICENSE.md) for more information.
