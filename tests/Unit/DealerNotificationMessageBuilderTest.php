<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\Dealer;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\DealerNotificationMessageBuilder;
use App\Support\SupportedLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DealerNotificationMessageBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_assigned_message_uses_dealer_locale(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Dealer,
            'locale' => 'en',
        ]);
        $dealer = Dealer::query()->create([
            'user_id' => $user->id,
            'company_name' => 'Acme Motors',
            'phone' => '+9647500000001',
        ]);
        $vehicle = Vehicle::query()->create([
            'vin' => 'TESTVIN123',
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2022,
            'raw_data' => ['color' => 'White', 'destination' => 'Dubai'],
        ]);

        $message = app(DealerNotificationMessageBuilder::class)->vehicleAssigned($dealer, $vehicle, 'Kamal Kamal');

        $this->assertStringContainsString('A new vehicle has been added to your account at Kamal Kamal', $message);
        $this->assertStringContainsString('VIN: TESTVIN123', $message);
        $this->assertStringContainsString('Make: Toyota', $message);
        $this->assertStringContainsString('Destination: Dubai', $message);
    }

    public function test_vehicle_assigned_defaults_to_kurdish_when_locale_not_set(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Dealer,
            'locale' => null,
        ]);
        $dealer = Dealer::query()->create([
            'user_id' => $user->id,
            'company_name' => 'Acme Motors',
            'phone' => '+9647500000001',
        ]);
        $vehicle = Vehicle::query()->create([
            'vin' => 'TESTVIN123',
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2022,
        ]);

        $message = app(DealerNotificationMessageBuilder::class)->vehicleAssigned($dealer, $vehicle, 'Kamal Kamal');

        $this->assertStringContainsString('ئۆتۆمبێلێکی نوێ زیادکرا بۆ هەژمارەکەتان لە کۆمپانیای Kamal Kamal', $message);
        $this->assertStringContainsString('ژمارەی شاسی: TESTVIN123', $message);
    }

    public function test_supported_locale_defaults_to_kurdish_for_notifications(): void
    {
        $this->assertSame('ckb', SupportedLocale::forNotifications(null));
        $this->assertSame('en', SupportedLocale::forNotifications('en'));
        $this->assertSame('ckb', SupportedLocale::forNotifications('fr'));
        $this->assertFalse(SupportedLocale::isCustomized(null));
        $this->assertTrue(SupportedLocale::isCustomized('ar'));
    }
}
