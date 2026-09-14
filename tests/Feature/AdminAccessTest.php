<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_admin_routes(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_authenticated_non_admin_users_are_redirected_to_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertRedirect(route('dashboard'));
    }

    public function test_admin_users_can_access_admin_routes(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::create(['name' => 'admin', 'label' => 'Administrator']));

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk();
    }
}