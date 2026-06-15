<?php

namespace App\Actions;

use App\Enums\VehicleSource;
use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use App\Models\VinstackSetting;
use App\Services\VinstackService;
use App\Support\VehicleGalleryMerger;
use App\Support\VehicleImageStages;
use Illuminate\Support\Arr;

class SyncVehiclesAction
{
    public function __construct(
        protected VinstackService $vinstack,
    ) {}

    /**
     * @return array{created: int, updated: int, total: int, restorable: list<array{id: int, vin: string|null}>}
     */
    public function execute(): array
    {
        $items = $this->vinstack->autos();

        $created = 0;
        $updated = 0;
        $restorableById = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $vinstackId = (string) Arr::get($item, 'id');

            if ($vinstackId === '') {
                continue;
            }

            $payload = $this->mapAuto($item);
            $vin = Arr::get($item, 'vin');

            $vehicle = Vehicle::withTrashed()->where('vinstack_id', $vinstackId)->first();

            if ($vehicle) {
                if ($vehicle->trashed()) {
                    $this->trackRestorable($restorableById, $vehicle);

                    continue;
                }

                if (in_array($vehicle->source, [VehicleSource::Manual, VehicleSource::NujoomAlJazeera], true)) {
                    continue;
                }

                $vehicle->update(VehicleGalleryMerger::mergeSyncPayload($vehicle, $payload));
                $updated++;

                continue;
            }

            if (is_string($vin) && $vin !== '') {
                $trashedByVin = Vehicle::onlyTrashed()->where('vin', strtoupper($vin))->first();

                if ($trashedByVin) {
                    $this->trackRestorable($restorableById, $trashedByVin);

                    continue;
                }
            }

            Vehicle::query()->create([
                ...$payload,
                'source' => VehicleSource::Vinstack,
                'vinstack_id' => $vinstackId,
                'status' => VehicleStatus::Available,
            ]);
            $created++;
        }

        VinstackSetting::current()->update([
            'last_sync_at' => now(),
        ]);

        return [
            'created' => $created,
            'updated' => $updated,
            'total' => count($items),
            'restorable' => array_values($restorableById),
        ];
    }

    /**
     * @param  array<int, array{id: int, vin: string|null}>  $restorableById
     */
    protected function trackRestorable(array &$restorableById, Vehicle $vehicle): void
    {
        $restorableById[$vehicle->id] = [
            'id' => $vehicle->id,
            'vin' => $vehicle->vin,
        ];
    }

    protected function mapAuto(array $item): array
    {
        $imagesByStage = VehicleImageStages::resolve($item);
        $images = [];

        foreach (VehicleImageStages::STAGES as $stage) {
            foreach ($imagesByStage[$stage] as $url) {
                if (! in_array($url, $images, true)) {
                    $images[] = $url;
                }
            }
        }

        return [
            'vin' => Arr::get($item, 'vin'),
            'make' => Arr::get($item, 'make'),
            'model' => Arr::get($item, 'model'),
            'year' => Arr::get($item, 'year'),
            'price' => Arr::get($item, 'price'),
            'images' => $images,
            'raw_data' => [
                ...$item,
                'images_by_stage' => $imagesByStage,
            ],
        ];
    }
}
