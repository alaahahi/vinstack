<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Dealer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminDealerStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_dealer_and_receives_credentials_delivery_status(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/dealers', [
            'name' => 'Dealer User',
            'email' => 'dealer@example.com',
            'password' => 'secret123',
            'company_name' => 'Showroom Name',
            'phone' => '07511077812',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user.name', 'Dealer User')
            ->assertJsonPath('data.company_name', 'Showroom Name')
            ->assertJsonPath('login_credentials.username', '07511077812')
            ->assertJsonPath('login_credentials.password', 'secret123')
            ->assertJsonPath('credentials_notification.ok', false)
            ->assertJsonPath('credentials_notification.message', 'WA Queue غير مفعّل — راجع إعدادات الإشعارات.');

        $this->assertDatabaseHas('users', [
            'email' => 'dealer@example.com',
            'role' => UserRole::Dealer->value,
        ]);

        $this->assertDatabaseHas('dealers', [
            'company_name' => 'Showroom Name',
            'phone' => '07511077812',
        ]);
    }

    public function test_admin_dealers_index_is_ordered_by_id(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        $firstUser = User::factory()->create(['role' => UserRole::Dealer, 'name' => 'Second']);
        $secondUser = User::factory()->create(['role' => UserRole::Dealer, 'name' => 'First']);

        $firstDealer = Dealer::query()->create([
            'user_id' => $firstUser->id,
            'company_name' => 'Z Showroom',
        ]);

        $secondDealer = Dealer::query()->create([
            'user_id' => $secondUser->id,
            'company_name' => 'A Showroom',
        ]);

        $response = $this->getJson('/api/admin/dealers');

        $response->assertOk();
        $this->assertSame(
            [$firstDealer->id, $secondDealer->id],
            array_column($response->json('data'), 'id'),
        );
    }
}
