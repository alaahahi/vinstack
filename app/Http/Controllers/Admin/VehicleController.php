<?php

namespace App\Http\Controllers\Admin;

use App\Actions\AssignVehicleAction;
use App\Actions\UnassignVehicleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignVehicleRequest;
use App\Enums\VehicleSource;
use App\Models\Dealer;
use App\Models\Vehicle;
use App\Services\VehicleDetailService;
use App\Services\VehicleUploadedImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index(Request $request, VehicleUploadedImageService $gallery): JsonResponse
    {
        $query = Vehicle::query()
            ->with(['activeAssignment.dealer.user:id,name,email', 'uploadedImages']);

        $search = $request->string('search')->trim()->toString();
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('vin', 'like', "%{$search}%")
                    ->orWhere('make', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('vinstack_id', 'like', "%{$search}%");
            });
        }

        $status = $request->input('status');
        if ($status === 'imported') {
            // Legacy UI: «مستوردة» refers to Vinstack sync source, not local status.
            $query->where('source', VehicleSource::Vinstack);
        } elseif ($status) {
            $query->where('status', $status);
        }

        if ($source = $request->input('source')) {
            $allowedSources = [VehicleSource::Vinstack->value, VehicleSource::Manual->value];

            if (in_array($source, $allowedSources, true)) {
                $query->where('source', $source);
            }
        }

        if ($request->filled('dealer_id')) {
            $dealerId = (int) $request->input('dealer_id');

            if ($dealerId > 0) {
                $query->whereHas('activeAssignment', fn ($q) => $q->where('dealer_id', $dealerId));
            }
        } elseif (($dealerName = $request->string('dealer_name')->trim()->toString()) !== '') {
            $query->whereHas('activeAssignment.dealer', fn ($q) => $q->where(
                'company_name',
                'like',
                "%{$dealerName}%",
            ));
        }

        if ($request->filled('sort_field')) {
            $sortField = $request->input('sort_field');
            $sortOrder = $request->input('sort_order', 'desc') === 'asc' ? 'asc' : 'desc';
            $allowedSort = ['id', 'vin', 'make', 'model', 'year', 'price', 'status', 'created_at'];

            if (in_array($sortField, $allowedSort, true)) {
                $query->orderBy($sortField, $sortOrder);
            }
        } else {
            $query->newestFirst();
        }

        $vehicles = $query->paginate(
            perPage: min((int) $request->input('per_page', 50), 100),
            page: (int) $request->input('page', 1),
        );

        $vehicles->through(fn (Vehicle $vehicle) => $gallery->enrichListVehicle($vehicle));

        return response()->json([
            'data' => $vehicles->items(),
            'meta' => [
                'current_page' => $vehicles->currentPage(),
                'last_page' => $vehicles->lastPage(),
                'per_page' => $vehicles->perPage(),
                'total' => $vehicles->total(),
                'from' => $vehicles->firstItem(),
                'to' => $vehicles->lastItem(),
                'has_more' => $vehicles->hasMorePages(),
            ],
        ]);
    }

    public function details(Vehicle $vehicle, VehicleDetailService $details): JsonResponse
    {
        return response()->json([
            'data' => $details->build($vehicle, includeAssignment: true),
        ]);
    }

    public function assign(
        AssignVehicleRequest $request,
        Vehicle $vehicle,
        AssignVehicleAction $action,
    ): JsonResponse {
        $dealer = Dealer::query()->findOrFail($request->integer('dealer_id'));

        $assignment = $action->execute($vehicle, $dealer, $request->user());

        return response()->json([
            'data' => $assignment,
            'message' => 'Vehicle assigned successfully.',
        ]);
    }

    public function unassign(Vehicle $vehicle, UnassignVehicleAction $action): JsonResponse
    {
        if (! $vehicle->activeAssignment()->exists()) {
            return response()->json([
                'message' => 'السيارة غير مسندة لأي تاجر.',
            ], 422);
        }

        $action->execute($vehicle);

        return response()->json([
            'message' => 'تم إلغاء إسناد السيارة.',
        ]);
    }
}
