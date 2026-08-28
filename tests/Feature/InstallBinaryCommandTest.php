<?php

use Illuminate\Support\Facades\File;

it('runs the domain-manager:install-binary artisan command successfully', function () {
    $tempDir = sys_get_temp_dir() . '/dns-manager-test-bin';
    File::deleteDirectory($tempDir);

    $this->artisan('domain-manager:install-binary', [
        '--target' => $tempDir,
        '--force' => true,
    ])
        ->expectsOutputToContain('Detecting host environment for DNS Engine')
        ->expectsOutputToContain('Verifying binary execution & health')
        ->assertSuccessful();

    expect(File::exists($tempDir . '/dns-manager'))->toBeTrue();

    File::deleteDirectory($tempDir);
});
