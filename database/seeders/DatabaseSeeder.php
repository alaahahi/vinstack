<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Dealer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@vinstack.local'],
            [
                'name' => 'Admin',
                'phone' => '07900000001',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
            ],
        );

        $dealerUser = User::query()->updateOrCreate(
            ['email' => 'dealer@vinstack.local'],
            [
                'name' => 'Demo Dealer',
                'phone' => '07511077812',
                'password' => Hash::make('password'),
                'role' => UserRole::Dealer,
            ],
        );

        Dealer::query()->updateOrCreate(
            ['user_id' => $dealerUser->id],
            [
                'company_name' => 'Demo Motors',
                'phone' => '+966500000000',
            ],
        );

        $this->command?->info('Admin phone: 07900000001');
        $this->command?->info('Admin email (backup tab): admin@vinstack.local / password');
        $this->command?->info('Dealer phone: 07511077812');
    }
}
