<?php

namespace App\Http\Controllers\Api\v1\Users;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Users\ToggleUserStatusRequest;
use App\Http\Resources\Users\UserResource;
use App\Models\Users\User;

// User Management - APIs for toggling user status
class ToggleUserStatusController extends ApiController
{
	// Toggle user active status
	public function update(ToggleUserStatusRequest $request, User $user)
	{
		$this->authorize('toggleStatus', User::class);

		$validated = $request->validated();

		$user->is_active = $validated['is_active'];
		$user->save();

		return $this->updated(
			new UserResource($user->load('roles')),
			$user->is_active ? 'User activated successfully' : 'User deactivated successfully'
		);
	}
}

