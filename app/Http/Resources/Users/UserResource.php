<?php

namespace App\Http\Resources\Users;

use App\Http\Resources\RolePermission\RoleResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    // Transform the resource into an array
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'image_url' => $this->profile_image_url, // Use media library accessor
            'is_active' => $this->is_active,
            'last_login' => $this->last_login?->toIso8601String(),
            'last_password_change' => $this->last_password_change?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            'settings' => new UserSettingResource($this->whenLoaded('setting')),
        ];
    }
}
