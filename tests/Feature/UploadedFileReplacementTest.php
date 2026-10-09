<?php

namespace Tests\Feature;

use App\Support\UploadedFileReplacement;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class UploadedFileReplacementTest extends TestCase
{
    public function test_new_file_is_persisted_before_old_file_is_deleted(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('images/old.jpg', 'old contents');
        $newPath = null;

        UploadedFileReplacement::persist(
            UploadedFile::fake()->image('new.jpg'),
            'images',
            'public',
            'images/old.jpg',
            function (string $path) use (&$newPath): string {
                $newPath = $path;
                Storage::disk('public')->assertExists('images/old.jpg');
                Storage::disk('public')->assertExists($path);

                return 'saved';
            },
        );

        Storage::disk('public')->assertMissing('images/old.jpg');
        Storage::disk('public')->assertExists($newPath);
    }

    public function test_persistence_failure_removes_new_file_and_keeps_old_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('images/old.jpg', 'old contents');
        $newPath = null;

        try {
            UploadedFileReplacement::persist(
                UploadedFile::fake()->image('new.jpg'),
                'images',
                'public',
                'images/old.jpg',
                function (string $path) use (&$newPath): never {
                    $newPath = $path;
                    throw new RuntimeException('Simulated database save failure.');
                },
            );
            $this->fail('The persistence exception should be rethrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated database save failure.', $exception->getMessage());
        }

        Storage::disk('public')->assertExists('images/old.jpg');
        Storage::disk('public')->assertMissing($newPath);
    }
}
