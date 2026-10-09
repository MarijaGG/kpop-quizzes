<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Group;
use App\Models\GuessIdolImage;
use App\Models\GuessSong;
use App\Models\Member;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MediaDeletionService
{
    /** Delete a record first, then its owned files after the database succeeds. */
    public function delete(Model $model): bool
    {
        $files = $this->filesFor($model);
        $deleted = DB::transaction(fn (): bool => (bool) $model->delete());

        if (! $deleted) {
            return false;
        }

        foreach ($files as $disk => $paths) {
            $paths = array_values(array_unique(array_filter($paths, fn ($path) => is_string($path) && $path !== '')));
            if ($paths !== []) {
                Storage::disk($disk)->delete($paths);
            }
        }

        return true;
    }

    private function filesFor(Model $model): array
    {
        if ($model instanceof Group) {
            return [
                'public' => array_merge(
                    [$model->image],
                    $model->members()->pluck('image')->all(),
                    $model->albums()->pluck('image')->all(),
                    $model->guessIdolImages()->pluck('image')->all(),
                ),
            ];
        }

        if ($model instanceof Member) {
            return ['public' => array_merge([$model->image], $model->guessIdolImages()->pluck('image')->all())];
        }

        if ($model instanceof Album || $model instanceof Quiz) {
            return ['public' => [$model->image]];
        }

        if ($model instanceof GuessIdolImage) {
            return ['public' => [$model->image]];
        }

        if ($model instanceof GuessSong) {
            return ['local' => [$model->audio]];
        }

        return [];
    }
}
