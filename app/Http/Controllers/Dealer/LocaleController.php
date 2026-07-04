<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dealer\UpdateDealerLocaleRequest;
use App\Support\SupportedLocale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function update(UpdateDealerLocaleRequest $request): JsonResponse
    {
        $user = $request->user();
        $locale = SupportedLocale::normalize($request->string('locale')->toString());

        $user->update([
            'locale' => $locale,
            'locale_customized' => true,
        ]);

        return response()->json([
            'data' => ['locale' => $locale],
            'message' => 'Locale updated.',
        ]);
    }
}
