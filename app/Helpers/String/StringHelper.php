<?php

namespace App\Helpers\String;

class StringHelper
{
    // Clean a string by removing special characters and keeping only alphanumeric
    public static function cleanAlphanumeric(string $string): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower(trim($string)));
    }

    // Normalize a string (trim, lowercase)
    public static function normalize(string $string): string
    {
        return strtolower(trim($string));
    }
}

