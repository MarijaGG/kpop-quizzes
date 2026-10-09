<?php

namespace App\Http\Controllers\Admin;

use App\Models\GuessSong;
use App\Services\MediaDeletionService;
use App\Support\UploadedFileReplacement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GuessSongController extends BaseAdminController
{
    public function index()
    {
        $songs = GuessSong::latest()->paginate(24);

        return view('admin.guess-songs.index', compact('songs'));
    }

    public function create()
    {
        return view('admin.guess-songs.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateSong($request);
        UploadedFileReplacement::persist(
            $request->file('audio'),
            'audio/guess-song',
            'local',
            null,
            fn (string $path) => GuessSong::create(array_merge($data, ['audio' => $path])),
        );

        return redirect()->route('admin.guess-songs.index')->with('success', 'Song added.');
    }

    public function edit(GuessSong $guessSong)
    {
        return view('admin.guess-songs.edit', ['song' => $guessSong]);
    }

    public function audio(GuessSong $guessSong)
    {
        abort_unless(Storage::disk('local')->exists($guessSong->audio), 404);

        return Storage::disk('local')->response($guessSong->audio, null, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }

    public function update(Request $request, GuessSong $guessSong)
    {
        $data = $this->validateSong($request, false);
        if ($request->hasFile('audio')) {
            UploadedFileReplacement::persist(
                $request->file('audio'),
                'audio/guess-song',
                'local',
                $guessSong->audio,
                fn (string $path) => $guessSong->update(array_merge($data, ['audio' => $path])),
            );
        } else {
            $guessSong->update($data);
        }

        return redirect()->route('admin.guess-songs.index')->with('success', 'Song updated.');
    }

    public function destroy(MediaDeletionService $mediaDeletion, GuessSong $guessSong)
    {
        $mediaDeletion->delete($guessSong);

        return redirect()->route('admin.guess-songs.index')->with('success', 'Song deleted.');
    }

    private function validateSong(Request $request, bool $required = true): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'artist' => ['required', 'string', 'max:255'],
            'audio' => [$required ? 'required' : 'nullable', 'file', 'mimes:mp3,wav,ogg,m4a', 'max:10240'],
        ]);
    }
}
