<?php

namespace ProjectMoon\FilamentDomainManager\DTOs;

class RecordResult
{
    public function __construct(
        public string $type,
        public string $target,
        public bool $matched,
        public array $resolved = [],
        public ?string $message = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'] ?? '',
            target: $data['target'] ?? '',
            matched: (bool) ($data['matched'] ?? false),
            resolved: $data['resolved'] ?? [],
            message: $data['message'] ?? null,
        );
    }
}
