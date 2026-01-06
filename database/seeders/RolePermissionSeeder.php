<?php

namespace Database\Seeders;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get roles
        $adminRole = Role::where('name', 'admin')->first();
        $userRole = Role::where('name', 'user')->first();

        if (!$adminRole || !$userRole) {
            $this->command->error('Roles not found! Please run RoleSeeder first.');
            return;
        }

        // Define permissions for each role
        $adminPermissions = [
            // User Management - Admin has all
            'view any user',
            'create user',
            'update any user',
            'delete any user',
            'toggle user status',
            
            // Role Management - Admin has all
            'view roles',
            'assign roles',
            'remove roles',
            
            // Profile Management - Admin can upload images for anyone
            'upload profile image',
            
            // Credential Management - Admin can change any user's credentials (includes their own)
            'change any password',
            'change any username',
            
            // Settings Management - Admin can manage all settings (includes their own)
            'view any settings',
            'update any settings',

            // Conversation Management - Admin has all permissions
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

            // Message Management - Admin has all permissions
            'view any message',
            'view own messages',
            'create message',
            'regenerate any message',
            'regenerate own messages',
            'delete any message',
            'delete own messages',

            // Feedback Management - Admin has all permissions
            'create any feedback',
            'create own feedback',
            'delete any feedback',
            'delete own feedback',
        ];

        $userPermissions = [
            // Profile Management - User can only manage their own
            'view own profile',
            'update own profile',
            'upload own profile image',
            
            // Credential Management - User can only change their own
            'change own password',
            'change own username',
            
            // Settings Management - User can only manage their own
            'view own settings',
            'update own settings',

            // Conversation Management - User can only manage their own
            'view own conversations',
            'create conversation',
            'update own conversations',
            'delete own conversations',
            'restore own conversations',

            // Message Management - User can only manage their own
            'view own messages',
            'create message',
            'regenerate own messages',
            'delete own messages',

            // Feedback Management - User can only manage their own
            'create own feedback',
            'delete own feedback',
        ];

        // Assign permissions to admin role using Spatie's givePermissionTo method
        $adminRole->givePermissionTo($adminPermissions);

        // Assign permissions to user role
        $userRole->givePermissionTo($userPermissions);

        $this->command->info('Role permissions assigned successfully!');
        $this->command->info("Admin role: {$adminRole->permissions()->count()} permissions");
        $this->command->info("User role: {$userRole->permissions()->count()} permissions");
    }
}
