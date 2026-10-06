<?php

namespace Tests\Feature;

use App\Models\GuessIdolImage;
use App\Models\GuessSong;
use App\Models\Member;
use Database\Seeders\ApiDataSeeder;
use Database\Seeders\GameContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GameContentSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_game_demo_seeder_imports_only_available_media_and_is_idempotent(): void
    {
        $manifest = json_decode(file_get_contents(resource_path('data/game-demo.json')), true, 512, JSON_THROW_ON_ERROR);
        $expectedImages = collect($manifest['guess_idol_images'])
            ->filter(fn ($image) => Storage::disk('public')->exists($image['image']))
            ->count();
        $expectedSongs = collect($manifest['guess_songs'])
            ->filter(fn ($song) => Storage::disk('public')->exists($song['audio']))
            ->count();

        $this->seed(ApiDataSeeder::class);
        $this->seed(GameContentSeeder::class);

        $this->assertSame($expectedImages, GuessIdolImage::count());
        $this->assertSame($expectedSongs, GuessSong::count());
        $this->assertTrue(GuessIdolImage::query()->get()->every(function (GuessIdolImage $image): bool {
            return Storage::disk('public')->exists($image->image)
                && Member::whereKey($image->member_id)->where('group_id', $image->group_id)->exists();
        }));
        $this->assertTrue(GuessSong::query()->get()->every(fn (GuessSong $song) => Storage::disk('public')->exists($song->audio)));

        $this->seed(GameContentSeeder::class);

        $this->assertSame($expectedImages, GuessIdolImage::count());
        $this->assertSame($expectedSongs, GuessSong::count());
    }
}
