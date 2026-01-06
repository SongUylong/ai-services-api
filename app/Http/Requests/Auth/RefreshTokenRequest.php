<?php

namespace App\Http\Requests\Auth;

use App\Helpers\Validation\AllowedFieldsValidator;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Validator;

class RefreshTokenRequest extends BaseFormRequest
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
			'refresh_token' => ['required', 'string'],
			'remember_me' => ['required', 'boolean'],
		];
	}

	public function withValidator(Validator $validator): void
	{
		$validator->after(function (Validator $validator) {
			$allowedFields = ['refresh_token', 'remember_me'];
			AllowedFieldsValidator::validate($validator, $this->all(), $allowedFields);
		});
	}
}

