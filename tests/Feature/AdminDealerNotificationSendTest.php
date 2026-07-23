<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Dealer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminDealerNotificationSendTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_send_manual_notification_to_all_dealers_with_phone(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        $firstUser = User::factory()->create(['role' => UserRole::Dealer]);
        $secondUser = User::factory()->create(['role' => UserRole::Dealer]);
        $thirdUser = User::factory()->create(['role' => UserRole::Dealer]);

        Dealer::query()->create([
            'user_id' => $firstUser->id,
            'company_name' => 'Dealer A',
            'phone' => '07501111111',
        ]);
        Dealer::query()->create([
            'user_id' => $secondUser->id,
            'company_name' => 'Dealer B',
            'phone' => '07502222222',
        ]);
        Dealer::query()->create([
            'user_id' => $thirdUser->id,
            'company_name' => 'Dealer C',
            'phone' => null,
        ]);

        $response = $this->postJson('/api/admin/dealer-notifications/send', [
            'send_to_all' => true,
            'message' => 'رسالة جماعية للتجار',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('sent', 0)
            ->assertJsonPath('failed', 2);
    }

    public function test_send_to_all_requires_message(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/dealer-notifications/send', [
            'send_to_all' => true,
            'message' => '',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    public function test_admin_can_paginate_notification_log(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        $dealerUser = User::factory()->create(['role' => UserRole::Dealer]);
        $dealer = Dealer::query()->create([
            'user_id' => $dealerUser->id,
            'company_name' => 'Dealer Log',
            'phone' => '07503333333',
        ]);

        for ($i = 1; $i <= 12; $i++) {
            \App\Models\DealerNotificationLog::query()->create([
                'dealer_id' => $dealer->id,
                'created_by' => $admin->id,
                'phone' => $dealer->phone,
                'message' => "رسالة رقم {$i}",
                'source' => 'manual',
                'event' => 'dealer.manual_notification',
                'channel' => 'wa_queue',
                'wa_queue_id' => 1000 + $i,
                'wa_queue_status' => 'queued',
            ]);
        }

        $this->getJson('/api/admin/dealer-notifications?page=1&per_page=10')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.page', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 12)
            ->assertJsonPath('meta.has_more', true);

        $this->getJson('/api/admin/dealer-notifications?page=2&per_page=10')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.page', 2)
            ->assertJsonPath('meta.has_more', false);
    }
}
