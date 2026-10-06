<?php

namespace Tests\Feature;

use App\Models\GuessIdolImage;
use App\Models\GuessSong;
use App\Models\Group;
use App\Models\Member;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminGameMediaStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guess_idol_image_upload_stays_on_public_storage_disk(): void
    {
        Storage::fake('public');
        $group = Group::create(['name' => 'Storage Group']);
        $member = Member::create(['group_id' => $group->id, 'name' => 'Storage Member']);

        $this->actingAs($this->adminUser())
            ->post(route('admin.guess-idol-images.store'), [
                'group_id' => $group->id,
                'member_id' => $member->id,
                'difficulty' => 'easy',
                'image' => UploadedFile::fake()->image('member.jpg'),
            ])
            ->assertRedirect(route('admin.guess-idol-images.index'));

        $image = GuessIdolImage::firstOrFail();
        Storage::disk('public')->assertExists($image->image);
        $this->assertStringNotContainsString('/public/storage/', str_replace('\\', '/', Storage::disk('public')->path($image->image)));
    }

    public function test_guess_song_upload_stays_on_public_storage_disk(): void
    {
        Storage::fake('public');

        $this->actingAs($this->adminUser())
            ->post(route('admin.guess-songs.store'), [
                'title' => 'Storage Song',
                'artist' => 'Storage Artist',
                'audio' => UploadedFile::fake()->create('song.mp3', 100, 'audio/mpeg'),
            ])
            ->assertRedirect(route('admin.guess-songs.index'));

        $song = GuessSong::firstOrFail();
        Storage::disk('public')->assertExists($song->audio);
        $this->assertStringNotContainsString('/public/storage/', str_replace('\\', '/', Storage::disk('public')->path($song->audio)));
    }

    private function adminUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::create(['name' => 'admin', 'label' => 'Administrator']));

        return $user;
    }
}
