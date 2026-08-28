<?php

return [
    /*
    |--------------------------------------------------------------------------
    | DNS Manager Binary Path
    |--------------------------------------------------------------------------
    |
    | The absolute or relative path to the compiled Go dns-manager executable.
    | If null, the package will inspect standard locations (bin/dns-manager,
    | storage/app/bin/dns-manager, vendor/bin/dns-manager).
    |
    */
    'binary_path' => env('DNS_MANAGER_BINARY_PATH', base_path('bin/dns-manager')),

    /*
    |--------------------------------------------------------------------------
    | Ingress Target & Server IP
    |--------------------------------------------------------------------------
    |
    | These values are presented to tenants when generating manual DNS setup
    | instructions for CNAME and A records.
    |
    */
    'ingress_target' => env('DOMAIN_MANAGER_INGRESS_TARGET', 'ingress.example.com'),
    'ingress_ip' => env('DOMAIN_MANAGER_INGRESS_IP', '192.0.2.1'),

    /*
    |--------------------------------------------------------------------------
    | Default DNS Resolvers
    |--------------------------------------------------------------------------
    |
    | Public and authoritative nameservers queried in parallel to measure
    | global DNS propagation.
    |
    */
    'resolvers' => [
        '1.1.1.1:53',
        '8.8.8.8:53',
        '9.9.9.9:53',
        '208.67.222.222:53',
    ],

    /*
    |--------------------------------------------------------------------------
    | Automatic SSL Auditing
    |--------------------------------------------------------------------------
    |
    | When enabled, domain DNS verification automatically executes a TLS
    | handshake audit to inspect SSL certificate validity and expiration.
    |
    */
    'auto_ssl_audit' => env('DOMAIN_MANAGER_AUTO_SSL_AUDIT', true),

    /*
    |--------------------------------------------------------------------------
    | Global DNS Provider Credentials
    |--------------------------------------------------------------------------
    |
    | Optional global credentials for DNS providers (Cloudflare, GoDaddy, etc.).
    | If not specified here, credentials can be configured per domain in Filament.
    |
    */
    'providers' => [
        'cloudflare' => [
            'api_token' => env('CLOUDFLARE_DNS_API_TOKEN', ''),
        ],
        'godaddy' => [
            'key' => env('GODADDY_API_KEY', ''),
            'secret' => env('GODADDY_API_SECRET', ''),
        ],
    ],
];
