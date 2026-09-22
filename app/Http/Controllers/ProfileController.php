<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\FavoriteUpdateRequest;
use App\Models\Album;
use App\Models\Group;
use App\Models\Member;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $groups = Group::orderBy('name')->get()->map->toArray()->all();
        $members = Member::orderBy('name')->get()->map->toArray()->all();
        $albums = Album::orderBy('title')->get()->map->toArray()->all();
        $quizNames = Quiz::pluck('name', 'id')->mapWithKeys(fn ($name, $id) => [(string) $id => $name])->all();
        $favorites = $request->user()->favorites()->get()->groupBy('item_type');

        $viewData = [
            'user' => $request->user(),
            'avatarMember' => $request->user()->avatarMember(),
            'selectedTitle' => $request->user()->selectedTitleLabel(),
            'results' => $request->user()->quizResults()->latest()->paginate(5),
            'quizNames' => $quizNames,
            'groups' => $groups,
            'members' => $members,
            'albums' => $albums,
            'favorites' => [
                'group' => $this->resolveFavorites($favorites->get('group', collect()), $groups),
                'member' => $this->resolveFavorites($favorites->get('member', collect()), $members),
                'album' => $this->resolveFavorites($favorites->get('album', collect()), $albums),
            ],
        ];

        if ($request->ajax()) {
            return view('profile.partials.quiz-history', $viewData);
        }

        return view('profile.show', $viewData);
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
        return view('profile.edit', [
            'user' => $request->user(),
            'members' => Member::orderBy('name')->get()->map->toArray()->all(),
            'groups' => Group::orderBy('name')->get()->map->toArray()->all(),
            'avatarMember' => $request->user()->avatarMember(),
            'unlockedTitles' => $request->user()->unlockedTitles(),
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

    public function equipTitle(Request $request): RedirectResponse|JsonResponse
    {
        $titleKey = $request->validate([
            'title' => ['required', 'string', Rule::in(array_keys($request->user()->unlockedTitles()))],
        ])['title'];

        $request->user()->update(['selected_title' => $titleKey]);

        if ($request->expectsJson()) {
            return response()->json(['selected_title' => $titleKey]);
        }

        return Redirect::route('profile')->with('status', 'title-equipped');
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
