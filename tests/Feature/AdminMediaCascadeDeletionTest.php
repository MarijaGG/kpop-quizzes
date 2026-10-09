<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Group;
use App\Models\GuessIdolImage;
use App\Models\Member;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminMediaCascadeDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_group_removes_files_for_all_cascaded_descendants(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::create(['name' => 'admin', 'label' => 'Administrator']));
        $paths = [
            'images/groups/group.jpg',
            'images/members/member.jpg',
            'images/albums/album.jpg',
            'images/guess-idol/guess.jpg',
        ];
        foreach ($paths as $path) {
            Storage::disk('public')->put($path, 'test file');
        }

        $group = Group::create(['name' => 'Cascade group', 'image' => $paths[0]]);
        $member = Member::create([
            'group_id' => $group->id,
            'name' => 'Cascade member',
            'image' => $paths[1],
        ]);
        $album = Album::create([
            'group_id' => $group->id,
            'title' => 'Cascade album',
            'image' => $paths[2],
        ]);
        $guessImage = GuessIdolImage::create([
            'group_id' => $group->id,
            'member_id' => $member->id,
            'difficulty' => 'easy',
            'image' => $paths[3],
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.groups.destroy', $group->id))
            ->assertRedirect(route('admin.groups.index'));

        foreach ($paths as $path) {
            Storage::disk('public')->assertMissing($path);
        }
        $this->assertDatabaseMissing('groups', ['id' => $group->id]);
        $this->assertDatabaseMissing('members', ['id' => $member->id]);
        $this->assertDatabaseMissing('albums', ['id' => $album->id]);
        $this->assertDatabaseMissing('guess_idol_images', ['id' => $guessImage->id]);
    }

    public function test_api_group_deletion_uses_the_same_cascaded_media_cleanup(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::create(['name' => 'admin', 'label' => 'Administrator']));
        $paths = [
            'images/groups/api-group.jpg',
            'images/members/api-member.jpg',
            'images/albums/api-album.jpg',
            'images/guess-idol/api-guess.jpg',
        ];
        foreach ($paths as $path) {
            Storage::disk('public')->put($path, 'test file');
        }

        $group = Group::create(['name' => 'API cascade group', 'image' => $paths[0]]);
        $member = Member::create([
            'group_id' => $group->id,
            'name' => 'API cascade member',
            'image' => $paths[1],
        ]);
        Album::create(['group_id' => $group->id, 'title' => 'API cascade album', 'image' => $paths[2]]);
        GuessIdolImage::create([
            'group_id' => $group->id,
            'member_id' => $member->id,
            'difficulty' => 'easy',
            'image' => $paths[3],
        ]);

        $this->actingAs($admin)->deleteJson("/api/groups/{$group->id}")->assertNoContent();

        foreach ($paths as $path) {
            Storage::disk('public')->assertMissing($path);
        }
        $this->assertDatabaseMissing('groups', ['id' => $group->id]);
    }
}
