## Purpose

Provides an embedded Laravel application environment in `playground/` for local testing, browser verification, and live demoing of the Filament Domain Manager plugin.

## Requirements

### Requirement: Local Path Package Linkage and Autoloading
The playground application SHALL link to the root package using a Composer `"path"` repository configuration, allowing instant code reload from the plugin source without requiring Packagist publishing.

#### Scenario: Installing dependencies in playground
- **WHEN** `composer install` is executed within `playground/`
- **THEN** Composer symlinks `project-moon/filament-domain-manager` from `../` and resolves all plugin classes and commands

### Requirement: Filament Admin Panel Bootstrapping and Plugin Registration
The playground SHALL configure an active Filament Admin Panel with `FilamentDomainManagerPlugin` registered, rendering the Domain Management resource and navigation items at `/admin/domains`.

#### Scenario: Admin accesses domain management in browser
- **WHEN** an administrator signs into `/admin` and navigates to "Domains"
- **THEN** the Domain resource table renders with status badges, connection mode options, and verification actions

### Requirement: Database Migrations and Admin User Seeder
The playground SHALL configure a local SQLite database, execute all package migrations, and provide a `UserSeeder` with an initial admin credential (`admin@example.com` / `password`).

#### Scenario: Bootstrapping playground database
- **WHEN** `php artisan migrate:fresh --seed` runs in the playground
- **THEN** the `users` and `domains` tables are created and seeded with test data ready for login
