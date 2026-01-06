<?php

namespace App\Repositories;

use App\Helpers\User\UsernameHelper;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository
{
    // Find a user by ID
    public function findById(int $id): ?User
    {
        return User::with('roles')->find($id);
    }

    // Find a user by email
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    // Find a user by username
    public function findByUsername(string $username): ?User
    {
        return User::where('username', $username)->first();
    }

    // Check if username exists
    public function usernameExists(string $username): bool
    {
        return User::where('username', $username)->exists();
    }

    // Get all users with pagination
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return User::with('roles')->paginate($perPage);
    }

    // Get all users
    public function getAll(): Collection
    {
        return User::with('roles')->get();
    }

    // Create a new user
    public function create(array $data): User
    {
        return User::create($data);
    }

    // Update a user
    public function update(User $user, array $data): bool
    {
        return $user->update($data);
    }

    // Delete a user
    public function delete(User $user): bool
    {
        return $user->delete();
    }

    // Generate a unique username based on first name, last name, and email
    public function generateUniqueUsername(string $firstName, string $lastName, string $email): string
    {
        return UsernameHelper::generateUnique(
            $firstName,
            $lastName,
            $email,
            fn(string $username) => $this->usernameExists($username)
        );
    }
}
