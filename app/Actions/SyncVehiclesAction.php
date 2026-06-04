<?php

namespace App\Actions;

use App\Enums\VehicleStatus;
use App\Models\VinstackSetting;
use App\Models\Vehicle;
use App\Services\VinstackService;
use App\Support\VehicleImageStages;
use Illuminate\Support\Arr;

class SyncVehiclesAction
{
    public function __construct(
        protected VinstackService $vinstack,
    ) {}

    /**
     * @return array{created: int, updated: int, total: int}
     */
    public function execute(): array
    {
        $items = $this->vinstack->autos();

        $created = 0;
        $updated = 0;

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $vinstackId = (string) Arr::get($item, 'id');

            if ($vinstackId === '') {
                continue;
            }

            $payload = $this->mapAuto($item);

            $vehicle = Vehicle::query()->where('vinstack_id', $vinstackId)->first();

            if ($vehicle) {
                $vehicle->update($payload);
                $updated++;
            } else {
                Vehicle::query()->create([
                    ...$payload,
                    'vinstack_id' => $vinstackId,
                    'status' => VehicleStatus::Available,
                ]);
                $created++;
            }
        }

        VinstackSetting::current()->update([
            'last_sync_at' => now(),
        ]);

        return [
            'created' => $created,
            'updated' => $updated,
            'total' => count($items),
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
