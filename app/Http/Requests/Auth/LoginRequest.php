<?php

namespace App\Http\Requests\Auth;

use App\Helpers\Validation\AllowedFieldsValidator;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Validator;

class LoginRequest extends BaseFormRequest
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
            'username_or_email' => ['required', 'string'], // Accept both email and username
            'password' => ['required', 'string'],
			'remember_me' => ['sometimes', 'boolean'],
        ];
    }
    
    // Get custom messages for validator errors
    public function messages(): array
    {
        return [
            'username_or_email.required' => 'Email or username is required.',
            'password.required' => 'Password is required.',
        ];
    }

	public function withValidator(Validator $validator): void
	{
		$validator->after(function (Validator $validator) {
			$allowedFields = ['username_or_email', 'password', 'remember_me'];
			AllowedFieldsValidator::validate($validator, $this->all(), $allowedFields);
		});
	}
}

