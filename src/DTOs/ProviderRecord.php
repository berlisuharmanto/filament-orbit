<?php

namespace ProjectMoon\FilamentDomainManager\DTOs;

class ProviderRecord
{
    public function __construct(
        public string $type,
        public string $name,
        public string $value,
        public int $ttl = 300,
        public ?string $id = null,
    ) {}

    public static function cname(string $name, string $target, int $ttl = 300): self
    {
        return new self(type: 'CNAME', name: $name, value: $target, ttl: $ttl);
    }

    public static function a(string $name, string $ip, int $ttl = 300): self
    {
        return new self(type: 'A', name: $name, value: $ip, ttl: $ttl);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'value' => $this->value,
            'ttl' => $this->ttl,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'] ?? 'A',
            name: $data['name'] ?? '@',
            value: $data['value'] ?? '',
            ttl: (int) ($data['ttl'] ?? 300),
            id: $data['id'] ?? null,
        );
    }
}
