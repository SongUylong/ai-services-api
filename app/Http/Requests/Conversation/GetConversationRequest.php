<?php

namespace App\Http\Requests\Conversation;

use App\Helpers\Validation\AllowedFieldsValidator;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Validator;

class GetConversationRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Validate allowed fields
            $allowedFields = ['per_page'];
            AllowedFieldsValidator::validate($validator, $this->all(), $allowedFields);
        });
    }
}
