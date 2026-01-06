<?php

namespace App\Http\Resources\Messages;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $media = $this->getFirstMedia('attachments');

        return [
            'id' => $this->id,
            'message_id' => $this->message_id,
            'file_name' => $media?->file_name,
            'file_type' => $media?->mime_type,
            'file_size' => $media?->size,
            'file_url' => $media?->getUrl(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
