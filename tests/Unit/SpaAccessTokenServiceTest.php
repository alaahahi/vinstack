<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Services\Auth\SpaAccessTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SpaAccessTokenServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_issue_does_not_insert_personal_access_tokens_row(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);

        $before = DB::table('personal_access_tokens')->count();
        $token = app(SpaAccessTokenService::class)->issue($user);
        $after = DB::table('personal_access_tokens')->count();

        $this->assertSame($before, $after);
        $this->assertTrue(str_starts_with($token, SpaAccessTokenService::TOKEN_PREFIX));
    }

    public function test_signed_token_authenticates_api_me(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Admin,
            'email' => 'admin-spa@example.com',
        ]);

        $token = app(SpaAccessTokenService::class)->issue($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('user.email', 'admin-spa@example.com');

        $this->assertSame(0, DB::table('personal_access_tokens')->count());
    }

    public function test_find_token_returns_stateless_model(): void
    {
        $user = User::factory()->create(['role' => UserRole::Dealer]);
        $plain = app(SpaAccessTokenService::class)->issue($user);

        $model = PersonalAccessToken::findToken($plain);

        $this->assertInstanceOf(PersonalAccessToken::class, $model);
        $this->assertTrue($model->stateless);
        $this->assertSame($user->id, $model->tokenable_id);
    }

    public function test_login_issues_signed_token_without_db_row(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Admin,
            'email' => 'login-spa@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'login-spa@example.com',
            'password' => 'secret123',
        ]);

        $response->assertOk();
        $token = $response->json('token');

        $this->assertIsString($token);
        $this->assertTrue(str_starts_with($token, SpaAccessTokenService::TOKEN_PREFIX));
        $this->assertSame(0, DB::table('personal_access_tokens')->count());
    }

    public function test_legacy_db_token_is_migrated_via_response_header(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);
        $plain = $user->createToken('spa')->plainTextToken;

        $this->assertSame(1, DB::table('personal_access_tokens')->count());

        $response = $this->withHeader('Authorization', 'Bearer '.$plain)
            ->getJson('/api/me');

        $response->assertOk();
        $signed = $response->headers->get('X-Spa-Token');

        $this->assertIsString($signed);
        $this->assertTrue(str_starts_with($signed, SpaAccessTokenService::TOKEN_PREFIX));

        $this->withHeader('Authorization', 'Bearer '.$signed)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);
    }
}
