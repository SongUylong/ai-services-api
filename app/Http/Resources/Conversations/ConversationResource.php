<?php

namespace App\Http\Resources\Conversations;

use App\Http\Resources\Messages\MessageResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];

        // Handle paginated messages
        if ($this->relationLoaded('messages')) {
            $messages = $this->messages;
            
            // Check if messages are paginated
            if ($messages instanceof \Illuminate\Pagination\LengthAwarePaginator) {
                $data['messages'] = MessageResource::collection($messages->items());
                $data['total_messages'] = $messages->total();
            } else {
                // Non-paginated messages (backward compatibility)
                $data['messages'] = MessageResource::collection($messages);
            }
        }

        return $data;
    }
}
