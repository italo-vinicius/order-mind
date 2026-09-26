<?php

use App\Enums\UserRole;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\ToolLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;

uses(RefreshDatabase::class);

test('a user can log in and retrieve the current authenticated user', function (): void {
    $user = User::factory()->create(['role' => UserRole::Customer]);

    $response = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertCreated()
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonPath('data.user.role', UserRole::Customer->value)
        ->assertJsonStructure(['data' => ['token', 'token_expires_at', 'user']]);

    $token = $response->json('data.token');
    $accessToken = PersonalAccessToken::query()->sole();

    expect($accessToken->expires_at)->not->toBeNull()
        ->and(now()->diffInMinutes($accessToken->expires_at))->toBeGreaterThanOrEqual(119)
        ->and(now()->diffInMinutes($accessToken->expires_at))->toBeLessThanOrEqual(120);

    $this->withToken($token)->getJson('/api/auth/me')
        ->assertOk()
        ->assertExactJson(['data' => ['user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => UserRole::Customer->value,
        ]]]);
});

test('login validates input, rejects invalid credentials, and limits repeated failures', function (): void {
    $this->postJson('/api/auth/login', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password']);

    foreach (range(1, 5) as $attempt) {
        $this->postJson('/api/auth/login', [
            'email' => 'unknown@ordermind.test',
            'password' => 'wrong-password',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Credenciais inválidas.');
    }

    $this->postJson('/api/auth/login', [
        'email' => 'unknown@ordermind.test',
        'password' => 'wrong-password',
    ])->assertTooManyRequests()
        ->assertJsonPath('message', 'Muitas tentativas de login. Tente novamente em instantes.');
});

test('missing, revoked, and expired tokens cannot access protected routes', function (): void {
    $user = User::factory()->create();

    $this->getJson('/api/auth/me')->assertUnauthorized();

    $revoked = $user->createToken('browser-session', ['*'], now()->addMinutes(120));
    $this->withToken($revoked->plainTextToken)->postJson('/api/auth/logout')->assertNoContent();
    expect(PersonalAccessToken::query()->find($revoked->accessToken->id))->toBeNull();
    app('auth')->forgetGuards();
    $this->withToken($revoked->plainTextToken)->getJson('/api/auth/me')->assertUnauthorized();

    $expired = $user->createToken('browser-session', ['*'], now()->subMinute());
    app('auth')->forgetGuards();
    $this->withToken($expired->plainTextToken)->getJson('/api/auth/me')->assertUnauthorized();
});

test('policies isolate customer records and restrict administration', function (): void {
    $admin = User::factory()->admin()->create();
    $firstCustomer = User::factory()->create();
    $secondCustomer = User::factory()->create();
    $firstOrder = Order::factory()->for($firstCustomer)->create();
    $secondOrder = Order::factory()->for($secondCustomer)->create();
    $conversation = Conversation::factory()->for($secondCustomer)->create();
    $toolLog = ToolLog::factory()->for($secondCustomer)->create();

    expect($firstCustomer->can('view', $firstOrder))->toBeTrue()
        ->and($firstCustomer->can('view', $secondOrder))->toBeFalse()
        ->and($admin->can('view', $secondOrder))->toBeTrue()
        ->and($firstCustomer->can('view', $conversation))->toBeFalse()
        ->and($admin->can('view', $conversation))->toBeFalse()
        ->and($firstCustomer->can('view', $toolLog))->toBeFalse()
        ->and($admin->can('view', $toolLog))->toBeTrue();

    $customerToken = $firstCustomer->createToken('browser-session', ['*'], now()->addMinutes(120));
    $this->withToken($customerToken->plainTextToken)->getJson('/api/admin/access')->assertForbidden();

    $adminToken = $admin->createToken('browser-session', ['*'], now()->addMinutes(120));
    app('auth')->forgetGuards();
    $this->withToken($adminToken->plainTextToken)->getJson('/api/admin/access')->assertNoContent();
});
