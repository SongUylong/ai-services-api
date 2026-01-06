<?php

namespace Database\Seeders;

use App\Models\Users\User;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
		$adminEmail = env('ADMIN_EMAIL');
		$adminPassword = env('ADMIN_PASSWORD');

		if (blank($adminEmail) || blank($adminPassword)) {
			throw new \InvalidArgumentException('ADMIN_EMAIL and ADMIN_PASSWORD must be set in the environment before running the seeder.');
		}

        // 1. Ensure basic roles exist (admin, user)
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'api']
        );

        $userRole = Role::firstOrCreate(
            ['name' => 'user', 'guard_name' => 'api']
        );

		// 2. Create or update the Admin User using env overrides
		$admin = User::updateOrCreate(
			['email' => $adminEmail],
			[
				'username' => 'admin',
				'first_name' => 'Super',
				'last_name' => 'Admin',
				'phone_number' => '0123456789',
				'password' => Hash::make($adminPassword),
				'is_active' => true,
				'last_password_change' => now(),
			]
		);

        // Assign Admin Role using Spatie's assignRole method
        $admin->assignRole('admin');

        // 3. Create 50 Regular Users
        User::factory()
            ->count(50)
            ->create()
            ->each(function ($user) {
                // Assign User Role to each factory user
                $user->assignRole('user');
            });

        $this->command->info('Users seeded successfully!');
    }
}
