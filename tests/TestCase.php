<?php

namespace ProjectMoon\FilamentDomainManager\Tests;

use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use ProjectMoon\FilamentDomainManager\FilamentDomainManagerServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        if (extension_loaded('pdo_sqlite')) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
    }

    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            FilamentServiceProvider::class,
            FormsServiceProvider::class,
            TablesServiceProvider::class,
            SupportServiceProvider::class,
            NotificationsServiceProvider::class,
            FilamentDomainManagerServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        if (extension_loaded('pdo_sqlite')) {
            config()->set('database.default', 'testing');
            config()->set('database.connections.testing', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);
        }

        config()->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        config()->set('domain-manager.binary_path', dirname(__DIR__) . '/bin/dns-manager');
        config()->set('domain-manager.ingress_target', 'ingress.test.local');
        config()->set('domain-manager.ingress_ip', '192.0.2.1');
    }
}
