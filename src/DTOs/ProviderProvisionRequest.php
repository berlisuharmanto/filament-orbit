<?php

namespace ProjectMoon\FilamentDomainManager\DTOs;

class ProviderProvisionRequest
{
    /**
     * @param ProviderRecord[] $records
     */
    public function __construct(
        public string $provider,
        public string $zone,
        public array $records,
        public ?string $authToken = null,
        public ?string $authSecret = null,
        public ?string $zoneId = null,
    ) {}

    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'zone' => $this->zone,
            'auth_token' => $this->authToken,
            'auth_secret' => $this->authSecret,
            'zone_id' => $this->zoneId,
            'records' => array_map(fn (ProviderRecord $r) => $r->toArray(), $this->records),
        ];
    }
}
