<?php

use ProjectMoon\FilamentDomainManager\Models\Domain;

it('generates expected DNS records for apex and subdomains', function () {
    $apex = new Domain(['domain' => 'mybrand.com']);
    $apexRecords = $apex->getExpectedRecords();

    expect($apexRecords)->toBeArray()
        ->and($apexRecords)->toHaveCount(2)
        ->and($apexRecords[0]->type)->toBe('A')
        ->and($apexRecords[1]->type)->toBe('CNAME');

    $subdomain = new Domain(['domain' => 'shop.mybrand.com']);
    $subRecords = $subdomain->getExpectedRecords();

    expect($subRecords)->toBeArray()
        ->and($subRecords)->toHaveCount(1)
        ->and($subRecords[0]->type)->toBe('CNAME')
        ->and($subRecords[0]->target)->toBe('ingress.test.local');
});

it('can evaluate domain DNS records through Domain model', function () {
    $domain = new Domain([
        'domain' => 'one.one.one.one',
        'connection_mode' => 'manual',
    ]);

    // Mock update to avoid needing DB connection in unit test
    $domain->exists = false;

    $expected = $domain->getExpectedRecords();
    expect($expected)->toBeArray();
});

it('can auto-provision a domain using mock provider', function () {
    if (!extension_loaded('pdo_sqlite')) {
        $domain = new Domain([
            'domain' => 'app.tenant-mock.com',
            'tenant_id' => 'tenant-202',
            'connection_mode' => 'auto',
            'provider' => 'mock',
            'provider_credentials' => [
                'api_token' => 'mock-token-123',
            ],
        ]);

        $result = $domain->provisionWithProvider();
        expect($result->success)->toBeTrue()
            ->and($result->status)->toBe('provisioned')
            ->and($result->createdRecords)->toHaveCount(1);
        return;
    }

    /** @var Domain $domain */
    $domain = Domain::create([
        'domain' => 'app.tenant-mock.com',
        'tenant_id' => 'tenant-202',
        'connection_mode' => 'auto',
        'provider' => 'mock',
        'provider_credentials' => [
            'api_token' => 'mock-token-123',
        ],
    ]);

    $result = $domain->provisionWithProvider();

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe('provisioned')
        ->and($domain->fresh()->dns_status)->toBe('verified')
        ->and($domain->fresh()->provider_record_id)->not->toBeNull();

    // Test deletion / cleanup
    $removeResult = $domain->removeWithProvider();
    expect($removeResult->success)->toBeTrue()
        ->and($removeResult->status)->toBe('removed');
});
