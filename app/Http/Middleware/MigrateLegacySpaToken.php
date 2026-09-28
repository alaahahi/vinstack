<?php

namespace App\Http\Middleware;

use App\Models\PersonalAccessToken;
use App\Services\Auth\SpaAccessTokenService;
use App\Support\SqliteBusy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Temporary bridge: when a legacy DB Sanctum token authenticates the request,
 * issue a signed SPA token and expose it so the browser can replace localStorage
 * without forcing logout.
 */
class MigrateLegacySpaToken
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $user = $request->user();
        $accessToken = $user?->currentAccessToken();

        if (! $user || ! $accessToken instanceof PersonalAccessToken) {
            return $response;
        }

        if ($accessToken->stateless ?? false) {
            return $response;
        }

        $signed = app(SpaAccessTokenService::class)->issue($user);
        $response->headers->set('X-Spa-Token', $signed);
        $response->headers->set('Access-Control-Expose-Headers', 'X-Spa-Token');

        // Best-effort cleanup of the old DB row (never fail the response).
        SqliteBusy::soft(static function () use ($accessToken): void {
            $accessToken->delete();
        });

        return $response;
    }
}
