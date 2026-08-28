<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use ProjectMoon\FilamentDomainManager\Models\Domain;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Default Admin User for Filament Login
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
            ]
        );

        // 2. Create Sample Demo Domains for UI Preview
        Domain::firstOrCreate(
            ['domain' => 'store.acme-tenant.com'],
            [
                'tenant_id' => 'tenant-1001',
                'connection_mode' => 'manual',
                'dns_status' => 'pending',
                'ssl_status' => 'pending',
            ]
        );

        Domain::firstOrCreate(
            ['domain' => 'portal.client-corp.com'],
            [
                'tenant_id' => 'tenant-1002',
                'connection_mode' => 'auto',
                'provider' => 'mock',
                'provider_credentials' => [
                    'api_token' => 'mock-dev-token-12345',
                ],
                'dns_status' => 'verified',
                'ssl_status' => 'valid',
                'ssl_days_remaining' => 74,
                'ssl_issuer' => "Let's Encrypt Authority X3",
                'ssl_valid_to' => now()->addDays(74),
                'dns_last_checked_at' => now()->subMinutes(10),
                'ssl_last_checked_at' => now()->subMinutes(10),
            ]
        );
    }
}
