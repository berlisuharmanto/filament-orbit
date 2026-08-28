<?php

namespace ProjectMoon\FilamentDomainManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProjectMoon\FilamentDomainManager\DTOs\DnsCheckRequest;
use ProjectMoon\FilamentDomainManager\DTOs\DnsCheckResult;
use ProjectMoon\FilamentDomainManager\DTOs\ProviderProvisionRequest;
use ProjectMoon\FilamentDomainManager\DTOs\ProviderProvisionResult;
use ProjectMoon\FilamentDomainManager\DTOs\ProviderRecord;
use ProjectMoon\FilamentDomainManager\DTOs\RecordExpectation;
use ProjectMoon\FilamentDomainManager\DTOs\SslAuditResult;
use ProjectMoon\FilamentDomainManager\Facades\DomainManager;

class Domain extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'dns_records_data' => 'array',
        'provider_credentials' => 'encrypted:array',
        'dns_last_checked_at' => 'datetime',
        'ssl_valid_to' => 'datetime',
        'ssl_last_checked_at' => 'datetime',
        'ssl_days_remaining' => 'integer',
    ];

    /**
     * Get the expected DNS records for this domain (CNAME or A record).
     *
     * @return RecordExpectation[]
     */
    public function getExpectedRecords(): array
    {
        $ingressTarget = config('domain-manager.ingress_target', 'ingress.example.com');
        $ingressIp = config('domain-manager.ingress_ip', '192.0.2.1');

        // If apex domain (e.g. example.com), recommend A record; if subdomain, recommend CNAME
        $parts = explode('.', $this->domain);
        if (count($parts) <= 2) {
            return [
                RecordExpectation::a($ingressIp),
                RecordExpectation::cname($ingressTarget),
            ];
        }

        return [
            RecordExpectation::cname($ingressTarget),
        ];
    }

    /**
     * Verify DNS records via the Go engine bridge.
     */
    public function verifyDns(): DnsCheckResult
    {
        $request = new DnsCheckRequest(
            domain: $this->domain,
            expectedRecords: $this->getExpectedRecords(),
            resolvers: config('domain-manager.resolvers', []),
        );

        $result = DomainManager::checkDns($request);

        $this->update([
            'dns_status' => $result->status,
            'dns_last_checked_at' => now(),
            'dns_records_data' => $result->raw,
        ]);

        if (config('domain-manager.auto_ssl_audit', true)) {
            $this->auditSsl();
        }

        return $result;
    }

    /**
     * Audit TLS/SSL certificate status via the Go engine bridge.
     */
    public function auditSsl(): SslAuditResult
    {
        $result = DomainManager::auditSsl($this->domain);

        $this->update([
            'ssl_status' => $result->status,
            'ssl_issuer' => $result->issuer,
            'ssl_valid_to' => $result->validTo ? carbon($result->validTo) : null,
            'ssl_days_remaining' => $result->daysRemaining,
            'ssl_last_checked_at' => now(),
        ]);

        return $result;
    }

    /**
     * Provision DNS records with the selected provider API.
     */
    public function provisionWithProvider(): ProviderProvisionResult
    {
        if ($this->connection_mode !== 'auto' || empty($this->provider)) {
            return new ProviderProvisionResult(
                success: false,
                status: 'error',
                provider: $this->provider ?? '',
                zone: $this->domain,
                errorMessage: 'Automated connection mode and provider are required.'
            );
        }

        $creds = $this->provider_credentials ?? [];
        $token = $creds['api_token'] ?? $creds['key'] ?? config("domain-manager.providers.{$this->provider}.api_token", '');
        $secret = $creds['secret'] ?? config("domain-manager.providers.{$this->provider}.secret", '');

        // Extract sub/zone
        $zone = $this->domain;
        $name = '@';
        $parts = explode('.', $this->domain);
        if (count($parts) > 2) {
            $name = array_shift($parts);
            $zone = implode('.', $parts);
        }

        $ingressTarget = config('domain-manager.ingress_target', 'ingress.example.com');
        $records = [
            ProviderRecord::cname($name, $ingressTarget, 300),
        ];

        $request = new ProviderProvisionRequest(
            provider: $this->provider,
            zone: $zone,
            records: $records,
            authToken: $token,
            authSecret: $secret,
            zoneId: $this->provider_zone_id,
        );

        $result = DomainManager::provisionDns($request);

        if ($result->success) {
            $created = $result->createdRecords[0] ?? null;
            $this->update([
                'provider_record_id' => $created?->id,
                'dns_status' => 'verified',
                'dns_last_checked_at' => now(),
            ]);
        } else {
            $this->update([
                'dns_status' => 'failed',
                'dns_last_checked_at' => now(),
            ]);
        }

        return $result;
    }

    /**
     * Remove DNS records from provider when domain is deleted or disconnected.
     */
    public function removeWithProvider(): ProviderProvisionResult
    {
        if ($this->connection_mode !== 'auto' || empty($this->provider)) {
            return new ProviderProvisionResult(
                success: true,
                status: 'skipped',
                provider: $this->provider ?? '',
                zone: $this->domain,
            );
        }

        $creds = $this->provider_credentials ?? [];
        $token = $creds['api_token'] ?? $creds['key'] ?? config("domain-manager.providers.{$this->provider}.api_token", '');
        $secret = $creds['secret'] ?? config("domain-manager.providers.{$this->provider}.secret", '');

        $zone = $this->domain;
        $name = '@';
        $parts = explode('.', $this->domain);
        if (count($parts) > 2) {
            $name = array_shift($parts);
            $zone = implode('.', $parts);
        }

        $records = [
            new ProviderRecord(type: 'CNAME', name: $name, value: '', id: $this->provider_record_id),
        ];

        $request = new ProviderProvisionRequest(
            provider: $this->provider,
            zone: $zone,
            records: $records,
            authToken: $token,
            authSecret: $secret,
            zoneId: $this->provider_zone_id,
        );

        return DomainManager::removeDns($request);
    }
}
