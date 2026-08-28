## 1. Git Ignore & Runtime Boundary Configuration

- [x] 1.1 Update `.gitignore` to explicitly ignore `/playground/bin/`, `/playground/storage/**`, `/playground/database/*.sqlite`, `/playground/.env`, and `/playground/vendor/`, and verify with `git status`.
- [x] 1.2 Update root `composer.json` `playground:install` script to ensure `.env.example` copying and key generation.

## 2. Git Attributes & Distribution Rules

- [x] 2.1 Create `.gitattributes` marking `/playground`, `/tests`, `/.agent`, `/openspec`, and `/engine` with `export-ignore` and verify with `git check-attr -a playground/composer.json`.
- [x] 2.2 Verify git status reflects a clean tracked source tree without runtime junk.
