<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\BaseAdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Pagination\LengthAwarePaginator;

class QuizController extends BaseAdminController
{
    public function index()
    {
        $json = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
        $all = $json['quizzes'] ?? [];
        $questionsAll = $json['questions'] ?? [];
        $groups = $json['groups'] ?? [];
        $members = $json['members'] ?? [];

        // build quick lookup maps
        $groupsById = [];
        foreach ($groups as $g) { $groupsById[$g['id']] = (object)$g; }
        $membersById = [];
        foreach ($members as $m) { $membersById[$m['id']] = (object)$m; }

        $page = (int) request('page', 1);
        $perPage = 20;
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        // attach related questions, group and member as objects
        $items = array_map(function($i) use ($questionsAll, $groupsById, $membersById) {
            $quizQuestions = array_values(array_filter($questionsAll, function($q) use ($i){ return (string)($q['quiz_id'] ?? '') === (string)($i['id'] ?? ''); }));
            $i['questions'] = collect($quizQuestions);
            $i['group'] = $groupsById[$i['group_id'] ?? null] ?? null;
            $i['member'] = $membersById[$i['member_id'] ?? null] ?? null;
            return (object)$i;
        }, $items);
        $quizzes = new LengthAwarePaginator($items, $total, $perPage, $page, ['path' => url()->current()]);
        return view('admin.quizzes.index', compact('quizzes'));
    }

    public function create()
    {
        $json = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
        $groups = array_map(function($i){ return (object)$i; }, $json['groups'] ?? []);
        $members = array_map(function($i){ return (object)$i; }, $json['members'] ?? []);
        return view('admin.quizzes.create', compact('groups','members'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'group_id' => 'nullable',
            'member_id' => 'nullable',
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|max:2048',
            'questions' => 'array|size:10',
            'questions.*' => 'required|string',
        ]);
        $json = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
        $items = $json['quizzes'] ?? [];
        $ids = array_column($items, 'id');
        $max = count($ids) ? max($ids) : 0;
        $payload = [
            'id' => $max + 1,
            'group_id' => $data['group_id'] ?? null,
            'member_id' => $data['member_id'] ?? null,
            'name' => $data['name'],
        ];

        if ($request->hasFile('image')) {
            $payload['image'] = $request->file('image')->store('images/quizzes', 'public');
        }

        $items[] = $payload;
        $json['quizzes'] = $items;

        // persist quiz then questions
        file_put_contents(resource_path('data/api.json'), json_encode($json, JSON_PRETTY_PRINT));
        $quizId = $payload['id'];

        $questions = $json['questions'] ?? [];
        foreach ($data['questions'] as $i => $qtext) {
            $qids = array_column($questions, 'id');
            $qmax = count($qids) ? max($qids) : 0;
            $questions[] = [
                'id' => $qmax + 1,
                'quiz_id' => $quizId,
                'text' => $qtext,
                'order' => $i + 1,
            ];
        }
        $json['questions'] = $questions;
        file_put_contents(resource_path('data/api.json'), json_encode($json, JSON_PRETTY_PRINT));

        return redirect()->route('admin.quizzes.index')->with('success', 'Quiz created with 10 questions');
    }

    public function edit($quiz)
    {
        $json = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
        $groups = array_map(function($i){ return (object)$i; }, $json['groups'] ?? []);
        $members = array_map(function($i){ return (object)$i; }, $json['members'] ?? []);
        // normalize $quiz param to id when route-model binding may pass an object
        $quizId = $quiz;
        if (is_object($quiz) && isset($quiz->id)) { $quizId = $quiz->id; }
        $quizData = null;
        foreach ($json['quizzes'] ?? [] as $item) { if ((string)($item['id'] ?? '') === (string)$quizId) { $quizData = $item; break; } }
        $questions = array_values(array_filter($json['questions'] ?? [], function($q) use ($quizId) { return (string)($q['quiz_id'] ?? '') === (string)$quizId; }));
        usort($questions, function($a,$b){ return ($a['order'] ?? 0) <=> ($b['order'] ?? 0); });
        // ensure exactly 10 slots when editing
        $questions = array_map(function($q){ return (object)$q; }, $questions);
        return view('admin.quizzes.edit', ['quiz' => (object)($quizData ?? []), 'groups' => $groups, 'members' => $members, 'questions' => $questions]);
    }

    public function update(Request $request, $quiz)
    {
        $data = $request->validate([
            'group_id' => 'nullable',
            'member_id' => 'nullable',
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|max:2048',
        ]);

        $json = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
        $existing = [];
        foreach ($json['quizzes'] ?? [] as $item) { if ((string)($item['id'] ?? '') === (string)$quiz) { $existing = $item; break; } }

        if ($request->hasFile('image')) {
            if (! empty($existing['image'])) {
                Storage::disk('public')->delete($existing['image']);
            }
            $data['image'] = $request->file('image')->store('images/quizzes', 'public');
        }

        $updated = [];
        foreach ($json['quizzes'] ?? [] as $item) {
            if ((string)($item['id'] ?? '') === (string)$quiz) {
                $item = array_merge($item, [
                    'group_id' => $data['group_id'] ?? null,
                    'member_id' => $data['member_id'] ?? null,
                    'name' => $data['name'],
                    'image' => $data['image'] ?? $existing['image'] ?? null,
                ]);
            }
            $updated[] = $item;
        }
        $json['quizzes'] = $updated;
        file_put_contents(resource_path('data/api.json'), json_encode($json, JSON_PRETTY_PRINT));

        // update questions if provided
        if ($request->has('questions') && is_array($request->input('questions'))) {
            $qtexts = $request->input('questions');
            $allQuestions = $json['questions'] ?? [];

            // split existing questions for this quiz and others
            $existingForQuiz = array_values(array_filter($allQuestions, function($q) use ($quiz){ return (string)($q['quiz_id'] ?? '') === (string)$quiz; }));
            usort($existingForQuiz, function($a,$b){ return ($a['order'] ?? 0) <=> ($b['order'] ?? 0); });
            $otherQuestions = array_values(array_filter($allQuestions, function($q) use ($quiz){ return (string)($q['quiz_id'] ?? '') !== (string)$quiz; }));

            // determine current max id across all questions
            $allIds = array_column($allQuestions, 'id');
            $maxQ = count($allIds) ? max($allIds) : 0;

            $newQuestions = $otherQuestions;
            $keptIds = [];
            foreach ($qtexts as $i => $txt) {
                if (! is_string($txt) || trim($txt) === '') { continue; }
                if (isset($existingForQuiz[$i])) {
                    $q = $existingForQuiz[$i];
                    $q['text'] = $txt;
                    $q['order'] = $i + 1;
                    $newQuestions[] = $q;
                    $keptIds[] = $q['id'];
                } else {
                    $maxQ++;
                    $q = [
                        'id' => $maxQ,
                        'quiz_id' => (int)$quiz,
                        'text' => $txt,
                        'order' => $i + 1,
                    ];
                    $newQuestions[] = $q;
                    $keptIds[] = $q['id'];
                }
            }

            // write back questions
            $json['questions'] = $newQuestions;

            // remove answers only for questions that were removed from THIS quiz
            $answers = $json['answers'] ?? [];
            // determine which existing question ids for this quiz were removed
            $existingIds = array_column($existingForQuiz, 'id');
            $removedIds = array_values(array_diff($existingIds, $keptIds));
            if (!empty($removedIds)) {
                $answers = array_values(array_filter($answers, function($a) use ($removedIds){
                    if (!isset($a['question_id'])) { return true; }
                    // drop only if the answer belonged to one of the removed question ids
                    if (in_array($a['question_id'], $removedIds)) { return false; }
                    return true;
                }));
            }
            $json['answers'] = $answers;

            file_put_contents(resource_path('data/api.json'), json_encode($json, JSON_PRETTY_PRINT));
        }

        return redirect()->route('admin.quizzes.index')->with('success', 'Quiz updated');
    }

    public function destroy($quiz)
    {
        $json = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
        $existing = [];
        $new = [];
        foreach ($json['quizzes'] ?? [] as $item) {
            if ((string)($item['id'] ?? '') === (string)$quiz) { $existing = $item; continue; }
            $new[] = $item;
        }
        if (! empty($existing['image'])) { Storage::disk('public')->delete($existing['image']); }
        $json['quizzes'] = $new;
        file_put_contents(resource_path('data/api.json'), json_encode($json, JSON_PRETTY_PRINT));
        return redirect()->route('admin.quizzes.index')->with('success', 'Quiz deleted');
    }
}
