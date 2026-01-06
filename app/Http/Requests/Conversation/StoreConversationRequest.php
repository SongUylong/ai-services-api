<?php

namespace App\Http\Requests\Conversation;

use App\Helpers\Validation\AllowedFieldsValidator;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Validator;

class StoreConversationRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Validate allowed fields
            $allowedFields = ['title'];
            AllowedFieldsValidator::validate($validator, $this->all(), $allowedFields);
        });
    }
}
