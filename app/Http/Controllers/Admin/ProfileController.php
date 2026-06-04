<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\FormatsAuthUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAdminProfileRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use FormatsAuthUser;

    public function show(Request $request): JsonResponse
    {
        $user = $this->adminOrAbort($request->user());

        return response()->json([
            'data' => $this->profilePayload($user),
        ]);
    }

    public function update(UpdateAdminProfileRequest $request): JsonResponse
    {
        $user = $this->adminOrAbort($request->user());
        $validated = $request->validated();

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
        ]);
        $user->save();
        $user->refresh();

        return response()->json([
            'data' => $this->profilePayload($user),
            'user' => $this->userPayload($user),
            'message' => 'تم تحديث الملف الشخصي.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function profilePayload(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role->value,
        ];
    }

    protected function adminOrAbort(?User $user): User
    {
        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Admin profile not found.');
        }

        return $user;
    }
}
