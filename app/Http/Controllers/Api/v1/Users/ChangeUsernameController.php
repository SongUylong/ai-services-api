<?php

namespace App\Http\Controllers\Api\v1\Users;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Users\ChangeUsernameRequest;
use App\Http\Resources\Users\UserResource;
use App\Models\Users\User;

// User Management - APIs for managing username changes
class ChangeUsernameController extends ApiController
{
	// Change user username
	public function update(ChangeUsernameRequest $request, User $user)
	{
		// Self-service route (/v1/user/username) has no {user} param; default to auth user.
		if (!$user->exists) {
			$user = auth('api')->user();
		}

		$this->authorize('updateUsername', $user);

		$oldUsername = $user->username;
		$newUsername = $request->validated()['username'];

		// Check if username is actually changing
		if ($oldUsername === $newUsername) {
			return $this->okWithData(
				new UserResource($user->load('roles')),
				'Username unchanged'
			);
		}

		// Update username
		$user->username = $newUsername;
		$user->save();

		return $this->updated(
			new UserResource($user->load('roles')),
			'Username changed successfully'
		);
	}
}

