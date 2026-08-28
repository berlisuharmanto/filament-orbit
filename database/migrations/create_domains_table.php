<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->string('domain')->unique();
            $table->string('tenant_id')->nullable()->index();
            
            // Connection Mode & Provider Drivers
            $table->string('connection_mode')->default('manual'); // 'auto', 'manual'
            $table->string('provider')->nullable(); // 'cloudflare', 'godaddy', 'route53', 'mock'
            $table->text('provider_credentials')->nullable(); // Encrypted API token/secret
            $table->string('provider_zone_id')->nullable();
            $table->string('provider_record_id')->nullable();

            // DNS Health & Verification Status
            $table->string('dns_status')->default('pending'); // 'pending', 'verified', 'failed'
            $table->timestamp('dns_last_checked_at')->nullable();
            $table->json('dns_records_data')->nullable();

            // SSL / TLS Status
            $table->string('ssl_status')->default('pending'); // 'pending', 'valid', 'expiring_soon', 'expired', 'mismatch', 'error'
            $table->string('ssl_issuer')->nullable();
            $table->timestamp('ssl_valid_to')->nullable();
            $table->integer('ssl_days_remaining')->nullable();
            $table->timestamp('ssl_last_checked_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
