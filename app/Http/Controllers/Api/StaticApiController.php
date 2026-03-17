<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StaticApiController extends Controller
{
    public function __construct()
    {
        // Protect write endpoints; listing/show remain public
        $this->middleware('auth')->only(['store','update','destroy']);
    }
    protected function loadData()
    {
        $path = resource_path('data/api.json');
        if (! file_exists($path)) {
            return ['groups' => [], 'members' => [], 'albums' => [], 'quizzes' => [], 'questions' => [], 'answers' => []];
        }

        $json = file_get_contents($path);
        return json_decode($json, true) ?? [];
    }

    public function list(Request $request, $resource = null)
    {
        $data = $this->loadData();

        if ($resource === null) {
            return response()->json($data);
        }

        return response()->json($data[$resource] ?? []);
    }

    public function show($resource, $id)
    {
        $data = $this->loadData();
        $items = $data[$resource] ?? [];

        foreach ($items as $item) {
            if (isset($item['id']) && (string)$item['id'] === (string)$id) {
                return response()->json($item);
            }
        }

        return response()->json(null, 404);
    }

    public function store(Request $request, $resource)
    {
        $data = $this->loadData();
        $items = $data[$resource] ?? [];

        $payload = $request->all();
        // generate an id if missing
        $ids = array_column($items, 'id');
        $max = count($ids) ? max($ids) : 0;
        $payload['id'] = $payload['id'] ?? ($max + 1);

        $items[] = $payload;
        $data[$resource] = $items;

        // write back
        file_put_contents(resource_path('data/api.json'), json_encode($data, JSON_PRETTY_PRINT));

        return response()->json($payload, 201);
    }

    public function update(Request $request, $resource, $id)
    {
        $data = $this->loadData();
        $items = $data[$resource] ?? [];

        $found = false;
        foreach ($items as &$item) {
            if (isset($item['id']) && (string)$item['id'] === (string)$id) {
                $item = array_merge($item, $request->all());
                $item['id'] = $item['id'];
                $found = true;
                break;
            }
        }

        if (! $found) {
            return response()->json(null, 404);
        }

        $data[$resource] = $items;
        file_put_contents(resource_path('data/api.json'), json_encode($data, JSON_PRETTY_PRINT));

        return response()->json($item);
    }

    public function destroy($resource, $id)
    {
        $data = $this->loadData();
        $items = $data[$resource] ?? [];

        $new = [];
        $found = false;
        foreach ($items as $item) {
            if (isset($item['id']) && (string)$item['id'] === (string)$id) {
                $found = true;
                continue;
            }
            $new[] = $item;
        }

        if (! $found) {
            return response()->json(null, 404);
        }

        $data[$resource] = $new;
        file_put_contents(resource_path('data/api.json'), json_encode($data, JSON_PRETTY_PRINT));

        return response()->json(null, 204);
    }
}
