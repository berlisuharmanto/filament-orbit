<?php

namespace ProjectMoon\FilamentDomainManager\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ProjectMoon\FilamentDomainManager\Services\DnsEngineBridge;
use Symfony\Component\Process\Process;

class InstallBinaryCommand extends Command
{
    protected $signature = 'domain-manager:install-binary
                            {--force : Overwrite existing binary if present}
                            {--target= : Custom installation destination directory}';

    protected $description = 'Install or update the native compiled Go DNS Engine binary for the current OS and CPU architecture';

    public function handle(): int
    {
        $this->info('==> Detecting host environment for DNS Engine...');

        $os = $this->detectOS();
        $arch = $this->detectArch();

        $this->line("  -> Detected OS: <comment>{$os}</comment>");
        $this->line("  -> Detected Architecture: <comment>{$arch}</comment>");

        $binaryName = "dns-manager-{$os}-{$arch}" . ($os === 'windows' ? '.exe' : '');
        $destFileName = 'dns-manager' . ($os === 'windows' ? '.exe' : '');

        $targetDir = $this->option('target') ?: base_path('bin');
        if (!File::isDirectory($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        $destPath = $targetDir . DIRECTORY_SEPARATOR . $destFileName;

        if (File::exists($destPath) && !$this->option('force')) {
            $this->warn("Binary already exists at [{$destPath}]. Use --force to reinstall or overwrite.");
            
            // Run sanity check
            return $this->verifyInstallation($destPath);
        }

        // Look for pre-compiled assets in bin/dist or package distribution
        $sourceCandidates = [
            base_path("bin/dist/{$binaryName}"),
            dirname(__DIR__, 2) . "/bin/dist/{$binaryName}",
            base_path("bin/{$destFileName}"),
            dirname(__DIR__, 2) . "/bin/{$destFileName}",
        ];

        $sourcePath = null;
        foreach ($sourceCandidates as $candidate) {
            if (File::exists($candidate)) {
                $sourcePath = $candidate;
                break;
            }
        }

        if (!$sourcePath) {
            $this->error("No pre-compiled binary found for [{$binaryName}].");
            $this->line("Please build the binary by running:");
            $this->line("  <info>cd engine && ./build.sh</info>");
            return self::FAILURE;
        }

        $this->info("==> Installing binary from [{$sourcePath}] to [{$destPath}]...");
        File::copy($sourcePath, $destPath);

        if ($os !== 'windows') {
            @chmod($destPath, 0755);
        }

        return $this->verifyInstallation($destPath);
    }

    protected function verifyInstallation(string $path): int
    {
        $this->info('==> Verifying binary execution & health...');

        $process = new Process([$path, 'version']);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error('Binary failed execution test: ' . $process->getErrorOutput());
            return self::FAILURE;
        }

        $output = json_decode($process->getOutput(), true);
        if ($output && !empty($output['version'])) {
            $this->info("✓ DNS Engine binary successfully verified! Version: <comment>{$output['version']}</comment> ({$output['os']}/{$output['arch']})");
            return self::SUCCESS;
        }

        $this->warn('Binary executed but returned unexpected output format: ' . $process->getOutput());
        return self::SUCCESS;
    }

    public function detectOS(): string
    {
        $family = PHP_OS_FAMILY;

        return match (strtolower($family)) {
            'darwin' => 'darwin',
            'windows' => 'windows',
            default => 'linux',
        };
    }

    public function detectArch(): string
    {
        $machine = strtolower(php_uname('m'));

        if (str_contains($machine, 'arm64') || str_contains($machine, 'aarch64')) {
            return 'arm64';
        }

        return 'amd64';
    }
}
