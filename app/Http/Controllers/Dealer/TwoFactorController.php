<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Support\RecoveryCodesArchive;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;

class TwoFactorController extends Controller
{
    public function regenerateRecoveryCodes(
        Request $request,
        GenerateNewRecoveryCodes $generate,
    ): JsonResponse {
        $user = $request->user();

        if (! $user->hasEnabledTwoFactorAuthentication()) {
            return response()->json([
                'message' => 'المصادقة الثنائية غير مفعّلة.',
            ], 422);
        }

        $generate($user);

        $user = $user->fresh();

        RecoveryCodesArchive::archive($user, $user->recoveryCodes());

        return response()->json([
            'recovery_codes' => $user->recoveryCodes(),
            'message' => 'تم إنشاء رموز استرداد جديدة. الرموز السابقة لم تعد صالحة.',
        ]);
    }
}
