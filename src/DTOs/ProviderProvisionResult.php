<?php

namespace ProjectMoon\FilamentDomainManager\DTOs;

class ProviderProvisionResult
{
    /**
     * @param ProviderRecord[] $createdRecords
     */
    public function __construct(
        public bool $success,
        public string $status, // "provisioned", "removed", "error"
        public string $provider,
        public string $zone,
        public array $createdRecords = [],
        public ?string $errorMessage = null,
        public string $processedAt = '',
        public array $raw = [],
    ) {}

    public static function fromArray(array $data): self
    {
        $created = [];
        if (!empty($data['created_records']) && is_array($data['created_records'])) {
            foreach ($data['created_records'] as $r) {
                $created[] = ProviderRecord::fromArray($r);
            }
        } elseif (!empty($data['deleted_records']) && is_array($data['deleted_records'])) {
            foreach ($data['deleted_records'] as $r) {
                $created[] = ProviderRecord::fromArray($r);
            }
        }

        return new self(
            success: (bool) ($data['success'] ?? false),
            status: $data['status'] ?? 'error',
            provider: $data['provider'] ?? '',
            zone: $data['zone'] ?? '',
            createdRecords: $created,
            errorMessage: $data['error_message'] ?? null,
            processedAt: $data['processed_at'] ?? now()->toIso8601String(),
            raw: $data,
        );
    }
}
