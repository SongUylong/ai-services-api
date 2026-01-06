<?php

namespace App\Helpers\Validation;

use Illuminate\Validation\Validator;

class AllowedFieldsValidator
{
    // Validate that only allowed fields are present in the request
    public static function validate(
        Validator $validator,
        array $requestData,
        array $allowedFields,
        bool $requireAtLeastOne = false
    ): void {
        $payloadKeys = array_keys($requestData);
        $extraKeys = array_diff($payloadKeys, $allowedFields);

        // Check for extra fields
        if (!empty($extraKeys)) {
            $validator->errors()->add(
                'invalid_fields',
                'The following fields are not allowed: ' . implode(', ', $extraKeys)
            );
        }

        // Check if at least one field is provided (for update requests)
        if ($requireAtLeastOne) {
            $provided = array_intersect($payloadKeys, $allowedFields);

            if (empty($provided)) {
                $validator->errors()->add(
                    'payload',
                    'You must provide at least one of: ' . implode(', ', $allowedFields) . '.'
                );
            }
        }
    }
}
