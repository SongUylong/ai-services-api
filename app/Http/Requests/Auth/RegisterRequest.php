<?php

namespace App\Http\Requests\Auth;

use App\Helpers\Validation\AllowedFieldsValidator;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Validator;

class RegisterRequest extends BaseFormRequest
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
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            // Validate phone number with automatic country detection from + prefix
            // AUTO allows any valid international format starting with +
            'phone_number' => ['nullable', 'phone:AUTO,mobile', 'unique:users,phone_number'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'phone_number.phone' => 'The phone number must be a valid international mobile number with country code (e.g., +1234567890).',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Validate allowed fields
            $allowedFields = ['first_name', 'last_name', 'email', 'phone_number', 'password', 'password_confirmation'];
            AllowedFieldsValidator::validate($validator, $this->all(), $allowedFields);
        });
    }
}
