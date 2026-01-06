<?php

namespace App\Http\Requests\Users;

use App\Helpers\String\StringHelper;
use App\Helpers\Validation\AllowedFieldsValidator;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ChangeUsernameRequest extends BaseFormRequest
{
    // Determine if the user is authorized to make this request
    public function authorize(): bool
    {
        return true;
    }

    // Get the validation rules that apply to the request
    public function rules(): array
    {
        $user = $this->route('user') ?? auth('api')->user();
        $userId = $user?->id;

        return [
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('users', 'username')->ignore($userId),
            ],
        ];
    }

    // Get custom messages for validator errors
    public function messages(): array
    {
        return [
            'username.required' => 'Username is required.',
            'username.min' => 'Username must be at least 3 characters.',
            'username.max' => 'Username must not exceed 50 characters.',
            'username.regex' => 'Username can only contain lowercase letters, numbers, and underscores.',
            'username.unique' => 'This username is already taken.',
        ];
    }

    // Prepare the data for validation
    protected function prepareForValidation(): void
    {
        if ($this->has('username')) {
            $this->merge([
                'username' => StringHelper::normalize($this->username),
            ]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $allowedFields = ['username'];
            AllowedFieldsValidator::validate($validator, $this->all(), $allowedFields);
        });
    }
}
