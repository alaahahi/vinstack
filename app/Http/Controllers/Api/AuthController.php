<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Models\Dealer;
use App\Models\User;
use App\Support\PhoneNormalizer;
use App\Support\TwoFactorToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use FormatsAuthUser;

    public function login(LoginRequest $request): JsonResponse
    {
        if ($request->filled('phone')) {
            return $this->loginWithPhone($request->input('phone'));
        }

        return $this->loginWithEmail($request->input('email'), $request->input('password'));
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userPayload($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out']);
    }

    private function loginWithPhone(string $phone): JsonResponse
    {
        $normalized = PhoneNormalizer::normalize($phone);
        $user = User::query()->byPhone($normalized)->first();

        if (! $user && $normalized !== null) {
            $user = $this->resolveUserFromDealerPhone($normalized);
        }

        if (! $user) {
            throw ValidationException::withMessages([
                'phone' => ['رقم الهاتف غير مسجّل.'],
            ]);
        }

        return $this->respondForUser($user);
    }

    private function resolveUserFromDealerPhone(string $normalized): ?User
    {
        $dealer = Dealer::query()
            ->where('phone', $normalized)
            ->with('user')
            ->first();

        $user = $dealer?->user;

        if ($user === null) {
            return null;
        }

        if ($user->phone === null || $user->phone === '') {
            Log::warning('Dealer login phone matched dealers.phone but users.phone was empty; syncing.', [
                'user_id' => $user->id,
                'dealer_id' => $dealer->id,
                'phone' => $normalized,
            ]);
            $user->phone = $normalized;
            $user->save();
        }

        return $user;
    }

    private function loginWithEmail(string $email, string $password): JsonResponse
    {
        if (! Auth::attempt(['email' => $email, 'password' => $password])) {
            throw ValidationException::withMessages([
                'email' => ['بيانات الدخول غير صحيحة.'],
            ]);
        }

        /** @var User $user */
        $user = Auth::user();
        Auth::logout();

        return response()->json($this->issueToken($user));
    }

    private function respondForUser(User $user): JsonResponse
    {
        if ($user->isAdmin()) {
            return response()->json($this->issueToken($user));
        }

        if (! $user->hasEnabledTwoFactorAuthentication()) {
            return response()->json([
                'two_factor_setup' => true,
                'setup_token' => TwoFactorToken::create($user->id, 'setup'),
            ]);
        }

        return response()->json([
            'two_factor' => true,
            'challenge_token' => TwoFactorToken::create($user->id, 'challenge'),
        ]);
    }
}
