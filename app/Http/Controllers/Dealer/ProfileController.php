<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Api\FormatsAuthUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dealer\UpdateDealerProfileRequest;
use App\Models\Dealer;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ContainerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use FormatsAuthUser;

    public function show(Request $request, ContainerService $containers): JsonResponse
    {
        $user = $request->user();
        $dealer = $this->dealerOrAbort($user);

        return response()->json([
            'data' => $this->profilePayload($user, $dealer, $containers),
        ]);
    }

    public function update(UpdateDealerProfileRequest $request, ContainerService $containers): JsonResponse
    {
        $user = $request->user();
        $dealer = $this->dealerOrAbort($user);
        $validated = $request->validated();

        $user->phone = $validated['phone'];
        $user->save();

        $dealer->update([
            'company_name' => $validated['company_name'],
            'phone' => $validated['phone'],
        ]);

        $user->refresh();
        $dealer->refresh();

        return response()->json([
            'data' => $this->profilePayload($user, $dealer, $containers),
            'user' => $this->userPayload($user),
            'message' => 'تم تحديث الملف الشخصي.',
        ]);
    }

    public function stats(Request $request, ContainerService $containers): JsonResponse
    {
        $dealer = $this->dealerOrAbort($request->user());

        return response()->json([
            'vehicles_count' => $this->vehiclesCount($dealer),
            'containers_count' => count($containers->listForDealer($dealer)),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function profilePayload(User $user, Dealer $dealer, ContainerService $containers): array
    {
        return [
            'phone' => $user->phone,
            'company_name' => $dealer->company_name,
            'two_factor_enabled' => $user->hasEnabledTwoFactorAuthentication(),
            'stats' => [
                'vehicles_count' => $this->vehiclesCount($dealer),
                'containers_count' => count($containers->listForDealer($dealer)),
            ],
        ];
    }

    protected function vehiclesCount(Dealer $dealer): int
    {
        return Vehicle::query()
            ->whereHas('assignments', function ($q) use ($dealer) {
                $q->where('dealer_id', $dealer->id)->where('is_active', true);
            })
            ->count();
    }

    protected function dealerOrAbort(User $user): Dealer
    {
        $dealer = $user->dealer;

        if (! $dealer) {
            abort(403, 'Dealer profile not found.');
        }

        return $dealer;
    }
}
