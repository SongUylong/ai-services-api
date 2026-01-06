<?php

namespace App\Helpers\Http;

class RequestHelper
{
    // Get pagination parameters from request
    public static function getPaginationParams(?int $defaultPerPage = 15, ?int $maxPerPage = 100): array
    {
        $perPage = request()->get('per_page', $defaultPerPage);
        $page = request()->get('page', 1);
        
        // Validate and sanitize per_page
        if (!is_numeric($perPage) || $perPage < 1) {
            $perPage = $defaultPerPage;
        }
        
        // Ensure per_page doesn't exceed max
        $perPage = min((int) $perPage, $maxPerPage);
        
        // Validate and sanitize page
        if (!is_numeric($page) || $page < 1) {
            $page = 1;
        }
        
        return [
            'per_page' => $perPage,
            'page' => (int) $page,
        ];
    }

    // Get include relationships from request
    public static function getIncludes(): array
    {
        if (!request()->has('include')) {
            return [];
        }
        
        $items = explode(',', request()->get('include'));
        $items = array_map('trim', $items);
        
        return array_filter($items, fn($item) => !empty($item));
    }
}

