<?php

namespace App\Http\Controllers\Admin;

use App\Models\GuessIdolImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;

class GuessIdolImageController extends BaseAdminController
{
    public function index(Request $request)
    {
        $data = $this->data();
        $groups = $data['groups'] ?? [];
        $members = $data['members'] ?? [];
        $groupId = $request->query('group_id');
        $difficulty = $request->query('difficulty');

        $query = GuessIdolImage::query()->latest();
        if ($groupId !== null && $groupId !== '') {
            $query->where('group_id', $groupId);
        }
        if ($difficulty !== null && $difficulty !== '') {
            $query->where('difficulty', $difficulty);
        }

        $items = $query->get()->map(function (GuessIdolImage $image) use ($groups, $members) {
            $image->group_name = $this->nameFor($groups, $image->group_id);
            $image->member_name = $this->nameFor($members, $image->member_id);
            return $image;
        });
        $page = LengthAwarePaginator::resolveCurrentPage();
        $images = new LengthAwarePaginator(
            $items->forPage($page, 24),
            $items->count(),
            24,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.guess-idol-images.index', compact('images', 'groups', 'groupId', 'difficulty'));
    }

    public function create()
    {
        $data = $this->data();
        return view('admin.guess-idol-images.create', [
            'groups' => $data['groups'] ?? [],
            'members' => $data['members'] ?? [],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateImage($request);
        $data['image'] = $request->file('image')->store('images/guess-idol', 'public');
        $this->publishImage($data['image']);
        GuessIdolImage::create($data);

        return redirect()->route('admin.guess-idol-images.index')->with('success', 'Guess Idol image added.');
    }

    public function edit(GuessIdolImage $guessIdolImage)
    {
        $data = $this->data();
        return view('admin.guess-idol-images.edit', [
            'image' => $guessIdolImage,
            'groups' => $data['groups'] ?? [],
            'members' => $data['members'] ?? [],
        ]);
    }

    public function update(Request $request, GuessIdolImage $guessIdolImage)
    {
        $data = $this->validateImage($request, false);
        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($guessIdolImage->image);
            $this->deletePublishedImage($guessIdolImage->image);
            $data['image'] = $request->file('image')->store('images/guess-idol', 'public');
            $this->publishImage($data['image']);
        }
        $guessIdolImage->update($data);

        return redirect()->route('admin.guess-idol-images.index')->with('success', 'Guess Idol image updated.');
    }

    public function destroy(GuessIdolImage $guessIdolImage)
    {
        Storage::disk('public')->delete($guessIdolImage->image);
        $this->deletePublishedImage($guessIdolImage->image);
        $guessIdolImage->delete();
        return redirect()->route('admin.guess-idol-images.index')->with('success', 'Guess Idol image deleted.');
    }

    private function validateImage(Request $request, bool $required = true): array
    {
        $data = $this->data();
        $groupIds = array_column($data['groups'] ?? [], 'id');
        $memberIds = array_column($data['members'] ?? [], 'id');
        $rules = [
            'group_id' => ['required', 'integer', 'in:'.implode(',', $groupIds)],
            'member_id' => ['required', 'integer', 'in:'.implode(',', $memberIds)],
            'difficulty' => ['required', 'in:easy,medium,hard'],
            'image' => [$required ? 'required' : 'nullable', 'image', 'max:5120'],
        ];
        $validated = $request->validate($rules);

        $member = collect($data['members'] ?? [])->firstWhere('id', (int) $validated['member_id']);
        if (! $member || (string) ($member['group_id'] ?? '') !== (string) $validated['group_id']) {
            abort(422, 'The selected member must belong to the selected group.');
        }

        return $validated;
    }

    private function data(): array
    {
        return json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
    }

    private function nameFor(array $items, $id): string
    {
        foreach ($items as $item) {
            if ((string) ($item['id'] ?? '') === (string) $id) {
                return $item['name'] ?? $item['title'] ?? 'Unknown';
            }
        }
        return 'Unknown';
    }

    private function publishImage(string $path): void
    {
        $source = storage_path('app/public/'.$path);
        $destination = public_path('storage/'.$path);
        if (! is_dir(dirname($destination))) {
            mkdir(dirname($destination), 0755, true);
        }
        copy($source, $destination);
    }

    private function deletePublishedImage(string $path): void
    {
        $destination = public_path('storage/'.$path);
        if (is_file($destination)) {
            unlink($destination);
        }
    }
}
