<?php

namespace ProjectMoon\FilamentDomainManager;

use ProjectMoon\FilamentDomainManager\Commands\InstallBinaryCommand;
use ProjectMoon\FilamentDomainManager\Services\DnsEngineBridge;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentDomainManagerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-domain-manager')
            ->hasConfigFile('domain-manager')
            ->hasViews('filament-domain-manager')
            ->hasMigration('create_domains_table')
            ->hasCommand(InstallBinaryCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(DnsEngineBridge::class, function () {
            return new DnsEngineBridge(config('domain-manager.binary_path'));
        });
    }
}
