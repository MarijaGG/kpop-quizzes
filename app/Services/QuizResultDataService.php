<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Group;
use App\Models\Member;

class QuizResultDataService
{
    /**
     * Load only the entities referenced by this quiz attempt and its existing
     * statistics. Group results also need that group's members for score spreading.
     */
    public function forRun(array $run, array $quizStats = []): array
    {
        $targets = collect($run['questions'] ?? [])
            ->flatMap(fn (array $question) => $question['answers'] ?? [])
            ->filter(fn (array $answer) => ! empty($answer['target_id']))
            ->groupBy('target_type')
            ->map(fn ($answers) => $answers->pluck('target_id')->map(fn ($id) => (int) $id)->unique()->values());

        $memberIds = $targets->get('member', collect())->merge($this->statIds($quizStats, 'member'));
        $groupIds = $targets->get('group', collect())->merge($this->statIds($quizStats, 'group'));
        $albumIds = $targets->get('album', collect())->merge($this->statIds($quizStats, 'album'));

        $albums = Album::query()->whereIn('id', $albumIds->unique())->get();
        $groupIds = $groupIds->merge($albums->pluck('group_id'))
            ->filter()->map(fn ($id) => (int) $id)->unique()->values();
        $groups = Group::query()->whereIn('id', $groupIds)->get();

        $memberIds = $memberIds->merge(
            Member::query()->whereIn('group_id', $groupIds)->pluck('id')
        )->map(fn ($id) => (int) $id)->unique()->values();
        $members = Member::query()->whereIn('id', $memberIds)->get();

        return [
            'members' => $members,
            'groups' => $groups,
            'albums' => $albums,
        ];
    }

    private function statIds(array $quizStats, string $type)
    {
        return collect(array_keys($quizStats[$type] ?? []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id);
    }
}
