<?php

use Illuminate\Support\Facades\DB;

test('uses the isolated PostgreSQL test database', function (): void {
    expect(config('database.default'))->toBe('pgsql')
        ->and(DB::connection()->getDriverName())->toBe('pgsql')
        ->and(DB::scalar('SELECT current_database()'))->toBe('ordermind_test');
});
