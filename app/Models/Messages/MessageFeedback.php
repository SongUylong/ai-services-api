<?php

namespace App\Models\Messages;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageFeedback extends Model
{
    protected $table = 'message_feedback'; // Explicit table name is good practice

    protected $fillable = [
        'message_id',
        'user_id',
        'feedback_type', // 'like' or 'dislike'
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
