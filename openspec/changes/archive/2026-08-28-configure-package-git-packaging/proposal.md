## Why

When publishing open-source Laravel and Filament packages to GitHub and Packagist, repository cleanliness and downstream archive distribution size are critical. 

This change refines the repository's git tracking boundaries to keep only lightweight playground source files in version control, ignores all heavy runtime assets (`vendor/`, `.env`, temporary databases, logs, binaries), and configures `.gitattributes` with `export-ignore` rules so that non-production assets (`playground/`, `tests/`, `openspec/`, `.agent/`) are completely excluded from downstream `composer require` downloads.

## What Changes

* **Selective Playground Git Tracking**: Ensures `playground/` tracks only essential code (`app/`, `bootstrap/`, `config/`, `database/seeders/`, `database/migrations/`, `composer.json`, `.env.example`, `artisan`, `public/index.php`, `routes/`, `tests/`) while strictly ignoring runtime artifacts.
* **Refined `.gitignore`**: Explicitly ignores `/playground/vendor/`, `/playground/node_modules/`, `/playground/.env`, `/playground/bin/`, `/playground/database/*.sqlite`, and all storage log/cache directories.
* **`.gitattributes` Archive Configuration**: Creates `.gitattributes` with `export-ignore` directives for `/playground`, `/tests`, `/openspec`, `/.agent`, and other dev-only assets.

## Capabilities

### New Capabilities
- `package-export-rules`: Clean gitignore boundaries and `.gitattributes` export-ignore configuration to keep source tracking lightweight and downstream Composer archive downloads minimal.

### Modified Capabilities
<!-- None -->

## Impact

* **Distribution**: Ensures `composer require project-moon/filament-domain-manager` remains lightweight (< 500KB) for consumers.
* **Repository**: Keeps git commits clean, avoiding accidental commits of runtime SQLite databases, vendor folders, or local secrets.
