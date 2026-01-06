<?php

namespace App\Policies\Messages;

use App\Models\Messages\Message;
use App\Models\Messages\MessageFeedback;
use App\Models\Users\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MessageFeedbackPolicy
{
    use HandlesAuthorization;

    // Determine whether the user can give feedback to a message
    public function create(User $user, Message $message): bool
    {
        // Users with permission can give feedback to any message
        if ($user->hasPermissionTo('create any feedback')) {
            return true;
        }

        // Can only give feedback to bot messages in own conversations
        if ($message->sender !== 'bot') {
            return false;
        }

        // Load conversation if not already loaded
        if (!$message->relationLoaded('conversation')) {
            $message->load('conversation');
        }

        return $user->id === $message->conversation->user_id && $user->hasPermissionTo('create own feedback');
    }

    // Determine whether the user can delete their feedback
    public function delete(User $user, MessageFeedback $feedback): bool
    {
        // Users with permission can delete any feedback
        if ($user->hasPermissionTo('delete any feedback')) {
            return true;
        }

        // Users can only delete their own feedback
        return $user->id === $feedback->user_id && $user->hasPermissionTo('delete own feedback');
    }
}
