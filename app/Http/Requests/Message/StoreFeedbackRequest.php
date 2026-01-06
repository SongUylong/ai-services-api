<?php

namespace App\Http\Requests\Message;

use App\Helpers\Validation\AllowedFieldsValidator;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreFeedbackRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'feedback_type' => ['required', Rule::in(['like', 'dislike'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Validate allowed fields
            $allowedFields = ['feedback_type'];
            AllowedFieldsValidator::validate($validator, $this->all(), $allowedFields);
        });
    }
}
