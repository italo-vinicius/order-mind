<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = (string) Str::uuid();
        $startedAt = hrtime(true);

        Log::withContext(['request_id' => $requestId]);

        try {
            $response = $next($request);
        } catch (\Throwable $exception) {
            Log::error('HTTP request failed.', [
                'method' => $request->method(),
                'path' => '/'.$request->path(),
                'duration_ms' => $this->duration($startedAt),
            ]);

            throw $exception;
        }

        $response->headers->set('X-Request-ID', $requestId);
        Log::info('HTTP request completed.', [
            'method' => $request->method(),
            'path' => '/'.$request->path(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $this->duration($startedAt),
        ]);

        return $response;
    }

    private function duration(int $startedAt): int
    {
        return (int) round((hrtime(true) - $startedAt) / 1_000_000);
    }
}
