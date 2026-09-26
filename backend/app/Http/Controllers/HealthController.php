<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::select('SELECT 1');
        } catch (Throwable) {
            Log::warning('Database health check failed.');

            return response()->json([
                'data' => ['status' => 'unavailable', 'database' => 'unavailable'],
            ], 503);
        }

        return response()->json([
            'data' => ['status' => 'ok', 'database' => 'ok'],
        ]);
    }
}
