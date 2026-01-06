<?php

namespace App\Http\Requests\Users;

use App\Helpers\Validation\AllowedFieldsValidator;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Validator;

class ListUsersRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'per_page.integer' => 'The per page value must be an integer.',
            'per_page.min' => 'The per page value must be at least 1.',
            'per_page.max' => 'The per page value must not exceed 100.',
            'page.integer' => 'The page number must be an integer.',
            'page.min' => 'The page number must be at least 1.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Validate allowed fields
            $allowedFields = ['per_page', 'page'];
            AllowedFieldsValidator::validate($validator, $this->all(), $allowedFields);
        });
    }
}
