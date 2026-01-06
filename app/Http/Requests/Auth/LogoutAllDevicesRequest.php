<?php

namespace App\Http\Requests\Auth;

use App\Helpers\Validation\AllowedFieldsValidator;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Validator;

class LogoutAllDevicesRequest extends BaseFormRequest
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
            // Validate against the current password on the API guard
            'password' => ['required', 'current_password:api'],
		];
	}

	public function withValidator(Validator $validator): void
	{
		$validator->after(function (Validator $validator) {
			$allowedFields = ['password'];
			AllowedFieldsValidator::validate($validator, $this->all(), $allowedFields);
		});
	}
}

