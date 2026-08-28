<?php

namespace ProjectMoon\FilamentDomainManager\DTOs;

class RecordExpectation
{
    public function __construct(
        public string $type,
        public string $target,
    ) {}

    public static function a(string $ip): self
    {
        return new self('A', $ip);
    }

    public static function cname(string $target): self
    {
        return new self('CNAME', $target);
    }

    public static function txt(string $content): self
    {
        return new self('TXT', $content);
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'target' => $this->target,
        ];
    }
}
