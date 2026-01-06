<?php

namespace App\Http\Controllers\Api\v1\Users;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Users\AssignRoleRequest;
use App\Http\Resources\Users\UserResource;
use App\Models\Users\User;
use App\Services\Users\UserRoleService;
use Spatie\Permission\Models\Role;

// User Role Management - APIs for managing user roles
class UserRoleController extends ApiController
{
	public function __construct(
		protected UserRoleService $userRoleService
	) {}

	// Show all roles that user has
	public function show(User $user)
	{
		$this->authorize('viewRoles', User::class);

		$user->loadMissing('roles');

		return $this->okWithData([
			'user' => new UserResource($user),
			'roles' => $user->roles,
			'available_roles' => Role::select('id', 'name')->get(),
		]);
	}

	// Assign a role to a user
	public function store(User $user, AssignRoleRequest $request)
	{
		$this->authorize('assignRoles', User::class);

		$this->userRoleService->assignRole($user, $request->validated()['role']);

		return $this->created(
			new UserResource($user->load('roles')),
			'Role assigned successfully'
		);
	}

	// Remove a role from a user
	public function destroy(User $user, string $role)
	{
		$this->authorize('removeRoles', User::class);

		$this->userRoleService->removeRole($user, $role);

		return $this->okWithData(
			new UserResource($user->load('roles')),
			'Role removed successfully'
		);
	}
}

