<?php

namespace App\Http\Controllers\Api\v1\Users;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Users\UpdateUserSettingRequest;
use App\Http\Resources\Users\UserSettingResource;
use App\Services\Users\UserSettingService;

// User Settings Management - APIs for managing user settings (dark mode, remember me)
class UserSettingController extends ApiController
{
    public function __construct(
        protected UserSettingService $userSettingService
    ) {}

    // Get authenticated user's settings
    public function show()
    {
        $user = request()->user();

        // Policy check: users can only view their own settings (or admin can view any)
        $this->authorize('viewSettings', $user);

        $setting = $this->userSettingService->getOrCreateSettings($user);

        return $this->okWithData(new UserSettingResource($setting));
    }

    // Update authenticated user's settings
    public function update(UpdateUserSettingRequest $request)
    {
        $user = $request->user();

        // Policy check: users can only update their own settings (or admin can update any)
        $this->authorize('updateSettings', $user);

        $setting = $this->userSettingService->updateSettings(
            $user,
            $request->only(['theme', 'language', 'preferred_ai_model_id'])
        );

        return $this->updated(
            new UserSettingResource($setting),
            'Settings updated successfully'
        );
    }
}
