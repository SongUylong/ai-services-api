<?php

namespace App\Models\Messages;

use App\Models\AiModels\AiModel;
use App\Models\Conversations\Conversation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'parent_id', // The message this is replying to
        'sender', // 'user' or 'bot'
        'content',
        'ai_model_id',
        'original_message_id', // For tracking regenerated messages
        'regeneration_index', // 0 for original, 1,2,3... for regenerations
        'status', // 'pending', 'completed', 'failed'
    ];

    protected $touches = ['conversation'];
    // Helper to check sender type
    public function isUser(): bool
    {
        return $this->sender === 'user';
    }

    // Relationships
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function aiModel(): BelongsTo
    {
        return $this->belongsTo(AiModel::class);
    }

    // Relationship for original message (for regenerations)
    public function originalMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'original_message_id');
    }

    // Get all regenerated versions of this message
    public function regenerations(): HasMany
    {
        return $this->hasMany(Message::class, 'original_message_id')->orderBy('regeneration_index');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class);
    }

    public function feedback(): HasOne
    {
        return $this->hasOne(MessageFeedback::class);
    }

    // Parent message relationship (the message this is replying to)
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'parent_id');
    }

    // Child messages (replies to this message)
    public function replies(): HasMany
    {
        return $this->hasMany(Message::class, 'parent_id');
    }
}
