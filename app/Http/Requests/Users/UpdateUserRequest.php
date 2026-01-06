<?php

namespace App\Http\Requests\Users;

use App\Helpers\Validation\AllowedFieldsValidator;
use App\Http\Requests\BaseFormRequest;
use App\Models\Users\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function getUserId(): ?int
    {
        // For admin routes: /admin/users/{user}
        $routeUser = $this->route('user');
        if ($routeUser instanceof User) {
            return $routeUser->getKey();
        }

        // Ensure we are resolving the api guard for self-update routes
        Auth::shouldUse('api');

        // Fall back through api guard then default guard
        return Auth::guard('api')->id()
            ?? $this->user()?->getKey()
            ?? Auth::id();
    }

    public function rules(): array
    {
        $userId = $this->getUserId();

        $rules = [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];

        // Validate email uniqueness (ignore current user)
        if ($this->filled('email')) {
            $rules['email'] = [
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ];
        }

        // Validate phone_number uniqueness (ignore current user)
        if ($this->has('phone_number')) {
            $rules['phone_number'] = [
                'nullable',
                'phone:AUTO,mobile',
                Rule::unique('users', 'phone_number')->ignore($userId),
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'phone_number.phone' => 'The phone number must be a valid international mobile number with country code (e.g., +1234567890).',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Validate allowed fields (require at least one for update requests)
            $allowedFields = ['first_name', 'last_name', 'is_active', 'email', 'phone_number'];
            AllowedFieldsValidator::validate($validator, $this->all(), $allowedFields, requireAtLeastOne: true);
        });
    }
}
