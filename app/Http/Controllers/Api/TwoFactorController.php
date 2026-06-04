<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ConfirmTwoFactorRequest;
use App\Http\Requests\Api\TwoFactorChallengeRequest;
use App\Http\Requests\Api\TwoFactorSetupRequest;
use App\Models\User;
use App\Support\RecoveryCodesArchive;
use App\Support\TwoFactorToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;

class TwoFactorController extends Controller
{
    use FormatsAuthUser;

    public function setup(
        TwoFactorSetupRequest $request,
        EnableTwoFactorAuthentication $enable,
    ): JsonResponse {
        $user = $this->resolveDealerFromToken('setup', $request->validated('setup_token'));

        $enable($user, force: empty($user->two_factor_secret));

        $user->refresh();

        return response()->json([
            'qr_svg' => $user->twoFactorQrCodeSvg(),
            'recovery_codes' => $user->recoveryCodes(),
        ]);
    }

    public function confirm(
        ConfirmTwoFactorRequest $request,
        ConfirmTwoFactorAuthentication $confirm,
    ): JsonResponse {
        $token = $request->validated('setup_token');
        $user = $this->resolveDealerFromToken('setup', $token);

        try {
            $confirm($user, $request->validated('code'));
        } catch (ValidationException $e) {
            throw ValidationException::withMessages([
                'code' => ['رمز التحقق غير صحيح.'],
            ]);
        }

        TwoFactorToken::forget('setup', $token);

        $user = $user->fresh();

        RecoveryCodesArchive::archiveOnce($user, $user->recoveryCodes());

        return response()->json([
            ...$this->issueToken($user),
            'recovery_codes' => $user->recoveryCodes(),
            'message' => 'تم تفعيل المصادقة الثنائية.',
        ]);
    }

    public function challenge(TwoFactorChallengeRequest $request): JsonResponse
    {
        $token = $request->validated('challenge_token');
        $user = $this->resolveDealerFromToken('challenge', $token, 'challenge_token');

        if (! $request->validated('code') && ! $request->input('recovery_code')) {
            throw ValidationException::withMessages([
                'code' => ['أدخل رمز التحقق أو رمز الاسترداد.'],
            ]);
        }

        if (! $this->verifyCode($user, $request->input('code'), $request->input('recovery_code'))) {
            throw ValidationException::withMessages([
                'code' => ['رمز التحقق غير صحيح.'],
            ]);
        }

        TwoFactorToken::forget('challenge', $token);

        return response()->json($this->issueToken($user));
    }

    protected function verifyCode(User $user, ?string $code, ?string $recoveryCode): bool
    {
        if ($recoveryCode && $this->consumeRecoveryCode($user, $recoveryCode)) {
            return true;
        }

        if (! $code || empty($user->two_factor_secret)) {
            return false;
        }

        return app(TwoFactorAuthenticationProvider::class)->verify(
            Fortify::currentEncrypter()->decrypt($user->two_factor_secret),
            $code,
        );
    }

    protected function consumeRecoveryCode(User $user, string $recoveryCode): bool
    {
        $match = Collection::make($user->recoveryCodes())->first(
            fn (string $stored) => hash_equals($stored, $recoveryCode),
        );

        if (! $match) {
            return false;
        }

        $user->replaceRecoveryCode($match);

        return true;
    }
}
