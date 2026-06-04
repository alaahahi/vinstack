<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Services\VehicleImageDownloadService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VehicleImageDownloadController extends Controller
{
    public function download(
        Request $request,
        Vehicle $vehicle,
        VehicleImageDownloadService $downloads,
    ): StreamedResponse|Response {
        if ($request->user()?->isDealer()) {
            $this->ensureDealerAssigned($request, $vehicle);
        }

        $url = $request->string('url')->trim()->toString();

        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            abort(422, 'Invalid image URL.');
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (! in_array($scheme, ['http', 'https'], true)) {
            abort(422, 'Invalid image URL scheme.');
        }

        if (! $downloads->urlBelongsToVehicle($vehicle, $url)) {
            abort(403, 'Image URL is not part of this vehicle gallery.');
        }

        return $downloads->download($vehicle, $url);
    }

    protected function ensureDealerAssigned(Request $request, Vehicle $vehicle): void
    {
        $dealerId = $request->user()->dealer?->id;

        $assigned = $vehicle->assignments()
            ->where('dealer_id', $dealerId)
            ->where('is_active', true)
            ->exists();

        if (! $assigned) {
            abort(403, 'This vehicle is not assigned to you.');
        }
    }
}
