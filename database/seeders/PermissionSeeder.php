<?php

namespace Database\Seeders;

use Spatie\Permission\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // User Management
            'view any user',
            'create user',
            'update any user',
            'delete any user',
            'toggle user status',

            // Role Management
            'view roles',
            'assign roles',
            'remove roles',

            // Profile Management
            'upload profile image',
            'view own profile',
            'update own profile',
            'upload own profile image',

            // Credential Management
            'change any password',
            'change any username',
            'change own password',
            'change own username',

            // Settings Management
            'view any settings',
            'update any settings',
            'view own settings',
            'update own settings',

            // Conversation Management
            'view any conversation',
            'view own conversations',
            'create conversation',
            'update any conversation',
            'update own conversations',
            'delete any conversation',
            'delete own conversations',
            'restore any conversation',
            'restore own conversations',
            'force delete conversation',

            // Message Management
            'view any message',
            'view own messages',
            'create message',
            'regenerate any message',
            'regenerate own messages',
            'delete any message',
            'delete own messages',

            // Feedback Management
            'create any feedback',
            'create own feedback',
            'delete any feedback',
            'delete own feedback',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'api',
            ]);
        }

        $this->command->info('Permissions seeded successfully!');
    }
}
