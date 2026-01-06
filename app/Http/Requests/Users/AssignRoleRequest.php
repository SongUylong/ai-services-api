<?php

namespace App\Http\Requests\Users;

use App\Helpers\Validation\AllowedFieldsValidator;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Validator;

class AssignRoleRequest extends BaseFormRequest
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
            'role' => ['required', 'string', 'exists:roles,name'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $allowedFields = ['role'];
            AllowedFieldsValidator::validate($validator, $this->all(), $allowedFields);
        });
    }
}
