<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_checks_the_database_and_returns_success(): void
    {
        DB::shouldReceive('select')->once()->with('SELECT 1')->andReturn([]);

        $this->getJson('/api/health')
            ->assertOk()
            ->assertExactJson(['data' => ['status' => 'ok', 'database' => 'ok']]);
    }

    public function test_database_failure_returns_503_without_connection_details(): void
    {
        DB::shouldReceive('select')->once()->andThrow(new RuntimeException('private connection details'));

        $this->getJson('/api/health')
            ->assertStatus(503)
            ->assertExactJson(['data' => ['status' => 'unavailable', 'database' => 'unavailable']]);
    }

    public function test_cors_allows_only_the_configured_frontend(): void
    {
        $this->withHeaders([
            'Origin' => config('cors.allowed_origins.0'),
            'Access-Control-Request-Method' => 'GET',
        ])->options('/api/health')
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', config('cors.allowed_origins.0'));

        $this->withHeaders([
            'Origin' => 'https://untrusted.example',
            'Access-Control-Request-Method' => 'GET',
        ])->options('/api/health')
            // A single configured origin is returned verbatim, never reflected or wildcarded.
            ->assertHeader('Access-Control-Allow-Origin', config('cors.allowed_origins.0'));
    }
}
