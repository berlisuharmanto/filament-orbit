## Purpose

Provides an automated Artisan command to detect host platform architecture, install or update the compiled DNS manager binary, and verify executable permissions.

## Requirements

### Requirement: Architecture and OS Auto-Detection
The Artisan command SHALL detect the host operating system family and CPU architecture, resolving the appropriate binary asset artifact name.

#### Scenario: Supported platform detection
- **WHEN** the command `php artisan domain-manager:install-binary` runs on Linux x86_64
- **THEN** it resolves the target asset identifier to `linux-amd64`

#### Scenario: Unsupported platform detection
- **WHEN** the command runs on an unsupported operating system or architecture
- **THEN** the command halts with a descriptive error message and instructions for building from source

### Requirement: Binary Installation and Execution Verification
The installer SHALL place the binary into the designated binary storage path, ensure executable permissions (`0755` on Unix-like environments), and execute a health check (`--version`) to confirm operation.

#### Scenario: Successful binary installation and health check
- **WHEN** the installer places the binary and runs the verification test
- **THEN** it receives a valid JSON version response and reports a success message in the console

#### Scenario: Verification failure
- **WHEN** the binary cannot execute due to missing permissions or host dynamic link issues
- **THEN** the installer emits an actionable error with troubleshooting guidance and non-zero exit code

### Requirement: Overwrite and Version Updates
The installer command SHALL allow updating or re-installing the binary with a `--force` flag when a binary is already present.

#### Scenario: Force reinstall
- **WHEN** an existing binary is detected and `--force` is supplied
- **THEN** the existing file is replaced with the newly downloaded/copied binary
