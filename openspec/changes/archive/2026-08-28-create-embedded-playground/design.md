## Context

See `proposal.md` for motivation.

The project currently contains the completed `project-moon/filament-domain-manager` Composer package and Go DNS engine. To allow live browser testing and demoing, an embedded Laravel 11 application will be scaffolded in `playground/`.

## Goals / Non-Goals

**Goals:**
* Create a complete, standalone Laravel 11 application inside `playground/`.
* Configure `playground/composer.json` to require `project-moon/filament-domain-manager` via a `"path"` repository pointing to `../`.
* Register `FilamentDomainManagerPlugin` within `playground/app/Providers/Filament/AdminPanelProvider.php`.
* Provide a pre-seeded admin user (`admin@example.com` / `password`) for immediate login at `http://127.0.0.1:8000/admin`.
* Add root composer scripts to launch the playground server with a single command.

**Non-Goals:**
* Committing playground build artifacts or vendor files to the main package git tree (`playground/vendor` and `playground/.env` are ignored).
* Deploying the playground to production (playground is strictly for local dev/demo).

## Decisions

### 1. Composer Path Repository Linkage
* **Decision**: Configure `playground/composer.json` with:
  ```json
  "repositories": [
      {
          "type": "path",
          "url": "../",
          "options": {
              "symlink": true
          }
      }
  ],
  "require": {
      "project-moon/filament-domain-manager": "@dev"
  }
  ```
* **Rationale**: Symlinking `../` ensures any edits made to `src/` or `resources/` inside the plugin are immediately reflected when refreshing the playground browser without reinstalling.

### 2. Zero-Config Database with SQLite & Auto-Seeding
* **Decision**: Set `DB_CONNECTION=sqlite` with database file at `playground/database/database.sqlite`.
* **Rationale**: Developers can launch and test immediately without configuring MySQL or PostgreSQL credentials.

### 3. Filament Panel Provider Configuration
* **Decision**: Register the plugin in `playground/app/Providers/Filament/AdminPanelProvider.php` using standard `->plugin(FilamentDomainManagerPlugin::make())`.

## Risks / Trade-offs

* **[Risk] Path repository version resolution in Composer** → **Mitigation**: Require `"project-moon/filament-domain-manager": "@dev"` with `"minimum-stability": "dev"` in `playground/composer.json`.
* **[Risk] Git tracking bloated playground dependencies** → **Mitigation**: Ensure `.gitignore` ignores `playground/vendor/`, `playground/node_modules/`, and `playground/.env`.
