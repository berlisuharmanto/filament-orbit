<?php

use Filament\Panel;
use ProjectMoon\FilamentDomainManager\FilamentDomainManagerPlugin;
use ProjectMoon\FilamentDomainManager\Resources\DomainResource;

it('registers plugin resources into Filament panel', function () {
    $plugin = FilamentDomainManagerPlugin::make();

    expect($plugin->getId())->toBe('filament-domain-manager');

    $panel = Panel::make()
        ->id('admin')
        ->default()
        ->plugin($plugin);

    expect($panel->getResources())->toContain(DomainResource::class);
});
