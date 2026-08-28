<?php

namespace ProjectMoon\FilamentDomainManager\DTOs;

class PropagationResult
{
    public function __construct(
        public float $percentage,
        public int $resolversChecked,
        public int $resolversMatched,
        public array $details = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            percentage: (float) ($data['percentage'] ?? 0.0),
            resolversChecked: (int) ($data['resolvers_checked'] ?? 0),
            resolversMatched: (int) ($data['resolvers_matched'] ?? 0),
            details: $data['details'] ?? [],
        );
    }
}
