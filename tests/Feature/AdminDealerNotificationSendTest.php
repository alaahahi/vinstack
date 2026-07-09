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
}
