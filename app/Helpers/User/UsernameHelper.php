<?php

namespace App\Helpers\User;

use App\Helpers\String\StringHelper;

class UsernameHelper
{
    // Generate a unique username based on first name, last name, and email
    public static function generateUnique(
        string $firstName,
        string $lastName,
        string $email,
        callable $existsCallback,
        int $maxLength = 50
    ): string {
        // Get first letter of first name and convert to lowercase
        $firstInitial = strtolower(substr(trim($firstName), 0, 1));
        
        // Clean last name: lowercase, remove special characters and spaces, keep only alphanumeric
        $cleanLastName = StringHelper::cleanAlphanumeric($lastName);
        
        // Combine: first initial + last name
        $baseUsername = $firstInitial . $cleanLastName;
        
        // Limit to leave room for numbers if needed
        $baseLength = $maxLength - 10; // Reserve 10 chars for numbers
        $baseUsername = substr($baseUsername, 0, $baseLength);

        $username = $baseUsername;
        $counter = 1;

        // Check if username exists, if so append numbers
        while ($existsCallback($username)) {
            $suffix = (string) $counter;
            // Ensure total length doesn't exceed maxLength
            $maxBaseLength = $maxLength - strlen($suffix);
            $username = substr($baseUsername, 0, $maxBaseLength) . $suffix;
            $counter++;
        }

        return $username;
    }
}

