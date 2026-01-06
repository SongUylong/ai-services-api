<?php

namespace App\Services\Users;

use App\Models\Users\User;
use App\Models\Users\UserSetting;

class UserSettingService
{
    // Get or create user settings
    public function getOrCreateSettings(User $user): UserSetting
    {
        return $user->setting ?? $user->setting()->create(UserSetting::defaults());
    }

    // Update user settings
    public function updateSettings(User $user, array $data): UserSetting
    {
        $setting = $this->getOrCreateSettings($user);
        $setting->update($data);
        
        return $setting->fresh();
    }

    // Get user setting value
    public function getSetting(User $user, string $key, $default = null)
    {
        $setting = $this->getOrCreateSettings($user);
        
        return $setting->{$key} ?? $default;
    }

    // Update a specific setting
    public function updateSetting(User $user, string $key, $value): UserSetting
    {
        return $this->updateSettings($user, [$key => $value]);
    }
}

