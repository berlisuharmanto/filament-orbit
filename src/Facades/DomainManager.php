<?php

namespace ProjectMoon\FilamentDomainManager\Facades;

use Illuminate\Support\Facades\Facade;
use ProjectMoon\FilamentDomainManager\Services\DnsEngineBridge;

/**
 * @method static array version()
 * @method static \ProjectMoon\FilamentDomainManager\DTOs\DnsCheckResult checkDns(\ProjectMoon\FilamentDomainManager\DTOs\DnsCheckRequest $request)
 * @method static \ProjectMoon\FilamentDomainManager\DTOs\SslAuditResult auditSsl(string $domain, int $port = 443, int $timeoutMs = 5000)
 * @method static \ProjectMoon\FilamentDomainManager\DTOs\ProviderProvisionResult provisionDns(\ProjectMoon\FilamentDomainManager\DTOs\ProviderProvisionRequest $request)
 * @method static \ProjectMoon\FilamentDomainManager\DTOs\ProviderProvisionResult removeDns(\ProjectMoon\FilamentDomainManager\DTOs\ProviderProvisionRequest $request)
 * @method static bool isInstalled()
 * @method static string getBinaryPath()
 *
 * @see \ProjectMoon\FilamentDomainManager\Services\DnsEngineBridge
 */
class DomainManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DnsEngineBridge::class;
    }
}
