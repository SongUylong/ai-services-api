<?php

namespace App\Http\Requests\Users;

use App\Helpers\Validation\AllowedFieldsValidator;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Validator;

class UploadProfileImageRequest extends BaseFormRequest
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
            'image' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg,gif,webp',
                'max:' . config('support.maximum_profile_image_size'),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $allowedFields = ['image'];
            AllowedFieldsValidator::validate($validator, $this->all(), $allowedFields);
        });
    }
}
