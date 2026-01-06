<?php

namespace App\Http\Resources\Users;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSettingResource extends JsonResource
{
    // Transform the resource into an array
    public function toArray(Request $request): array
    {
        return [
            'theme' => $this->theme ?? 'light',
            'language' => $this->language ?? 'en',
            'preferred_ai_model_id' => $this->preferred_ai_model_id,
        ];
    }
}
