<?php

namespace App\Http\Requests\Users;

use App\Helpers\Validation\AllowedFieldsValidator;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Validator;

class UpdateUserSettingRequest extends BaseFormRequest
{
    // Determine if the user is authorized to make this request
    public function authorize(): bool
    {
        return true;
    }

    // Get the validation rules that apply to the request
    public function rules(): array
    {
        return [
            'theme' => ['sometimes', 'required_without_all:language,preferred_ai_model_id', 'string', 'in:light,dark'],
            'language' => ['sometimes', 'required_without_all:theme,preferred_ai_model_id', 'string', 'in:en,kh'],
            'preferred_ai_model_id' => ['sometimes', 'required_without_all:theme,language', 'nullable', 'integer', 'exists:ai_models,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $allowedFields = ['theme', 'language', 'preferred_ai_model_id'];
            AllowedFieldsValidator::validate($validator, $this->all(), $allowedFields, requireAtLeastOne: true);
        });
    }
}
