<?php

namespace App\Helpers\Auth;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PasswordHelper
{
    // Hash a password
    public static function hash(string $password): string
    {
        return Hash::make($password);
    }

    // Verify a password against a hash
    public static function verify(string $password, string $hashedPassword): bool
    {
        return Hash::check($password, $hashedPassword);
    }

    // Validate that a password matches the hashed password
    public static function validatePassword(string $password, string $hashedPassword, string $field = 'password'): void
    {
        if (!self::verify($password, $hashedPassword)) {
            throw ValidationException::withMessages([
                $field => ['The provided password is incorrect.'],
            ]);
        }
    }

    // Check if a new password is different from the current password
    public static function ensurePasswordChanged(
        string $newPassword, 
        string $currentHashedPassword, 
        ?string $currentPlainPassword = null,
        int $minDifferentChars = 5,
        string $field = 'new_password'
    ): void {
        if (self::verify($newPassword, $currentHashedPassword)) {
            throw ValidationException::withMessages([
                $field => ['The new password must be different from the current password.'],
            ]);
        }

        // If current plain password is provided, check character difference using Levenshtein distance
        if ($currentPlainPassword !== null) {
            // Levenshtein distance calculates minimum number of edits (insertions, deletions, substitutions)
            $distance = levenshtein($newPassword, $currentPlainPassword);
            
            if ($distance < $minDifferentChars) {
                throw ValidationException::withMessages([
                    $field => ["The new password must differ by at least {$minDifferentChars} characters from your current password."],
                ]);
            }
        }
    }

    // Validate password strength
    public static function isStrong(string $password, int $minLength = 8, bool $requireUppercase = true, bool $requireLowercase = true, bool $requireNumbers = true, bool $requireSpecialChars = false): bool
    {
        if (strlen($password) < $minLength) {
            return false;
        }

        // Check for at least one uppercase letter
        if ($requireUppercase && !preg_match('/[A-Z]/', $password)) {
            return false;
        }

        // Check for at least one lowercase letter
        if ($requireLowercase && !preg_match('/[a-z]/', $password)) {
            return false;
        }

        // Check for at least one number
        if ($requireNumbers && !preg_match('/[0-9]/', $password)) {
            return false;
        }

        // Check for at least one special character
        if ($requireSpecialChars && !preg_match('/[^a-zA-Z0-9]/', $password)) {
            return false;
        }

        return true;
    }

    // Validate password strength with exception
    public static function validateStrength(string $password, int $minLength = 8, string $field = 'password'): void
    {
        $errors = [];

        if (strlen($password) < $minLength) {
            $errors[] = "The password must be at least {$minLength} characters.";
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'The password must contain at least one uppercase letter.';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'The password must contain at least one lowercase letter.';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'The password must contain at least one number.';
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages([
                $field => $errors,
            ]);
        }
    }
}

