<?php

namespace App\Http\Controllers;

use App\Models\GuessSong;
use App\Models\QuizResult;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class GuessSongController extends Controller
{
    private const ROUNDS = 5;

    private const TIERS = [
        'hard' => ['seconds' => 0.5, 'points' => 3, 'next' => 'medium'],
        'medium' => ['seconds' => 2, 'points' => 2, 'next' => 'easy'],
        'easy' => ['seconds' => 5, 'points' => 1, 'next' => null],
    ];

    public function index(): View
    {
        return view('guess-song.index', [
            'available' => GuessSong::count(),
            'rounds' => self::ROUNDS,
        ]);
    }

    public function start(): RedirectResponse
    {
        session()->forget([
            'guess_song_result_saved',
            'guess_song_run',
            'guess_song_play_key',
            'guess_song_play_started_at',
        ]);

        if (GuessSong::count() < self::ROUNDS) {
            return back()->withErrors(['songs' => 'At least '.self::ROUNDS.' songs need to be added before this can be played.']);
        }

        $songs = GuessSong::inRandomOrder()->take(self::ROUNDS)->get()
            ->map(fn (GuessSong $song) => [
                'id' => $song->id,
                'title' => $song->title,
                'artist' => $song->artist,
                'audio' => $song->audio,
            ])->values()->all();

        session([
            'guess_song_run' => [
                'songs' => $songs,
                'index' => 0,
                'tier' => 'hard',
                'responses' => [],
            ],
        ]);

        return redirect()->route('guess-song.take');
    }

    public function take(): View|RedirectResponse
    {
        $run = session('guess_song_run');
        if (empty($run) || ! isset($run['songs'][$run['index'] ?? 0])) {
            return redirect()->route('guess-song.index');
        }

        $song = $run['songs'][$run['index']];
        $tier = $run['tier'];

        return view('guess-song.take', [
            'tier' => $tier,
            'tierSeconds' => self::TIERS[$tier]['seconds'],
            'tierPoints' => self::TIERS[$tier]['points'],
            'index' => $run['index'],
            'total' => count($run['songs']),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);
        $term = trim($validated['q']);

        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $matches = GuessSong::query()
            ->where(fn ($query) => $query
                ->where('title', 'like', '%'.$term.'%')
                ->orWhere('artist', 'like', '%'.$term.'%'))
            ->orderBy('title')
            ->limit(8)
            ->get(['title', 'artist'])
            ->map(fn (GuessSong $song) => [
                'title' => $song->title,
                'artist' => $song->artist,
            ]);

        return response()->json($matches);
    }

    public function clip()
    {
        $run = session('guess_song_run');
        $index = $run['index'] ?? null;
        $song = $run['songs'][$index] ?? null;

        abort_unless($song && isset(self::TIERS[$run['tier'] ?? '']), 404);
        abort_unless(Storage::disk('local')->exists($song['audio']), 404);

        $playKey = $index.'-'.$run['tier'];
        if (session('guess_song_play_key') !== $playKey) {
            session([
                'guess_song_play_key' => $playKey,
                'guess_song_play_started_at' => microtime(true),
            ]);
        }

        return Storage::disk('local')->response($song['audio'], null, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }

    public function answer(Request $request): RedirectResponse
    {
        $run = session('guess_song_run');
        if (empty($run) || ! isset($run['songs'][$run['index'] ?? 0])) {
            return redirect()->route('guess-song.index');
        }

        $validated = $request->validate([
            'guess' => ['nullable', 'string', 'max:255'],
            'skip' => ['nullable', 'boolean'],
        ]);

        $index = $run['index'];
        $song = $run['songs'][$index];
        $tier = $run['tier'];
        $isSkip = filter_var($validated['skip'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $guess = trim($validated['guess'] ?? '');
        $correct = ! $isSkip && $this->isCorrectGuess($guess, $song);

        if ($correct) {
            $playKey = $index.'-'.$tier;
            $startedAt = session('guess_song_play_key') === $playKey
                ? (float) session('guess_song_play_started_at', 0)
                : 0.0;
            $requiredSeconds = self::TIERS[$tier]['seconds'];

            if ($startedAt === 0 || microtime(true) < $startedAt + $requiredSeconds) {
                return back()->withErrors(['guess' => 'Listen to this tier’s clip before submitting your guess.']);
            }
        }

        if ($correct) {
            $run['responses'][] = [
                'title' => $song['title'],
                'artist' => $song['artist'],
                'guess' => $guess,
                'points' => self::TIERS[$tier]['points'],
                'correct' => true,
            ];
            $run['index'] = $index + 1;
            $run['tier'] = 'hard';
            session()->forget(['guess_song_play_key', 'guess_song_play_started_at']);
        } elseif ($isSkip && self::TIERS[$tier]['next']) {
            $run['tier'] = self::TIERS[$tier]['next'];
            session()->forget(['guess_song_play_key', 'guess_song_play_started_at']);
        } else {
            $run['responses'][] = [
                'title' => $song['title'],
                'artist' => $song['artist'],
                'guess' => $guess,
                'points' => 0,
                'correct' => false,
            ];
            $run['index'] = $index + 1;
            $run['tier'] = 'hard';
            session()->forget(['guess_song_play_key', 'guess_song_play_started_at']);
        }

        if ($run['index'] >= count($run['songs'])) {
            $run['score'] = collect($run['responses'])->sum('points');
            $run['newTitle'] = $this->saveResult($run);
            session(['guess_song_run' => $run]);
            return redirect()->route('guess-song.result');
        }

        session(['guess_song_run' => $run]);
        return redirect()->route('guess-song.take');
    }

    public function result(): View|RedirectResponse
    {
        $run = session('guess_song_run');
        if (empty($run) || ! isset($run['score'])) {
            return redirect()->route('guess-song.index');
        }

        return view('guess-song.result', [
            'run' => $run,
            'max' => count($run['songs']) * self::TIERS['hard']['points'],
            'newTitle' => $run['newTitle'] ?? null,
        ]);
    }

    private function isCorrectGuess(string $guess, array $song): bool
    {
        $guess = $this->normalize($guess);
        if ($guess === '') {
            return false;
        }

        $candidates = [
            $this->normalize($song['title']),
            $this->normalize($song['title'].' '.$song['artist']),
            $this->normalize($song['artist'].' '.$song['title']),
        ];

        return in_array($guess, $candidates, true);
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower($value);
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? '';
        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }

    private function saveResult(array $run): ?array
    {
        if (! auth()->check() || session()->has('guess_song_result_saved')) {
            return null;
        }

        $correct = collect($run['responses'])->where('correct', true)->count();
        $total = count($run['songs']);

        QuizResult::create([
            'user_id' => auth()->id(),
            'quiz_id' => 0,
            'quiz_name' => 'Guess the Song',
            'result_type' => 'guess_song',
            'correct_answers' => $correct,
            'total_questions' => $total,
            'result_name' => null,
            'total_points' => $run['score'],
            'details' => ['responses' => $run['responses']],
        ]);
        session(['guess_song_result_saved' => true]);

        if ($run['score'] === $total * self::TIERS['hard']['points']) {
            $award = auth()->user()->awardTitle('song-expert', 'Song Expert');

            return $award ? ['key' => $award->title_key, 'label' => $award->title_label] : null;
        }

        return null;
    }
}
