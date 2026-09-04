<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\FavoriteUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $data = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
        $quizNames = [];
        foreach ($data['quizzes'] ?? [] as $quiz) {
            $quizNames[(string)($quiz['id'] ?? '')] = $quiz['name'] ?? 'Quiz';
        }
        $favorites = $request->user()->favorites()->get()->groupBy('item_type');

        return view('profile.show', [
            'user' => $request->user(),
            'avatarMember' => $request->user()->avatarMember(),
            'results' => $request->user()->quizResults()->latest()->get(),
            'quizNames' => $quizNames,
            'groups' => $data['groups'] ?? [],
            'members' => $data['members'] ?? [],
            'albums' => $data['albums'] ?? [],
            'favorites' => [
                'group' => $this->resolveFavorites($favorites->get('group', collect()), $data['groups'] ?? []),
                'member' => $this->resolveFavorites($favorites->get('member', collect()), $data['members'] ?? []),
                'album' => $this->resolveFavorites($favorites->get('album', collect()), $data['albums'] ?? []),
            ],
        ]);
    }

    public function updateFavorites(FavoriteUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        DB::transaction(function () use ($user, $validated): void {
            $user->favorites()->delete();

            foreach ([
                'group' => $validated['favorite_groups'] ?? [],
                'member' => $validated['favorite_members'] ?? [],
                'album' => $validated['favorite_albums'] ?? [],
            ] as $type => $items) {
                foreach ($items as $position => $itemId) {
                    if ($itemId !== null) {
                        $user->favorites()->create([
                            'item_type' => $type,
                            'item_id' => $itemId,
                            'position' => $position + 1,
                        ]);
                    }
                }
            }
        });

        return Redirect::route('profile')->with('status', 'favorites-updated');
    }

    public function edit(Request $request): View
    {
        $data = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];

        return view('profile.edit', [
            'user' => $request->user(),
            'members' => $data['members'] ?? [],
            'groups' => $data['groups'] ?? [],
            'avatarMember' => $request->user()->avatarMember(),
        ]);
    }

    public function history(Request $request): View
    {
        return redirect()->route('profile');
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    private function resolveFavorites($favorites, array $items): array
    {
        $resolved = [];
        foreach ($favorites as $favorite) {
            foreach ($items as $item) {
                if ((int) ($item['id'] ?? 0) === (int) $favorite->item_id) {
                    $resolved[$favorite->position] = $item;
                    break;
                }
            }
        }

        return $resolved;
    }
}
