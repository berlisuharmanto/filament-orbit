<?php

namespace ProjectMoon\FilamentDomainManager\DTOs;

class DnsCheckResult
{
    /**
     * @param RecordResult[] $records
     */
    public function __construct(
        public bool $success,
        public string $status, // "verified", "pending", "failed"
        public string $domain,
        public array $records,
        public PropagationResult $propagation,
        public string $checkedAt,
        public array $raw = [],
    ) {}

    public static function fromArray(array $data): self
    {
        $records = [];
        if (!empty($data['records']) && is_array($data['records'])) {
            foreach ($data['records'] as $r) {
                $records[] = RecordResult::fromArray($r);
            }
        }

        return new self(
            success: (bool) ($data['success'] ?? false),
            status: $data['status'] ?? 'failed',
            domain: $data['domain'] ?? '',
            records: $records,
            propagation: PropagationResult::fromArray($data['propagation'] ?? []),
            checkedAt: $data['checked_at'] ?? now()->toIso8601String(),
            raw: $data,
        );
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
