<?php

namespace App\Services\Users;

use App\Helpers\Auth\PasswordHelper;
use App\Models\Users\User;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class UserService
{
    public function __construct(
        protected UserRepository $userRepository
    ) {
    }

    // Get all users with pagination
    public function getAllUsers(int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->getAllPaginated($perPage);
    }

    // Get all users
    public function getAllUsersList(): Collection
    {
        return $this->userRepository->getAll();
    }

    // Get user by ID
    public function getUserById(int $id): ?User
    {
        return $this->userRepository->findById($id);
    }

    // Create a new user
    public function createUser(array $data): User
    {
        // Generate unique username
        $username = $this->userRepository->generateUniqueUsername(
            $data['first_name'],
            $data['last_name'],
            $data['email']
        );

        // Hash password
        $data['password'] = PasswordHelper::hash($data['password']);
        $data['username'] = $username;
        $data['is_active'] = $data['is_active'] ?? true;
        $data['last_password_change'] = now();

        $user = $this->userRepository->create($data);

        // Assign default user role
        $user->assignRole('user');

        return $user->load('roles');
    }


    // Update user
    public function updateUser(User $user, array $data): bool
    {
        return $this->userRepository->update($user, $data);
    }

    // Delete user
    public function deleteUser(User $user): bool
    {
        return $this->userRepository->delete($user);
    }
}
