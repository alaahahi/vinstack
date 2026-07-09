<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Dealer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminDealerNotificationLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_set_dealer_notification_locale(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $dealerUser = User::factory()->create([
            'role' => UserRole::Dealer,
            'locale' => 'ar',
            'locale_customized' => false,
        ]);
        $dealer = Dealer::query()->create([
            'user_id' => $dealerUser->id,
            'company_name' => 'Showroom',
        ]);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/dealers/{$dealer->id}/notification-locale", [
            'locale' => 'en',
        ])
            ->assertOk()
            ->assertJsonPath('data.notification_locale', 'en')
            ->assertJsonPath('data.notification_locale_customized', true);

        $dealerUser->refresh();

        $this->assertSame('en', $dealerUser->locale);
        $this->assertTrue($dealerUser->locale_customized);
    }

    public function test_admin_can_reset_dealer_notification_locale_to_default(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $dealerUser = User::factory()->create([
            'role' => UserRole::Dealer,
            'locale' => 'en',
            'locale_customized' => true,
        ]);
        $dealer = Dealer::query()->create([
            'user_id' => $dealerUser->id,
            'company_name' => 'Showroom',
        ]);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/dealers/{$dealer->id}/notification-locale", [
            'locale' => 'default',
        ])
            ->assertOk()
            ->assertJsonPath('data.notification_locale', 'ckb')
            ->assertJsonPath('data.notification_locale_customized', false);

        $dealerUser->refresh();

        $this->assertFalse($dealerUser->locale_customized);
    }
}
