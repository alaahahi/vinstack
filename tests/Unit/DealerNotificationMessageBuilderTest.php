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

    public function test_vehicle_assigned_message_uses_customized_dealer_locale(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Dealer,
            'locale' => 'en',
            'locale_customized' => true,
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
    }

    public function test_vehicle_assigned_defaults_to_kurdish_when_locale_not_customized(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Dealer,
            'locale' => 'ar',
            'locale_customized' => false,
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

    public function test_supported_locale_uses_kurdish_until_customized(): void
    {
        $this->assertSame('ckb', SupportedLocale::forNotifications(null, false));
        $this->assertSame('ckb', SupportedLocale::forNotifications('ar', false));
        $this->assertSame('en', SupportedLocale::forNotifications('en', true));
        $this->assertSame('ar', SupportedLocale::forNotifications('ar', true));
    }

    public function test_login_credentials_message_uses_email_and_welcome(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Dealer,
            'email' => 'dealer@example.com',
            'locale' => 'ar',
            'locale_customized' => true,
        ]);
        $dealer = Dealer::query()->create([
            'user_id' => $user->id,
            'company_name' => 'Acme Motors',
            'phone' => '07504781630',
        ]);

        $message = app(DealerNotificationMessageBuilder::class)->loginCredentials(
            $dealer,
            'dealer@example.com',
            'secret123',
            'https://vinstack.test/login',
        );

        $this->assertStringContainsString('مرحباً بكم في', $message);
        $this->assertStringContainsString('البريد الإلكتروني: dealer@example.com', $message);
        $this->assertStringNotContainsString('07504781630', $message);
        $this->assertStringNotContainsString('اسم المستخدم', $message);
    }
}
