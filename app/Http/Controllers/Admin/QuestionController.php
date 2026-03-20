<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\BaseAdminController;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class QuestionController extends BaseAdminController
{
    public function index($quizId)
    {
        $json = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
        $questions = array_values(array_filter($json['questions'] ?? [], function($q) use ($quizId) { return (string)($q['quiz_id'] ?? '') === (string)$quizId; }));
        $page = (int) request('page', 1);
        $perPage = 50;
        $total = count($questions);
        $items = array_slice($questions, ($page - 1) * $perPage, $perPage);
        $items = array_map(function($i){ return (object)$i; }, $items);
        $p = new LengthAwarePaginator($items, $total, $perPage, $page, ['path' => url()->current()]);
        // find quiz
        $quiz = null;
        foreach ($json['quizzes'] ?? [] as $qq) { if ((string)($qq['id'] ?? '') === (string)$quizId) { $quiz = (object)$qq; break; } }
        return view('admin.questions.index', ['questions' => $p, 'quiz' => $quiz]);
    }

    public function create($quizId)
    {
        $json = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
        $questions = $json['questions'] ?? [];
        $ids = array_column($questions, 'id');
        $max = count($ids) ? max($ids) : 0;
        $newId = $max + 1;
        $questions[] = [
            'id' => $newId,
            'quiz_id' => (int)$quizId,
            'text' => '',
            'order' => count($questions) + 1,
        ];
        $json['questions'] = $questions;
        file_put_contents(resource_path('data/api.json'), json_encode($json, JSON_PRETTY_PRINT));

        return redirect()->route('admin.quizzes.questions.edit', [$quizId, $newId]);
    }

    public function edit($quizId, $questionId)
    {
        $json = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
        $question = null;
        foreach ($json['questions'] ?? [] as $q) { if ((string)($q['id'] ?? '') === (string)$questionId) { $question = (object)$q; break; } }
        $answers = array_values(array_filter($json['answers'] ?? [], function($a) use ($questionId){ return (string)($a['question_id'] ?? '') === (string)$questionId; }));
        // legacy data: some answer rows reference old question IDs (1..n) matching the question's order
        if (empty($answers) && $question && isset($question->order)) {
            $legacyId = (string)$question->order;
            $answers = array_values(array_filter($json['answers'] ?? [], function($a) use ($legacyId){ return (string)($a['question_id'] ?? '') === $legacyId; }));
        }
        $answers = array_map(function($i){ return (object)$i; }, $answers);
        // normalize legacy counters/member_id into target_type/target_id when possible
        foreach ($answers as $idx => $ans) {
            if (! isset($ans->target_type) || ! isset($ans->target_id)) {
                $targetType = null;
                $targetId = null;
                if (! empty($ans->member_id)) {
                    $targetType = 'member';
                    $targetId = (int)$ans->member_id;
                } elseif (! empty($ans->counters) && is_array($ans->counters)) {
                    foreach ($ans->counters as $mid => $val) {
                        if ((int)$val > 0) { $targetType = 'member'; $targetId = (int)$mid; break; }
                    }
                }
                $answers[$idx]->target_type = $targetType;
                $answers[$idx]->target_id = $targetId;
            }
        }
        // derive question-level target_type (prefer explicit in answers)
        $questionTargetType = null;
        foreach ($answers as $a) { if (! empty($a->target_type)) { $questionTargetType = $a->target_type; break; } }
        if (empty($questionTargetType)) { $questionTargetType = 'member'; }
        // find quiz to scope members (only show members from the quiz's group)
        $quizObj = null;
        foreach ($json['quizzes'] ?? [] as $qq) { if ((string)($qq['id'] ?? '') === (string)$quizId) { $quizObj = $qq; break; } }
        $quizGroupId = $quizObj['group_id'] ?? null;

        $membersRaw = $json['members'] ?? [];
        if (! empty($quizGroupId)) {
            $membersRaw = array_values(array_filter($membersRaw, function($m) use ($quizGroupId){ return (string)($m['group_id'] ?? '') === (string)$quizGroupId; }));
        }

        $members = array_map(function($i){ return (object)$i; }, $membersRaw);
        $groups = array_map(function($i){ return (object)$i; }, $json['groups'] ?? []);

        // scope albums to the quiz's group when possible
        $albumsRaw = $json['albums'] ?? [];
        if (! empty($quizGroupId)) {
            $albumsRaw = array_values(array_filter($albumsRaw, function($a) use ($quizGroupId){ return (string)($a['group_id'] ?? '') === (string)$quizGroupId; }));
        }
        $albums = array_map(function($i){ return (object)$i; }, $albumsRaw);
        return view('admin.questions.edit', ['quiz_id' => $quizId, 'question' => $question, 'answers' => $answers, 'members' => $members, 'groups' => $groups, 'albums' => $albums, 'question_target_type' => $questionTargetType]);
    }

    public function update(Request $request, $quizId, $questionId)
    {
        $data = $request->validate([
            'answers' => 'array|max:8',
            'answers.*.text' => 'nullable|string|max:1000',
            'target_type' => 'nullable|in:group,member,album',
            'answers.*.target_id' => 'nullable|integer',
        ]);

        $json = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];

        // remove existing answers for this question
        $answers = $json['answers'] ?? [];
        $answers = array_values(array_filter($answers, function($a) use ($questionId){ return (string)($a['question_id'] ?? '') !== (string)$questionId; }));

        // add submitted answers (each answer maps to a single member)
        $incoming = $data['answers'] ?? [];
        $ids = array_column($answers, 'id');
        $max = count($ids) ? max($ids) : 0;
        foreach ($incoming as $i => $a) {
            if (empty($a['text'])) { continue; }
            $max++;
            $answers[] = [
                'id' => $max,
                'question_id' => (int)$questionId,
                'text' => $a['text'],
                'order' => $i + 1,
                'target_type' => $data['target_type'] ?? ($a['target_type'] ?? null),
                'target_id' => isset($a['target_id']) && $a['target_id'] !== '' ? (int)$a['target_id'] : null,
            ];
        }

        $json['answers'] = $answers;
        file_put_contents(resource_path('data/api.json'), json_encode($json, JSON_PRETTY_PRINT));

        return redirect()->route('admin.quizzes.questions.index', $quizId)->with('success', 'Answers saved');
    }
}
