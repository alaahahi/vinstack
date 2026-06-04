<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Support\DealerPresence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HeartbeatController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        DealerPresence::touch($request->user());

        return response()->json(['ok' => true]);
    }
}
