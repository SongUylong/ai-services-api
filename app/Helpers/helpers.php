<?php

if (!function_exists('api_response')) {
    // Return a standardized API response
    function api_response(mixed $data = null, ?string $message = null, int $statusCode = 200, array $headers = []): \Illuminate\Http\JsonResponse
    {
        $response = [];

        if ($message !== null) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode, $headers);
    }
}

if (!function_exists('api_success')) {
    // Return a success API response
    function api_success(mixed $data = null, ?string $message = null, int $statusCode = 200): \Illuminate\Http\JsonResponse
    {
        return api_response($data, $message, $statusCode);
    }
}

if (!function_exists('api_error')) {
    // Return an error API response
    function api_error(string $message, int $statusCode = 400, mixed $errors = null): \Illuminate\Http\JsonResponse
    {
        $response = ['message' => $message];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }
}

if (!function_exists('api_created')) {
    // Return a created API response (201)
    function api_created(mixed $data = null, ?string $message = null): \Illuminate\Http\JsonResponse
    {
        return api_response($data, $message, 201);
    }
}

if (!function_exists('api_no_content')) {
    // Return a no content API response (204)
    function api_no_content(): \Illuminate\Http\JsonResponse
    {
        return response()->json(null, 204);
    }
}

if (!function_exists('api_not_found')) {
    // Return a not found API response (404)
    function api_not_found(string $message = 'Resource not found'): \Illuminate\Http\JsonResponse
    {
        return api_error($message, 404);
    }
}

if (!function_exists('api_unauthorized')) {
    // Return an unauthorized API response (401)
    function api_unauthorized(string $message = 'Unauthorized'): \Illuminate\Http\JsonResponse
    {
        return api_error($message, 401);
    }
}

if (!function_exists('api_forbidden')) {
    // Return a forbidden API response (403)
    function api_forbidden(string $message = 'Forbidden'): \Illuminate\Http\JsonResponse
    {
        return api_error($message, 403);
    }
}

if (!function_exists('api_validation_error')) {
    // Return a validation error API response (422)
    function api_validation_error(mixed $errors, string $message = 'Validation failed'): \Illuminate\Http\JsonResponse
    {
        return api_error($message, 422, $errors);
    }
}

