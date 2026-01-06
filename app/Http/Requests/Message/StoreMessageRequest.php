<?php

namespace App\Http\Requests\Message;

use App\Helpers\Validation\AllowedFieldsValidator;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Validator;

class StoreMessageRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string'],
            'ai_model_id' => ['nullable', 'integer', 'exists:ai_models,id'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:' . config('support.maximum_attachment_size', 10240)],
        ];
    }

    public function messages(): array
    {
        return [
            'attachments.max' => 'You can upload a maximum of 5 files at once',
            'attachments.*.file' => 'Each attachment must be a valid file',
            'attachments.*.max' => 'Each file must not exceed ' . (config('support.maximum_attachment_size', 10240) / 1024) . 'MB',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Validate allowed fields
            $allowedFields = ['content', 'ai_model_id', 'attachments'];
            AllowedFieldsValidator::validate($validator, $this->all(), $allowedFields);
        });
    }
}
