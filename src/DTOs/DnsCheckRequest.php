<?php

namespace ProjectMoon\FilamentDomainManager\DTOs;

class DnsCheckRequest
{
    /**
     * @param RecordExpectation[] $expectedRecords
     * @param string[] $resolvers
     */
    public function __construct(
        public string $domain,
        public array $expectedRecords = [],
        public array $resolvers = [],
        public int $timeoutMs = 5000,
    ) {}

    public function toArray(): array
    {
        return [
            'domain' => $this->domain,
            'expected_records' => array_map(fn (RecordExpectation $r) => $r->toArray(), $this->expectedRecords),
            'resolvers' => $this->resolvers,
            'timeout_ms' => $this->timeoutMs,
        ];
    }
}
