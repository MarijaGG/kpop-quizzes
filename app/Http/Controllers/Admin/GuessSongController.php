<?php

namespace App\Http\Controllers\Admin;

use App\Models\GuessSong;
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
        $data['audio'] = $request->file('audio')->store('audio/guess-song', 'public');
        $this->publishAudio($data['audio']);
        GuessSong::create($data);

        return redirect()->route('admin.guess-songs.index')->with('success', 'Song added.');
    }

    public function edit(GuessSong $guessSong)
    {
        return view('admin.guess-songs.edit', ['song' => $guessSong]);
    }

    public function update(Request $request, GuessSong $guessSong)
    {
        $data = $this->validateSong($request, false);
        if ($request->hasFile('audio')) {
            Storage::disk('public')->delete($guessSong->audio);
            $this->deletePublishedAudio($guessSong->audio);
            $data['audio'] = $request->file('audio')->store('audio/guess-song', 'public');
            $this->publishAudio($data['audio']);
        }
        $guessSong->update($data);

        return redirect()->route('admin.guess-songs.index')->with('success', 'Song updated.');
    }

    public function destroy(GuessSong $guessSong)
    {
        Storage::disk('public')->delete($guessSong->audio);
        $this->deletePublishedAudio($guessSong->audio);
        $guessSong->delete();

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

    private function publishAudio(string $path): void
    {
        $source = storage_path('app/public/'.$path);
        $destination = public_path('storage/'.$path);
        if (! is_dir(dirname($destination))) {
            mkdir(dirname($destination), 0755, true);
        }
        copy($source, $destination);
    }

    private function deletePublishedAudio(string $path): void
    {
        $destination = public_path('storage/'.$path);
        if (is_file($destination)) {
            unlink($destination);
        }
    }
}
