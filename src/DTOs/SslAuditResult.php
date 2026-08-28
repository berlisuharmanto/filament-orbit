<?php

namespace ProjectMoon\FilamentDomainManager\DTOs;

class SslAuditResult
{
    public function __construct(
        public bool $success,
        public string $status, // "valid", "expiring_soon", "expired", "mismatch", "error"
        public string $domain,
        public ?string $issuer = null,
        public ?string $subject = null,
        public array $sans = [],
        public ?string $validFrom = null,
        public ?string $validTo = null,
        public int $daysRemaining = 0,
        public bool $isExpired = false,
        public ?string $errorMessage = null,
        public string $checkedAt = '',
        public array $raw = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            success: (bool) ($data['success'] ?? false),
            status: $data['status'] ?? 'error',
            domain: $data['domain'] ?? '',
            issuer: $data['issuer'] ?? null,
            subject: $data['subject'] ?? null,
            sans: $data['sans'] ?? [],
            validFrom: $data['valid_from'] ?? null,
            validTo: $data['valid_to'] ?? null,
            daysRemaining: (int) ($data['days_remaining'] ?? 0),
            isExpired: (bool) ($data['is_expired'] ?? false),
            errorMessage: $data['error_message'] ?? null,
            checkedAt: $data['checked_at'] ?? now()->toIso8601String(),
            raw: $data,
        );
    }

    public function isValid(): bool
    {
        return in_array($this->status, ['valid', 'expiring_soon'], true);
    }
}
