<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $json = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
        $quizzes = $json['quizzes'] ?? [];
        $groups = $json['groups'] ?? [];
        // apply optional group filter: ?group=ID or ?group=none
        $groupFilter = $request->query('group');
        if ($groupFilter !== null) {
            if ($groupFilter === 'none') {
                $quizzes = array_values(array_filter($quizzes, function($q){ return empty($q['group_id']) && empty($q['member_id']); }));
            } else {
                $quizzes = array_values(array_filter($quizzes, function($q) use ($groupFilter){ return (string)($q['group_id'] ?? '') === (string)$groupFilter; }));
            }
        }
        // ensure objects and defaults
        $quizzes = array_map(function($q){ return (object) array_merge(['image' => null, 'name' => 'Quiz '.($q['id'] ?? '?')], $q); }, $quizzes);
        $groups = array_map(function($g){ return (object)$g; }, $groups);
        return view('quizzes.index', ['quizzes' => $quizzes, 'groups' => $groups, 'groupFilter' => $groupFilter]);
    }

    public function show($id)
    {
        $json = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
        $quiz = null;
        foreach ($json['quizzes'] ?? [] as $q) { if ((string)($q['id'] ?? '') === (string)$id) { $quiz = (object)$q; break; } }
        if (! $quiz) { abort(404); }
        $questions = array_values(array_filter($json['questions'] ?? [], function($q) use ($id){ return (string)($q['quiz_id'] ?? '') === (string)$id; }));
        $count = count($questions);
        return view('quizzes.show', ['quiz' => $quiz, 'questions_count' => $count]);
    }

    public function start($id)
    {
        $json = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
        $questions = array_values(array_filter($json['questions'] ?? [], function($q) use ($id){ return (string)($q['quiz_id'] ?? '') === (string)$id; }));
        if (empty($questions)) { return redirect()->route('quizzes.show', $id)->with('error','No questions'); }
        // build run payload: shuffle questions and answers
        shuffle($questions);
        $run = ['quiz_id' => (int)$id, 'questions' => [], 'answers' => []];
        // build answer lookups for robust matching
        $allAnswers = $json['answers'] ?? [];
        $answersByQuestionId = [];
        foreach ($allAnswers as $a) {
            $qid = isset($a['question_id']) ? (string)$a['question_id'] : null;
            if ($qid === null) { continue; }
            $answersByQuestionId[$qid][] = $a;
        }

        foreach ($questions as $q) {
            $qs = ['id' => $q['id'], 'text' => $q['text'] ?? '', 'order' => $q['order'] ?? null];
            $qid = (string)($q['id'] ?? '');
            $answers = $answersByQuestionId[$qid] ?? [];

            // fallback: some datasets store answers keyed by question order (1..N)
            if (empty($answers) && isset($q['order'])) {
                $answers = $answersByQuestionId[(string)($q['order'])] ?? [];
            }

            // fallback: maybe answers were saved with numeric keys cast differently; try loose match over all answers
            if (empty($answers)) {
                foreach ($allAnswers as $a) {
                    if ((string)($a['question_id'] ?? '') === (string)$q['id'] || (string)($a['question_id'] ?? '') === (string)($q['order'] ?? '')) {
                        $answers[] = $a;
                    }
                }
            }

            shuffle($answers);
            $ansItems = [];
            foreach ($answers as $a) {
                $ansItems[] = [
                    'id' => $a['id'],
                    'text' => $a['text'] ?? '',
                    'target_type' => $a['target_type'] ?? (!empty($a['member_id']) ? 'member' : null),
                    'target_id' => $a['target_id'] ?? ($a['member_id'] ?? null),
                ];
            }
            $qs['answers'] = $ansItems;
            $run['questions'][] = $qs;
        }
        // save to session
        session(["quiz_run.$id" => ['quiz' => $run, 'index' => 0, 'responses' => []]]);
        return redirect()->route('quizzes.take', $id);
    }

    public function take($id)
    {
        $state = session("quiz_run.$id");
        if (empty($state)) { return redirect()->route('quizzes.start', $id); }
        $run = $state['quiz'];
        $index = $state['index'] ?? 0;
        // defensive check: if the stored run questions are out of sync with the
        // current JSON (for example after editing questions), regenerate the run
        // by clearing the session and redirecting to start(). This prevents the
        // take form from showing a single/partial question set.
        $json = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
        $currentQuestions = array_values(array_filter($json['questions'] ?? [], function($q) use ($id){ return (string)($q['quiz_id'] ?? '') === (string)$id; }));
        $expectedCount = count($currentQuestions);
        if ($expectedCount !== count($run['questions'])) {
            session()->forget("quiz_run.$id");
            return redirect()->route('quizzes.start', $id);
        }

        if (! isset($run['questions'][$index])) { return redirect()->route('quizzes.result', $id); }
        $question = (object)$run['questions'][$index];
        $total = count($run['questions']);
        return view('quizzes.take', ['quiz_id' => $id, 'question' => $question, 'index' => $index, 'total' => $total]);
    }

    public function answer(Request $request, $id)
    {
        $state = session("quiz_run.$id");
        if (empty($state)) { return redirect()->route('quizzes.start', $id); }
        $run = $state['quiz'];
        $index = $state['index'] ?? 0;
        $question = $run['questions'][$index] ?? null;
        $choice = $request->validate(['choice' => 'required|integer']);
        // store response
        $responses = $state['responses'] ?? [];
        $responses[] = (int)$choice['choice'];
        $index++;
        session(["quiz_run.$id" => ['quiz' => $run, 'index' => $index, 'responses' => $responses]]);
        if ($index >= count($run['questions'])) {
            return redirect()->route('quizzes.result', $id);
        }
        return redirect()->route('quizzes.take', $id);
    }

    public function result($id)
    {
        $state = session("quiz_run.$id");
        if (empty($state)) { return redirect()->route('quizzes.start', $id); }
        $run = $state['quiz'];
        $responses = $state['responses'] ?? [];
        $json = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
        $members = $json['members'] ?? [];
        $groups = $json['groups'] ?? [];
        $albums = $json['albums'] ?? [];
        // find quiz metadata
        $quizMeta = null;
        foreach ($json['quizzes'] ?? [] as $q) { if ((string)($q['id'] ?? '') === (string)$id) { $quizMeta = $q; break; } }
        $isKnowledgeQuiz = false;
        $knowledgeMemberId = null;
        if ($quizMeta && !empty($quizMeta['member_id'])) {
            $knowledgeMemberId = (int)$quizMeta['member_id'];
            if (stripos($quizMeta['name'] ?? '', 'how well do you know') !== false) {
                $isKnowledgeQuiz = true;
            }
        }
        $membersById = [];
        $groupsById = [];
        $albumsById = [];
        foreach ($members as $m) { $membersById[$m['id']] = $m; }
        foreach ($groups as $g) { $groupsById[$g['id']] = $g; }
        foreach ($albums as $al) { $albumsById[$al['id']] = $al; }

        $memberScores = [];
        $groupScores = [];
        $albumScores = [];

        // determine preferred target type for this quiz by counting available answer types
        $typeCounts = ['member' => 0, 'group' => 0, 'album' => 0];
        foreach ($run['questions'] as $q) {
            foreach ($q['answers'] as $a) {
                $tt = $a['target_type'] ?? null;
                if ($tt && isset($typeCounts[$tt])) { $typeCounts[$tt]++; }
            }
        }
        $preferredType = null;
        arsort($typeCounts);
        $topType = array_key_first($typeCounts);
        if ($typeCounts[$topType] > 0) { $preferredType = $topType; }

        // build fast lookup of answers from run
        $answersById = [];
        foreach ($run['questions'] as $q) {
            foreach ($q['answers'] as $a) { $answersById[$a['id']] = $a; }
        }

        if ($isKnowledgeQuiz) {
            // compute correct count: a correct answer is one whose target_type is 'member' and target_id matches the quiz member
            $total = count($run['questions']);
            $correct = 0;
            foreach ($responses as $answerId) {
                $a = $answersById[$answerId] ?? null;
                if (!empty($a) && ($a['target_type'] ?? null) === 'member' && (int)($a['target_id'] ?? 0) === $knowledgeMemberId) {
                    $correct++;
                }
                // wrong answers are ignored (treated as null target)
            }
            $percent = $total > 0 ? round(($correct / $total) * 100) : 0;
            $resultType = 'percent';
            $result = (object)['percent' => $percent, 'correct' => $correct, 'total' => $total, 'member_id' => $knowledgeMemberId];
            return view('quizzes.result', ['quiz_id' => $id, 'resultType' => $resultType, 'result' => $result, 'members' => $members]);
        }

        // tally responses into scores for non-knowledge quizzes
        foreach ($responses as $answerId) {
            $a = $answersById[$answerId] ?? null;
            if (empty($a)) { continue; }
            $tt = $a['target_type'] ?? null;
            $tid = $a['target_id'] ?? null;
            if ($tt === 'member' && $tid) {
                $memberScores[$tid] = ($memberScores[$tid] ?? 0) + 1;
            } elseif ($tt === 'group' && $tid) {
                $groupScores[$tid] = ($groupScores[$tid] ?? 0) + 1;
                $groupMembers = array_values(array_filter($members, function($mm) use ($tid){ return (string)($mm['group_id'] ?? '') === (string)$tid; }));
                $count = count($groupMembers) ?: 1;
                foreach ($groupMembers as $gm) { $memberScores[$gm['id']] = ($memberScores[$gm['id']] ?? 0) + (1 / $count); }
            } elseif ($tt === 'album' && $tid) {
                $albumScores[$tid] = ($albumScores[$tid] ?? 0) + 1;
                $album = $albumsById[$tid] ?? null;
                $gid = $album['group_id'] ?? null;
                if ($gid) {
                    $groupScores[$gid] = ($groupScores[$gid] ?? 0) + 1;
                    $groupMembers = array_values(array_filter($members, function($mm) use ($gid){ return (string)($mm['group_id'] ?? '') === (string)$gid; }));
                    $count = count($groupMembers) ?: 1;
                    foreach ($groupMembers as $gm) { $memberScores[$gm['id']] = ($memberScores[$gm['id']] ?? 0) + (1 / $count); }
                }
            }
        }
        // determine top scores per type
        $topMember = null; $topGroup = null; $topAlbum = null;
        $topMemberScore = 0; $topGroupScore = 0; $topAlbumScore = 0;
        if (!empty($memberScores)) { arsort($memberScores); $topMemberId = (int) array_key_first($memberScores); $topMemberScore = $memberScores[$topMemberId]; $topMember = $membersById[$topMemberId] ?? null; }
        if (!empty($groupScores)) { arsort($groupScores); $topGroupId = (int) array_key_first($groupScores); $topGroupScore = $groupScores[$topGroupId]; $topGroup = $groupsById[$topGroupId] ?? null; }
        if (!empty($albumScores)) { arsort($albumScores); $topAlbumId = (int) array_key_first($albumScores); $topAlbumScore = $albumScores[$topAlbumId]; $topAlbum = $albumsById[$topAlbumId] ?? null; }

        // choose the result type: prefer the quiz's dominant answer type when possible
        $resultType = 'member';
        $result = $topMember ? (object)$topMember : null;
        if (!empty($preferredType)) {
            if ($preferredType === 'album' && $topAlbum) { $resultType = 'album'; $result = (object)$topAlbum; }
            elseif ($preferredType === 'group' && $topGroup) { $resultType = 'group'; $result = (object)$topGroup; }
            elseif ($preferredType === 'member' && $topMember) { $resultType = 'member'; $result = (object)$topMember; }
        } else {
            if ($topAlbumScore >= $topGroupScore && $topAlbumScore > $topMemberScore && $topAlbum) {
                $resultType = 'album'; $result = (object)$topAlbum;
            } elseif ($topGroupScore > $topMemberScore && $topGroupScore >= $topAlbumScore && $topGroup) {
                $resultType = 'group'; $result = (object)$topGroup;
            }
        }

        if (empty($result)) { 
            return view('quizzes.result', ['quiz_id' => $id, 'resultType' => null, 'result' => null, 'members' => $members]); 
        }

        // --- Persist simple quiz stats into the static api.json ---
        // structure: quiz_stats[quizId][type][id] => count
        $quizStats = $json['quiz_stats'] ?? [];
        $quizStats[$id] = $quizStats[$id] ?? [];

        if ($resultType === 'percent') {
            // bucket percent into ranges
            $p = (int)($result->percent ?? 0);
            if ($p <= 30) { $bucket = '0-30'; }
            elseif ($p <= 60) { $bucket = '31-60'; }
            elseif ($p <= 90) { $bucket = '61-90'; }
            else { $bucket = '91-100'; }
            $quizStats[$id]['percent_buckets'] = $quizStats[$id]['percent_buckets'] ?? [];
            $quizStats[$id]['percent_buckets'][$bucket] = ($quizStats[$id]['percent_buckets'][$bucket] ?? 0) + 1;
        } else {
            // increment count for the resulting entity
            $tid = null;
            if ($resultType === 'member') { $tid = $result->id ?? $result->member_id ?? null; }
            elseif ($resultType === 'group') { $tid = $result->id ?? null; }
            elseif ($resultType === 'album') { $tid = $result->id ?? null; }
            if ($tid) {
                $quizStats[$id][$resultType] = $quizStats[$id][$resultType] ?? [];
                $key = (string)$tid;
                $quizStats[$id][$resultType][$key] = ($quizStats[$id][$resultType][$key] ?? 0) + 1;
            }
        }

        $json['quiz_stats'] = $quizStats;
        file_put_contents(resource_path('data/api.json'), json_encode($json, JSON_PRETTY_PRINT));

        // build candidates list for display: all options present in run payload for the chosen/result types
        $candidates = [];
        if ($resultType === 'percent') {
            // percent buckets will be displayed directly from quizStats
            $candidates = [];
        } else {
            $seen = [];
            foreach ($run['questions'] as $q) {
                foreach ($q['answers'] as $a) {
                    if (($a['target_type'] ?? null) === $resultType && ! empty($a['target_id'])) {
                        $k = (string)$a['target_id'];
                        if (isset($seen[$k])) { continue; }
                        $seen[$k] = true;
                        $ent = null;
                        if ($resultType === 'member') { $ent = $membersById[$k] ?? null; }
                        if ($resultType === 'group') { $ent = $groupsById[$k] ?? null; }
                        if ($resultType === 'album') { $ent = $albumsById[$k] ?? null; }
                        if ($ent) { $candidates[] = (object) array_merge(['id' => $k], $ent); }
                    }
                }
            }
        }

        return view('quizzes.result', ['quiz_id' => $id, 'resultType' => $resultType, 'result' => $result, 'members' => $members, 'quizStats' => $quizStats[$id] ?? [], 'candidates' => $candidates]);
    }
}
