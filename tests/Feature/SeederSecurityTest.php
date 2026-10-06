<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolesAndAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_account_is_not_created_without_configured_credentials(): void
    {
        $this->clearAdminEnv();

        $this->seed(RolesAndAdminSeeder::class);

        $this->assertDatabaseMissing('users', ['email' => 'admin@example.com']);
        $this->assertSame(0, User::whereHas('roles', fn ($query) => $query->where('name', 'admin'))->count());
    }

    public function test_admin_account_uses_configured_credentials(): void
    {
        putenv('ADMIN_EMAIL=admin-configured@example.com');
        putenv('ADMIN_PASSWORD=secure-pass-123');

        try {
            $this->seed(RolesAndAdminSeeder::class);
        } finally {
            $this->clearAdminEnv();
        }

        $admin = User::where('email', 'admin-configured@example.com')->firstOrFail();
        $this->assertTrue($admin->isAdmin());
        $this->assertDatabaseMissing('users', ['email' => 'admin@example.com']);
    }

    public function test_default_test_user_is_not_seeded_in_production(): void
    {
        putenv('ADMIN_EMAIL=prod-admin@example.com');
        putenv('ADMIN_PASSWORD=secure-pass-123');
        $originalEnv = app()->environment();
        $this->app->instance('env', 'production');

        try {
            $this->artisan('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true])->assertSuccessful();
        } finally {
            $this->app->instance('env', $originalEnv);
            $this->clearAdminEnv();
        }

        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_default_test_user_is_seeded_locally(): void
    {
        $this->clearAdminEnv();

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    private function clearAdminEnv(): void
    {
        putenv('ADMIN_EMAIL');
        putenv('ADMIN_PASSWORD');
        unset($_ENV['ADMIN_EMAIL'], $_ENV['ADMIN_PASSWORD'], $_SERVER['ADMIN_EMAIL'], $_SERVER['ADMIN_PASSWORD']);
    }
}
