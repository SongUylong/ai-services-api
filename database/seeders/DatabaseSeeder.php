<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,              // 1. Create roles first
            PermissionSeeder::class,        // 2. Create permissions
            RolePermissionSeeder::class,    // 3. Assign permissions to roles
            AiModelSeeder::class,           // 4. Create AI models
            UserSeeder::class,              // 5. Create users and assign roles
        ]);
    }
}
