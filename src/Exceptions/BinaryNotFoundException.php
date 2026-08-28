<?php

namespace ProjectMoon\FilamentDomainManager\Exceptions;

use Exception;

class BinaryNotFoundException extends Exception
{
    public static function create(string $path): self
    {
        return new self("The dns-manager binary was not found at '{$path}'. Please run 'php artisan domain-manager:install-binary' to install it.");
    }
}
