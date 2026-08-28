<?php

namespace ProjectMoon\FilamentDomainManager\Exceptions;

use Exception;

class DnsEngineException extends Exception
{
    public static function executionFailed(string $command, string $error, int $exitCode): self
    {
        return new self("DNS Engine command '{$command}' failed (exit code {$exitCode}): {$error}");
    }

    public static function invalidJson(string $rawOutput): self
    {
        return new self("DNS Engine returned invalid JSON output: {$rawOutput}");
    }
}
