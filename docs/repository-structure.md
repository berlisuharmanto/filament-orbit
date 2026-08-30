# Technical Documentation: Repository File & Directory Structure

> **Authoritative Guide on Version Control Boundaries, Git Ignore Rules, and Composer Distribution Packaging**

---

## 1. Overview & Architecture

Project Moon is a **Hybrid Go + PHP Filament Plugin** with an embedded **Laravel 11 Developer Playground Sandbox**. Because it contains compiled multi-platform Go binaries, local development sandboxes, and automated testbeds, strict boundaries are enforced at three distinct levels:

1. **Tracked in Git**: Essential source code, schema migrations, seeders, configuration files, test suites, and pre-compiled static release binaries in `bin/dist/`.
2. **Ignored in Git (`.gitignore`)**: Local environment secrets (`.env`), third-party dependency trees (`vendor/`), dynamic binary build outputs, runtime SQLite databases, logs, and framework caches.
3. **Excluded from Distribution (`.gitattributes export-ignore`)**: Development testbeds, the Go engine source, test suites, and planning specifications that are stripped when downstream users install the package via `composer require`.

---

## 2. Directory Tree Map

```
project-moon/
├── .agent/                             # AI Agent workflows & skills [TRACKED, EXPORT-IGNORED]
├── .gitattributes                      # Composer release archive filters [TRACKED, EXPORT-IGNORED]
├── .gitignore                          # Git repository ignore boundaries [TRACKED, EXPORT-IGNORED]
├── bin/
│   ├── dist/                           # Cross-compiled static binaries [TRACKED]
│   │   ├── dns-manager-darwin-amd64
│   │   ├── dns-manager-darwin-arm64
│   │   ├── dns-manager-linux-amd64
│   │   ├── dns-manager-linux-arm64
│   │   └── dns-manager-windows-amd64.exe
│   └── dns-manager                     # Host active binary [IGNORED by .gitignore]
├── config/
│   └── domain-manager.php              # Default package configuration [TRACKED]
├── database/
│   └── migrations/
│       └── create_domains_table.php.stub # Database migration stub [TRACKED]
├── docs/                               # Technical documentation [TRACKED, EXPORT-IGNORED]
│   └── repository-structure.md
├── engine/                             # Go DNS & SSL Engine Source [TRACKED, EXPORT-IGNORED]
│   ├── cmd/dns-manager/main.go
│   ├── pkg/
│   │   ├── dnscheck/checker.go
│   │   ├── sslcheck/checker.go
│   │   └── provider/ (cloudflare, godaddy, mock)
│   ├── go.mod, go.sum, build.sh
├── openspec/                           # OpenSpec specifications & archives [TRACKED, EXPORT-IGNORED]
│   ├── specs/ (5 capabilities)
│   └── changes/archive/
├── playground/                         # Embedded Laravel 11 Dev Sandbox [LIGHTWEIGHT TRACKED, EXPORT-IGNORED]
│   ├── app/ (Models, Providers, Filament Panel) [TRACKED]
│   ├── bootstrap/app.php, providers.php [TRACKED]
│   ├── config/ [TRACKED]
│   ├── database/migrations/, seeders/ [TRACKED]
│   ├── public/index.php [TRACKED]
│   ├── routes/web.php, console.php [TRACKED]
│   ├── tests/Feature/PlaygroundTest.php [TRACKED]
│   ├── composer.json, phpunit.xml [TRACKED]
│   ├── .env.example [TRACKED]
│   ├── .env [IGNORED]
│   ├── bin/ [IGNORED]
│   ├── database/*.sqlite [IGNORED]
│   ├── storage/** [IGNORED]
│   └── vendor/ [IGNORED]
├── resources/
│   └── views/filament/modals/          # Blade modals (DNS setup instructions) [TRACKED]
├── src/                                # Core PHP Package Source [TRACKED]
│   ├── Commands/InstallBinaryCommand.php
│   ├── DTOs/ (DnsCheckRequest, DnsCheckResult, SslAuditResult, etc.)
│   ├── Facades/DomainManager.php
│   ├── Models/Domain.php
│   ├── Providers/DomainManagerServiceProvider.php
│   ├── Resources/DomainResource.php (Filament Panel)
│   └── Services/DnsEngineBridge.php
├── tests/                              # Package Unit & Feature Pest Tests [TRACKED, EXPORT-IGNORED]
├── composer.json                       # Root package definition [TRACKED]
├── LICENSE.md, README.md, summary.md   # Documentation [TRACKED]
└── phpunit.xml                         # Root test configuration [TRACKED, EXPORT-IGNORED]
```

---

## 3. The 3-Tier Inclusion & Exclusion Matrix

| Path / Pattern | Tracked in Git? | Ignored in `.gitignore`? | Excluded in `.gitattributes`? | Architectural Purpose |
| :--- | :---: | :---: | :---: | :--- |
| **`src/**`** | ✅ Yes | ❌ No | ❌ No (Included in release) | Core PHP package logic, Filament Resource, Eloquent models, and DTOs. |
| **`config/**`** | ✅ Yes | ❌ No | ❌ No (Included in release) | Default package configuration published via `artisan vendor:publish`. |
| **`database/migrations/**`** | ✅ Yes | ❌ No | ❌ No (Included in release) | Package database migrations published to host Laravel applications. |
| **`resources/views/**`** | ✅ Yes | ❌ No | ❌ No (Included in release) | Blade views and Filament setup instruction modals. |
| **`bin/dist/**`** | ✅ Yes | ❌ No | ❌ No (Included in release) | Pre-compiled static Go binaries distributed with the package so end users don't need a Go compiler. |
| **`bin/dns-manager`** | ❌ No | ✅ Yes (`/bin/dns-manager`) | N/A | Local active host binary created dynamically by `php artisan domain-manager:install-binary`. |
| **`engine/**`** | ✅ Yes | ❌ No | ✅ Yes (`/engine/** export-ignore`) | Go source code for the DNS engine. Kept in repo for development; stripped from end-user Composer downloads. |
| **`tests/**`** | ✅ Yes | ❌ No | ✅ Yes (`/tests/** export-ignore`) | Pest/PHPUnit test suite for the package. Not needed at production runtime. |
| **`docs/**`** | ✅ Yes | ❌ No | ✅ Yes (`/docs/** export-ignore`) | Technical documentation and architectural guides. |
| **`openspec/**`** | ✅ Yes | ❌ No | ✅ Yes (`/openspec/** export-ignore`) | OpenSpec specification files and change archives. |
| **`.agent/**`** | ✅ Yes | ❌ No | ✅ Yes (`/.agent/** export-ignore`) | Agent instructions, skills, and workflow templates. |
| **`playground/app/**`** | ✅ Yes | ❌ No | ✅ Yes (`/playground/** export-ignore`) | Laravel 11 sandbox application code for local testing and live demos. |
| **`playground/database/*.sqlite`** | ❌ No | ✅ Yes | N/A | Temporary local test databases; never committed. |
| **`playground/storage/**`** | ❌ No | ✅ Yes | N/A | Runtime logs, framework caches, and session data. |
| **`playground/vendor/**`** | ❌ No | ✅ Yes | N/A | Sandbox third-party Composer dependencies installed locally. |
| **`playground/.env`** | ❌ No | ✅ Yes | N/A | Sandbox local environment credentials (DB passwords, app keys). |

---

## 4. Deep-Dive: Layer Responsibilities

### 4.1 The Core PHP Plugin Layer (`src/`, `config/`, `database/`, `resources/`)
* **What is tracked**: All PHP classes, facades, service providers, Filament resources, Blade views, and migrations.
* **Release Status**: Fully included when a user runs `composer require project-moon/filament-domain-manager`.
* **Zero Bloat**: Contains no heavy development dependencies.

### 4.2 The Compiled Go DNS Engine Layer (`engine/`, `bin/`)
* **Source vs Distribution**:
  * The Go source code (`engine/`) is version-controlled so developers can compile and extend drivers.
  * The pre-compiled static binaries (`bin/dist/dns-manager-*`) are version-controlled and distributed in the package.
  * The active host binary (`bin/dns-manager`) is created dynamically during installation (`php artisan domain-manager:install-binary`) and is strictly ignored in Git.

### 4.3 The Embedded Playground Sandbox (`playground/`)
* **Source vs Runtime**:
  * Only essential application skeleton files are tracked (`app/`, `bootstrap/`, `config/`, `database/seeders/`, `database/migrations/`, `routes/`, `public/`, `tests/`, `composer.json`, `.env.example`, `phpunit.xml`, `artisan`).
  * All runtime junk (`vendor/`, `.env`, `bin/`, `database/*.sqlite`, `storage/**`) is strictly ignored in `.gitignore`.
  * The entire `/playground/**` folder is stripped from Composer release archives via `.gitattributes`.

---

## 5. Contributing & Git Hygiene Commands

### Verify Clean Working Tree
Before submitting commits or pull requests, verify that no runtime files have leaked into the untracked list:
```bash
git status -uall playground/
```
*Expected output*: `nothing added to commit but untracked files present` (or only new explicit source files).

### Testing Composer Release Archive
To verify that downstream users receive only runtime package files without tests, engine sources, or playground files:
```bash
git archive --format=zip HEAD -o test-release.zip
unzip -l test-release.zip
```
*Verification*: Confirm that `playground/`, `engine/`, `tests/`, and `openspec/` are **not present** in the zip output.
