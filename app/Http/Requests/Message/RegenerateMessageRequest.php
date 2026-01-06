<?php

namespace App\Http\Requests\Message;

use App\Http\Requests\BaseFormRequest;

class RegenerateMessageRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // No additional validation needed for regeneration
        return [];
    }
}
