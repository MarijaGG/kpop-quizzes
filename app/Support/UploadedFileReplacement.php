<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class UploadedFileReplacement
{
    /**
     * Store the new upload, persist its path, then remove the old file.
     * If persistence fails, remove the new upload and leave the old file intact.
     */
    public static function persist(
        UploadedFile $upload,
        string $directory,
        string $disk,
        ?string $oldPath,
        callable $persist,
    ): mixed {
        $newPath = $upload->store($directory, $disk);
        if (! $newPath) {
            throw new RuntimeException('The uploaded file could not be stored.');
        }

        try {
            $result = $persist($newPath);
            if ($result === false) {
                throw new RuntimeException('The database did not save the uploaded file reference.');
            }
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($newPath);
            throw $exception;
        }

        if ($oldPath && $oldPath !== $newPath) {
            Storage::disk($disk)->delete($oldPath);
        }

        return $result;
    }
}
