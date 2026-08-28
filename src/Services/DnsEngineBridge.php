<?php

namespace ProjectMoon\FilamentDomainManager\Services;

use Illuminate\Support\Facades\File;
use ProjectMoon\FilamentDomainManager\DTOs\DnsCheckRequest;
use ProjectMoon\FilamentDomainManager\DTOs\DnsCheckResult;
use ProjectMoon\FilamentDomainManager\DTOs\ProviderProvisionRequest;
use ProjectMoon\FilamentDomainManager\DTOs\ProviderProvisionResult;
use ProjectMoon\FilamentDomainManager\DTOs\SslAuditResult;
use ProjectMoon\FilamentDomainManager\Exceptions\BinaryNotFoundException;
use ProjectMoon\FilamentDomainManager\Exceptions\DnsEngineException;
use Symfony\Component\Process\Process;

class DnsEngineBridge
{
    protected ?string $binaryPath;

    public function __construct(?string $binaryPath = null)
    {
        $this->binaryPath = $binaryPath;
    }

    /**
     * Resolve the active binary executable path.
     */
    public function getBinaryPath(): string
    {
        if ($this->binaryPath && File::exists($this->binaryPath)) {
            return $this->binaryPath;
        }

        $candidates = [
            config('domain-manager.binary_path'),
            base_path('bin/dns-manager'),
            storage_path('app/bin/dns-manager'),
            base_path('vendor/bin/dns-manager'),
            dirname(__DIR__, 2) . '/bin/dns-manager',
        ];

        foreach ($candidates as $candidate) {
            if ($candidate && File::exists($candidate)) {
                return $candidate;
            }
        }

        // Return first configured or default if not yet installed
        return config('domain-manager.binary_path', base_path('bin/dns-manager'));
    }

    /**
     * Check if the native binary is installed and executable.
     */
    public function isInstalled(): bool
    {
        $path = $this->getBinaryPath();
        return File::exists($path) && is_executable($path);
    }

    /**
     * Get version information from the compiled Go binary.
     */
    public function version(): array
    {
        return $this->execute('version', []);
    }

    /**
     * Validate DNS records for a domain against expectations.
     */
    public function checkDns(DnsCheckRequest $request): DnsCheckResult
    {
        $data = $this->execute('check', $request->toArray());
        return DnsCheckResult::fromArray($data);
    }

    /**
     * Inspect and audit the TLS/SSL certificate status of a domain.
     */
    public function auditSsl(string $domain, int $port = 443, int $timeoutMs = 5000): SslAuditResult
    {
        $data = $this->execute('audit-ssl', [
            'domain' => $domain,
            'port' => $port,
            'timeout_ms' => $timeoutMs,
        ]);

        return SslAuditResult::fromArray($data);
    }

    /**
     * Automatically create/update DNS records via a provider driver.
     */
    public function provisionDns(ProviderProvisionRequest $request): ProviderProvisionResult
    {
        $data = $this->execute('provision-dns', $request->toArray());
        return ProviderProvisionResult::fromArray($data);
    }

    /**
     * Automatically delete DNS records via a provider driver.
     */
    public function removeDns(ProviderProvisionRequest $request): ProviderProvisionResult
    {
        $data = $this->execute('remove-dns', $request->toArray());
        return ProviderProvisionResult::fromArray($data);
    }

    /**
     * Low-level command execution via Symfony Process.
     */
    public function execute(string $subcommand, array $payload, int $timeout = 30): array
    {
        $binary = $this->getBinaryPath();

        if (!File::exists($binary)) {
            throw BinaryNotFoundException::create($binary);
        }

        $inputJson = !empty($payload) ? json_encode($payload, JSON_UNESCAPED_SLASHES) : '';

        $command = [$binary, $subcommand];
        if (!empty($inputJson)) {
            $command[] = "--input={$inputJson}";
        }

        $process = new Process($command);
        $process->setTimeout($timeout);
        $process->run();

        $output = trim($process->getOutput());

        if (!$process->isSuccessful() && empty($output)) {
            throw DnsEngineException::executionFailed(
                $subcommand,
                $process->getErrorOutput() ?: 'Unknown error',
                $process->getExitCode() ?? 1
            );
        }

        $decoded = json_decode($output, true);
        if ($decoded === null && !empty($output)) {
            throw DnsEngineException::invalidJson($output);
        }

        return $decoded ?? [];
    }
}
