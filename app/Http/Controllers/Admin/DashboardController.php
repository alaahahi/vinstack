<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ShowAdminDashboardRequest;
use App\Services\AdminDashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function show(ShowAdminDashboardRequest $request, AdminDashboardService $dashboard): JsonResponse
    {
        return response()->json([
            'data' => $dashboard->build(),
        ]);
    }
}
