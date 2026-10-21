<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Ensure client role exists for web and api guards
        Role::firstOrCreate(['name' => 'client', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'client', 'guard_name' => 'api']);

        $user = User::updateOrCreate(
            ['phone' => '0096512345678'],
            [
                'name' => 'Client User',
                'email' => 'client@app.com',
                'password' => Hash::make('12345678'),
                'user_status' => UserStatus::ACTIVE,
                'phone_verified_at' => now(),
                'email_verified_at' => now(),
                'preferred_language' => 'ar',
            ]
        );

        $user->syncRoles(['client']);
    }
}
