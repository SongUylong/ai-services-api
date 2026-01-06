<?php

namespace App\Http\Requests\Users;

use App\Helpers\Validation\AllowedFieldsValidator;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Validator;

class ChangePasswordRequest extends BaseFormRequest
{
	// Determine if the user is authorized to make this request
	public function authorize(): bool
	{
		return true;
	}

	// Get the validation rules that apply to the request
	public function rules(): array
	{
		$user = $this->route('user');
		$canChangeWithoutOldPassword = $user && auth('api')->id() !== $user->id;

		return [
			'current_password' => $canChangeWithoutOldPassword ? ['nullable', 'string'] : ['required', 'string'],
			'password' => ['required', 'string', 'min:8', 'confirmed'],
		];
	}

	public function withValidator(Validator $validator): void
	{
		$validator->after(function (Validator $validator) {
			$allowedFields = ['current_password', 'password', 'password_confirmation'];
			AllowedFieldsValidator::validate($validator, $this->all(), $allowedFields);
		});
	}
}

