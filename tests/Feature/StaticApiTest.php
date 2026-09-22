<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaticApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_users_cannot_write_to_the_static_api(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/groups', ['name' => 'Unauthorized Group'])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('groups', ['name' => 'Unauthorized Group']);
    }

    public function test_admin_users_can_create_a_group_with_validated_attributes(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::create(['name' => 'admin', 'label' => 'Administrator']));

        $this->actingAs($admin)
            ->postJson('/api/groups', [
                'name' => 'Validated Group',
                'unexpected' => 'must not be persisted',
            ])
            ->assertCreated()
            ->assertJsonPath('name', 'Validated Group')
            ->assertJsonMissingPath('unexpected');

        $this->assertDatabaseHas('groups', ['name' => 'Validated Group']);
    }

    public function test_invalid_relationships_are_rejected_by_resource_validation(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::create(['name' => 'admin', 'label' => 'Administrator']));

        $this->actingAs($admin)
            ->postJson('/api/members', [
                'group_id' => 999999,
                'name' => 'Invalid Member',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['group_id']);
    }
}
