<?php

namespace App\Http\Controllers\Api\v1\Users;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Users\ListUsersRequest;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Requests\Users\UploadProfileImageRequest;
use App\Http\Resources\Users\UserResource;
use App\Models\Users\User;
use App\Services\Users\UserService;

// User Management - APIs for managing users
class UserController extends ApiController
{
    public function __construct(
        protected UserService $userService
    ) {}

    // List of users
    public function index(ListUsersRequest $request)
    {
        $this->authorize('viewAny', User::class);

        $validated = $request->validated();
        $perPage = $validated['per_page'] ?? 15;

        $users = $this->userService->getAllUsers($perPage);


        return $this->okWithData(UserResource::collection($users));
    }

    // Store a newly created user
    public function store(StoreUserRequest $request)
    {
        $this->authorize('create', User::class);

        $user = $this->userService->createUser($request->validated());

        return $this->created(
            new UserResource($user->load('roles')),
            'User created successfully'
        );
    }

    // Show a user
    public function show(User $user)
    {
        $this->authorize('view', $user);

        $user = $this->userService->getUserById($user->id);

        $includes = RequestHelper::getIncludes();
        if (!empty($includes)) {
            $user->loadMissing($includes);
        }

        return $this->okWithData(new UserResource($user));
    }

    // Update a user
    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorize('update', $user);

        $this->userService->updateUser($user, $request->validated());

        return $this->updated(
            new UserResource($user->fresh()->load('roles')),
            'User updated successfully'
        );
    }

    // Upload or update a user's profile image
    public function uploadProfileImage(UploadProfileImageRequest $request, User $user)
    {
        $this->authorize('uploadImage', $user);

        $image = $request->validated()['image'];

        // Delete old profile image if exists
        $user->clearMediaCollection('profile_image');

        // Add new image to media library
        $user->addMedia($image)
            ->toMediaCollection('profile_image');

        return $this->updated(
            new UserResource($user->fresh()->load('roles')),
            'Profile image uploaded successfully'
        );
    }

    // Delete a user
    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        $this->userService->deleteUser($user);

        return $this->deleted('User deleted successfully');
    }

    // Update own profile (user updating themselves)
    public function updateOwn(UpdateUserRequest $request)
    {
        $user = auth()->user();
        $this->authorize('updateOwn', $user);

        $this->userService->updateUser($user, $request->validated());

        return $this->updated(
            new UserResource($user->fresh()->load('roles')),
            'Profile updated successfully'
        );
    }

    // Upload own profile image (user uploading their own image)
    public function uploadOwnProfileImage(UploadProfileImageRequest $request)
    {
        $user = auth()->user();
        $this->authorize('uploadImage', $user);

        $image = $request->validated()['image'];

        // Delete old profile image if exists
        $user->clearMediaCollection('profile_image');

        // Add new image to media library
        $user->addMedia($image)
            ->toMediaCollection('profile_image');

        return $this->updated(
            new UserResource($user->fresh()->load('roles')),
            'Profile image uploaded successfully'
        );
    }

}
