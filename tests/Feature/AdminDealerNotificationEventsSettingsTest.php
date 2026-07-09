<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\DealerNotificationEvents;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminDealerNotificationEventsSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_include_dealer_notification_event_catalog(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/wa-queue/settings');

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'dealer_notification_events' => [
                        DealerNotificationEvents::VEHICLE_UPDATED => true,
                    ],
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'dealer_notification_events',
                    'dealer_notification_event_catalog',
                ],
            ]);
    }

    public function test_admin_can_disable_vehicle_updated_event(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        $response = $this->putJson('/api/admin/wa-queue/settings', [
            'dealer_notification_events' => [
                DealerNotificationEvents::VEHICLE_UPDATED => false,
            ],
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'dealer_notification_events' => [
                        DealerNotificationEvents::VEHICLE_UPDATED => false,
                        DealerNotificationEvents::VEHICLE_ASSIGNED => true,
                    ],
                ],
            ]);
    }
}
