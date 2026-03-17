<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ApiController extends Controller
{
    protected function success($data, int $status = 200): JsonResponse
    {
        return response()->json($data, $status);
    }
}
