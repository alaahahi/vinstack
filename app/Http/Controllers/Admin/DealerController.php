<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDealerRequest;
use App\Http\Requests\Admin\UpdateDealerNotificationLocaleRequest;
use App\Http\Requests\Admin\UpdateDealerRequest;
use App\Models\Dealer;
use App\Models\User;
use App\Services\ContainerService;
use App\Services\DealerNotificationService;
use App\Support\DealerPresence;
use App\Support\RecoveryCodesArchive;
use App\Support\SupportedLocale;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DealerController extends Controller
{
    public function index(): JsonResponse
    {
        $dealers = Dealer::query()
            ->withCount('activeAssignments as vehicles_count')
            ->with('user:id,name,email,phone,locale,locale_customized,last_seen_at,recovery_codes_archive,two_factor_confirmed_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Dealer $dealer) => $this->formatDealer($dealer));

        return response()->json([
            'data' => $dealers,
            'meta' => [
                'login_url' => $this->loginPageUrl(),
            ],
        ]);
    }

    public function summary(ContainerService $containers): JsonResponse
    {
        $dealers = Dealer::query()
            ->withCount('activeAssignments as vehicles_count')
            ->orderByDesc('id')
            ->get(['id', 'company_name']);

        $data = $dealers->map(function (Dealer $dealer) use ($containers) {
            return [
                'id' => $dealer->id,
                'company_name' => $dealer->company_name,
                'vehicles_count' => (int) $dealer->vehicles_count,
                'containers_count' => count($containers->listForDealer($dealer)),
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }

    public function store(
        StoreDealerRequest $request,
        DealerNotificationService $dealerNotifications,
    ): JsonResponse
    {
        $plainPassword = $request->string('password')->toString();

        [$user, $dealer] = DB::transaction(function () use ($request, $plainPassword) {
            $user = User::query()->create([
                'name' => $request->string('name'),
                'email' => $request->string('email'),
                'phone' => $request->input('phone'),
                'password' => Hash::make($plainPassword),
                'role' => UserRole::Dealer,
            ]);

            $dealer = Dealer::query()->create([
                'user_id' => $user->id,
                'company_name' => $request->string('company_name'),
                'phone' => $request->input('phone'),
                'login_password_encrypted' => $plainPassword,
            ]);

            return [$user, $dealer];
        });

        $dealer->load('user:id,name,email,phone,locale,locale_customized,last_seen_at,recovery_codes_archive,two_factor_confirmed_at');

        $formatted = $this->formatDealer($dealer);
        $credentialsNotification = $dealerNotifications->sendLoginCredentials($dealer, $plainPassword);

        return response()->json([
            'data' => $formatted,
            'login_credentials' => [
                'username' => $formatted['login_identifier'],
                'password' => $plainPassword,
                'url' => $this->loginPageUrl(),
            ],
            'credentials_notification' => [
                'ok' => $credentialsNotification['ok'],
                'message' => $credentialsNotification['message'],
                'status' => $credentialsNotification['status'] ?? null,
            ],
        ], 201);
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

        if (! empty($validated['password'] ?? null)) {
            $user->password = Hash::make($validated['password']);
            $user->save();
            $dealer->login_password_encrypted = $validated['password'];
            $dealer->save();
        }

        $dealer->load('user:id,name,email,phone,locale,locale_customized,last_seen_at,recovery_codes_archive,two_factor_confirmed_at');

        return response()->json([
            'data' => $this->formatDealer($dealer),
            'message' => 'تم تحديث بيانات التاجر.',
        ]);
    }

    public function updateNotificationLocale(
        UpdateDealerNotificationLocaleRequest $request,
        Dealer $dealer,
    ): JsonResponse {
        $user = $dealer->user;

        if (! $user) {
            abort(404, 'Dealer user not found.');
        }

        $locale = $request->string('locale')->toString();

        if ($locale === 'default') {
            $user->locale_customized = false;
        } else {
            $user->locale = SupportedLocale::normalize($locale);
            $user->locale_customized = true;
        }

        $user->save();

        $dealer->load('user:id,name,email,phone,locale,locale_customized,last_seen_at,recovery_codes_archive,two_factor_confirmed_at');

        return response()->json([
            'data' => $this->formatDealer($dealer),
            'message' => 'تم تحديث لغة إشعارات التاجر.',
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
            'login_identifier' => $this->loginIdentifier($dealer),
            'login_url' => $this->loginPageUrl(),
            'copy_password' => $this->copyPasswordForAdmin($dealer),
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
            'locale' => $user?->locale,
            'locale_customized' => (bool) $user?->locale_customized,
            'notification_locale' => SupportedLocale::forNotifications(
                $user?->locale,
                (bool) $user?->locale_customized,
            ),
            'notification_locale_customized' => (bool) $user?->locale_customized,
        ];
    }

    protected function loginPageUrl(): string
    {
        return rtrim(config('app.url', url('/')), '/').'/login';
    }

    protected function copyPasswordForAdmin(Dealer $dealer): ?string
    {
        $password = $dealer->login_password_encrypted;

        if (! is_string($password) || trim($password) === '') {
            return null;
        }

        return $password;
    }

    protected function loginIdentifier(Dealer $dealer): string
    {
        $phone = trim((string) ($dealer->phone ?? ''));

        if ($phone !== '') {
            return $phone;
        }

        return trim((string) ($dealer->user?->email ?? ''));
    }
}
