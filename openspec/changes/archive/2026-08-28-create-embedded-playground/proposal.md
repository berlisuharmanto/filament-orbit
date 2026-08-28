## Why

Before publishing the Filament Multi-Tenancy Domain Manager package to Packagist and tagging official releases, developers need an interactive, full-stack Laravel playground to test plugin behaviors, panel providers, DNS verification flows, and UI modals in real browser sessions without altering host application setups.

This change introduces an embedded **Playground Application** in `playground/` with a complete Laravel 11 setup, Filament panel registration, SQLite/MySQL database configuration, and local path repository linkage to the root package.

## What Changes

* **Embedded Laravel 11 App (`playground/`)**: A dedicated Laravel application skeleton inside `playground/`.
* **Local Package Path Linkage**: `playground/composer.json` configured with a `"path"` repository pointing to `../`, symlinking the root package directly during development.
* **Filament Admin Panel Configuration**: `playground/app/Providers/Filament/AdminPanelProvider.php` configured with `FilamentDomainManagerPlugin::make()`.
* **Database & Seeder**: Pre-configured SQLite/MySQL database setup and `UserSeeder` with an initial admin account (`admin@example.com` / `password`) for immediate login.
* **Developer Scripts**: Helper commands (`composer playground:serve`, `composer playground:install`) added to the root package to run and refresh the playground effortlessly.

## Capabilities

### New Capabilities
- `embedded-playground`: Standalone, runnable Laravel application in `playground/` linking the local package for manual UI testing, browser verification, and live demo flows.

### Modified Capabilities
<!-- None -->

## Impact

* **Dependencies**: Adds Laravel 11 application dependencies isolated within `playground/`.
* **Filesystem**: Adds `playground/` directory (with `playground/vendor/` and `playground/.env` ignored in `.gitignore`).
* **Workflows**: Enables running `php artisan serve` inside `playground/` to preview `http://127.0.0.1:8000/admin`.
