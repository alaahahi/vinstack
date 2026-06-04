<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDealerRequest;
use App\Http\Requests\Admin\UpdateDealerRequest;
use App\Models\Dealer;
use App\Models\User;
use App\Support\DealerPresence;
use App\Support\RecoveryCodesArchive;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DealerController extends Controller
{
    public function index(): JsonResponse
    {
        $dealers = Dealer::query()
            ->withCount('activeAssignments as vehicles_count')
            ->with('user:id,name,email,last_seen_at,recovery_codes_archive,two_factor_confirmed_at')
            ->orderBy('company_name')
            ->get()
            ->map(fn (Dealer $dealer) => $this->formatDealer($dealer));

        return response()->json(['data' => $dealers]);
    }

    public function store(StoreDealerRequest $request): JsonResponse
    {
        $user = User::query()->create([
            'name' => $request->string('name'),
            'email' => $request->string('email'),
            'phone' => $request->input('phone'),
            'password' => Hash::make($request->string('password')),
            'role' => UserRole::Dealer,
        ]);

        $dealer = Dealer::query()->create([
            'user_id' => $user->id,
            'company_name' => $request->string('company_name'),
            'phone' => $request->input('phone'),
        ]);

        $dealer->load('user:id,name,email,last_seen_at,recovery_codes_archive,two_factor_confirmed_at');

        return response()->json(['data' => $this->formatDealer($dealer)], 201);
    }

    public function update(UpdateDealerRequest $request, Dealer $dealer): JsonResponse
    {
        $validated = $request->validated();
        $user = $dealer->user;

        if (array_key_exists('name', $validated)) {
            $user->name = $validated['name'];
            $user->save();
        }

        if (array_key_exists('phone', $validated)) {
            $user->phone = $validated['phone'];
            $user->save();
        }

        $dealerUpdates = [];

        if (array_key_exists('company_name', $validated)) {
            $dealerUpdates['company_name'] = $validated['company_name'];
        }

        if (array_key_exists('phone', $validated)) {
            $dealerUpdates['phone'] = $validated['phone'];
        }

        if ($dealerUpdates !== []) {
            $dealer->update($dealerUpdates);
        }

        $dealer->load('user:id,name,email,last_seen_at,recovery_codes_archive,two_factor_confirmed_at');

        return response()->json([
            'data' => $this->formatDealer($dealer),
            'message' => 'تم تحديث بيانات التاجر.',
        ]);
    }

    public function destroy(Dealer $dealer): JsonResponse
    {
        if ($dealer->activeAssignments()->exists()) {
            return response()->json([
                'message' => 'لا يمكن حذف التاجر لأنه مرتبط بسيارات',
            ], 422);
        }

        $user = $dealer->user;

        DB::transaction(function () use ($user, $dealer) {
            if ($user) {
                $user->tokens()->delete();
                $user->delete();
            } else {
                $dealer->delete();
            }
        });

        return response()->json([
            'message' => 'تم حذف التاجر.',
        ]);
    }

    public function recoveryCodes(Dealer $dealer): JsonResponse
    {
        $user = $dealer->user;

        if (! $user || ! RecoveryCodesArchive::hasArchive($user)) {
            return response()->json([
                'message' => 'لا توجد رموز محفوظة — يجب إعادة إنشائها من التاجر.',
            ], 404);
        }

        $codes = RecoveryCodesArchive::decrypt($user);

        if ($codes === null || $codes === []) {
            return response()->json([
                'message' => 'لا توجد رموز محفوظة — يجب إعادة إنشائها من التاجر.',
            ], 404);
        }

        return response()->json([
            'recovery_codes' => $codes,
            'archived_at' => $user->recovery_codes_archived_at?->toIso8601String(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatDealer(Dealer $dealer): array
    {
        $user = $dealer->user;

        return [
            'id' => $dealer->id,
            'company_name' => $dealer->company_name,
            'phone' => $dealer->phone,
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ] : null,
            'last_seen_at' => $user?->last_seen_at?->toIso8601String(),
            'is_online' => $user ? DealerPresence::isOnline($user) : false,
            'has_recovery_codes_archive' => $user
                ? RecoveryCodesArchive::hasArchive($user)
                : false,
            'two_factor_enabled' => $user?->two_factor_confirmed_at !== null,
            'vehicles_count' => (int) ($dealer->vehicles_count ?? $dealer->activeAssignments()->count()),
        ];
    }
}
