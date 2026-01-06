<?php

namespace App\Policies\Messages;

use App\Models\Messages\Message;
use App\Models\Users\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MessagePolicy
{
    use HandlesAuthorization;

    // Determine whether the user can create messages in a conversation
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create message');
    }

    // Determine whether the user can view the message
    public function view(User $user, Message $message): bool
    {
        // Users with permission can view any message
        if ($user->hasPermissionTo('view any message')) {
            return true;
        }

        // Users can only view messages in their own conversations
        return $user->id === $message->conversation->user_id && $user->hasPermissionTo('view own messages');
    }

    // Determine whether the user can regenerate the message (only for bot messages)
    public function regenerate(User $user, Message $message): bool
    {
        // Can only regenerate bot messages
        if ($message->sender !== 'bot') {
            return false;
        }

        // Users with permission can regenerate any message
        if ($user->hasPermissionTo('regenerate any message')) {
            return true;
        }

        // Users can only regenerate messages in their own conversations
        return $user->id === $message->conversation->user_id && $user->hasPermissionTo('regenerate own messages');
    }

    // Determine whether the user can delete the message
    public function delete(User $user, Message $message): bool
    {
        // Users with permission can delete any message
        if ($user->hasPermissionTo('delete any message')) {
            return true;
        }

        // Users can only delete messages in their own conversations
        return $user->id === $message->conversation->user_id && $user->hasPermissionTo('delete own messages');
    }
}
