<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $key = $this->throttleKey($credentials['email'], $request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'message' => 'Muitas tentativas de login. Tente novamente em instantes.',
                'retry_after' => RateLimiter::availableIn($key),
            ], 429);
        }

        $user = User::query()->where('email', $credentials['email'])->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($key, 60);

            return response()->json([
                'message' => 'Credenciais inválidas.',
                'errors' => ['email' => ['Credenciais inválidas.']],
            ], 422);
        }

        RateLimiter::clear($key);
        $expiresAt = now()->addMinutes((int) config('sanctum.expiration'));
        $token = $user->createToken('browser-session', ['*'], $expiresAt);

        return response()->json([
            'data' => [
                'token' => $token->plainTextToken,
                'token_expires_at' => $expiresAt->toISOString(),
                'user' => $this->userData($user),
            ],
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(status: 204);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => ['user' => $this->userData($request->user())]]);
    }

    /** @return array{id: int, name: string, email: string, role: string} */
    private function userData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
        ];
    }

    private function throttleKey(string $email, ?string $ip): string
    {
        return 'login:'.sha1(mb_strtolower($email).'|'.($ip ?? 'unknown'));
    }
}
