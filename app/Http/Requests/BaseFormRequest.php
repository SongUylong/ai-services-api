<?php

namespace App\Http\Requests;

use App\Exceptions\ErrorCode;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

abstract class BaseFormRequest extends FormRequest
{
    /**
     * Handle a failed validation attempt.
     * Uses the standard API error format for consistency.
     */
    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors();

        // Get all error messages
        $allMessages = $errors->all();

        // Create a clear, simple message
        $message = count($allMessages) === 1
            ? $allMessages[0]
            : 'The given data was invalid';

        // Use standard error format
        $response = [
            'success' => false,
            'error' => [
                'code' => ErrorCode::VALIDATION_ERROR,
                'message' => $message,
                'details' => [
                    'fields' => $errors->messages(),
                ],
            ],
        ];

        throw new HttpResponseException(
            response()->json($response, JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
