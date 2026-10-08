<?php

namespace Database\Seeders;

use App\Models\GuessIdolImage;
use App\Models\GuessSong;
use App\Models\Group;
use App\Models\Member;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class GameContentSeeder extends Seeder
{
    public function run(): void
    {
        $path = resource_path('data/game-demo.json');
        $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $missingImages = 0;
        $missingAudio = 0;
        $missingReferences = 0;

        foreach ($data['guess_idol_images'] as $image) {
            if (! Storage::disk('public')->exists($image['image'])) {
                $missingImages++;
                continue;
            }

            if (! Group::whereKey($image['group_id'])->exists()
                || ! Member::whereKey($image['member_id'])->where('group_id', $image['group_id'])->exists()) {
                $missingReferences++;
                continue;
            }

            GuessIdolImage::updateOrCreate(
                [
                    'group_id' => $image['group_id'],
                    'member_id' => $image['member_id'],
                    'difficulty' => $image['difficulty'],
                    'image' => $image['image'],
                ],
                [],
            );
        }

        foreach ($data['guess_songs'] as $song) {
            if (! Storage::disk('local')->exists($song['audio'])) {
                $missingAudio++;
                continue;
            }

            GuessSong::updateOrCreate(
                ['audio' => $song['audio']],
                ['title' => $song['title'], 'artist' => $song['artist']],
            );
        }

        if ($missingImages || $missingAudio || $missingReferences) {
            $this->command?->warn("Game demo content skipped: {$missingImages} Guess Idol image(s), {$missingAudio} Guess Song audio file(s), and {$missingReferences} invalid Guess Idol group/member reference(s). See README.md for setup instructions.");
        }
    }
}
