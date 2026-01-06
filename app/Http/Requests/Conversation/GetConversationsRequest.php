<?php

namespace App\Http\Requests\Conversation;

use App\Helpers\Validation\AllowedFieldsValidator;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Validator;

class GetConversationsRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'filter' => ['nullable', 'array'],
            'filter.title' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Validate allowed fields
            $allowedFields = ['per_page', 'sort', 'filter'];
            AllowedFieldsValidator::validate($validator, $this->all(), $allowedFields);
        });
    }
}
