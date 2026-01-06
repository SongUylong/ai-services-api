<?php

namespace App\Http\Resources\Messages;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Determine which feedback to show (original message feedback if this is a regeneration)
        $feedbackToShow = null;
        if ($this->original_message_id && $this->relationLoaded('originalMessage') && $this->originalMessage) {
            // This is a regenerated message, use original message's feedback
            $feedbackToShow = $this->originalMessage->feedback;
        } elseif ($this->relationLoaded('feedback')) {
            // This is an original message or no original loaded, use own feedback
            $feedbackToShow = $this->feedback;
        }

        // Build versions array for bot messages
        $versions = null;
        
        if ($this->sender === 'bot') {
            if ($this->original_message_id && $this->relationLoaded('originalMessage')) {
                // This is a regeneration - get all versions from original message
                if ($this->originalMessage->relationLoaded('regenerations')) {
                    $allVersions = collect([$this->originalMessage])->concat($this->originalMessage->regenerations);
                    $versions = $allVersions->sortBy('regeneration_index')->map(function ($version) {
                        return [
                            'version_index' => $version->regeneration_index,
                            'content' => $version->content,
                        ];
                    })->values()->toArray();
                }
            } elseif ($this->relationLoaded('regenerations')) {
                // This is an original message with regenerations loaded (could be empty or not)
                $allVersions = collect([$this])->concat($this->regenerations);
                $versions = $allVersions->sortBy('regeneration_index')->map(function ($version) {
                    return [
                        'version_index' => $version->regeneration_index,
                        'content' => $version->content,
                    ];
                })->values()->toArray();
            } else {
                // Original message without regenerations loaded - single version
                $versions = [
                    [
                        'version_index' => 0,
                        'content' => $this->content,
                    ]
                ];
            }
        }

        $data = [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender' => $this->sender,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            // Conditional relationships
            'attachments' => MessageAttachmentResource::collection($this->whenLoaded('attachments')),
            
            // Feedback is always from the original message (shared across regenerations)
            'feedback' => $this->when($feedbackToShow, fn() => new MessageFeedbackResource($feedbackToShow)),
            
            'ai_model' => $this->whenLoaded('aiModel', function () {
                return [
                    'id' => $this->aiModel->id,
                    'name' => $this->aiModel->name,
                ];
            }),
        ];

        // Add content field only for user messages
        if ($this->sender === 'user') {
            $data['content'] = $this->content;
        }

        // Add bot-specific fields only for bot messages
        if ($this->sender === 'bot') {
            // Add parent_id to show which user message the bot is replying to
            $data['parent_id'] = $this->parent_id;
            
            if ($versions !== null) {
                $data['versions'] = $versions;
            }
        }

        return $data;
    }
}
