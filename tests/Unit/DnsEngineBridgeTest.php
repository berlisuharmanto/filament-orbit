<?php

use ProjectMoon\FilamentDomainManager\DTOs\DnsCheckRequest;
use ProjectMoon\FilamentDomainManager\DTOs\ProviderProvisionRequest;
use ProjectMoon\FilamentDomainManager\DTOs\ProviderRecord;
use ProjectMoon\FilamentDomainManager\DTOs\RecordExpectation;
use ProjectMoon\FilamentDomainManager\Facades\DomainManager;
use ProjectMoon\FilamentDomainManager\Services\DnsEngineBridge;

it('detects and resolves the binary version correctly', function () {
    $bridge = app(DnsEngineBridge::class);
    
    expect($bridge->isInstalled())->toBeTrue();

    $versionData = $bridge->version();

    expect($versionData)->toBeArray()
        ->and($versionData['success'])->toBeTrue()
        ->and($versionData['version'])->toBe('1.0.0')
        ->and($versionData['os'])->toBeString()
        ->and($versionData['arch'])->toBeString();
});

it('can validate DNS records via the CLI bridge', function () {
    $request = new DnsCheckRequest(
        domain: 'one.one.one.one',
        expectedRecords: [
            RecordExpectation::a('1.1.1.1'),
        ],
        resolvers: ['1.1.1.1:53', '8.8.8.8:53'],
    );

    $result = DomainManager::checkDns($request);

    expect($result->domain)->toBe('one.one.one.one')
        ->and($result->records)->toBeArray()
        ->and($result->records)->not->toBeEmpty();
});

it('can audit domain SSL certificate status', function () {
    $result = DomainManager::auditSsl('1.1.1.1');

    expect($result->domain)->toBe('1.1.1.1')
        ->and($result->status)->toBeString();
});

it('can provision and remove DNS records using the mock provider driver', function () {
    $req = new ProviderProvisionRequest(
        provider: 'mock',
        zone: 'tenant-domain.com',
        records: [
            ProviderRecord::cname('shop', 'ingress.mysaas.com', 300),
        ],
    );

    $result = DomainManager::provisionDns($req);

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe('provisioned')
        ->and($result->provider)->toBe('mock')
        ->and($result->createdRecords)->toHaveCount(1)
        ->and($result->createdRecords[0]->name)->toBe('shop');

    // Remove
    $removeResult = DomainManager::removeDns($req);
    expect($removeResult->success)->toBeTrue()
        ->and($removeResult->status)->toBe('removed');
});
