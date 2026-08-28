<?php

namespace ProjectMoon\FilamentDomainManager;

use Filament\Contracts\Plugin;
use Filament\Panel;
use ProjectMoon\FilamentDomainManager\Resources\DomainResource;

class FilamentDomainManagerPlugin implements Plugin
{
    protected bool $hasTenantManagement = true;

    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'filament-domain-manager';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            DomainResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        // Boot hooks if needed across Filament v3 - v5
    }

    public function tenantManagement(bool $condition = true): static
    {
        $this->hasTenantManagement = $condition;
        return $this;
    }

    public function hasTenantManagement(): bool
    {
        return $this->hasTenantManagement;
    }
}
