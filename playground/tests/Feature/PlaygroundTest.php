<?php

namespace Tests\Feature;

use App\Models\User;
use ProjectMoon\FilamentDomainManager\Models\Domain;
use Tests\TestCase;

class PlaygroundTest extends TestCase
{
    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get('/admin/login');
        $response->assertStatus(200);
    }

    public function test_admin_can_access_domain_management_resource(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin User', 'password' => bcrypt('password')]
        );

        $response = $this->actingAs($user)->get('/admin/domains');

        $response->assertStatus(200);
        $response->assertSeeText('Domains');
        $response->assertSeeText('store.acme-tenant.com');
        $response->assertSeeText('portal.client-corp.com');
    }

    public function test_admin_can_open_domain_creation_form(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin User', 'password' => bcrypt('password')]
        );

        $response = $this->actingAs($user)->get('/admin/domains/create');

        $response->assertStatus(200);
        $response->assertSeeText('Domain Information');
        $response->assertSeeText('Connection Mode & DNS Automation');
    }
}
