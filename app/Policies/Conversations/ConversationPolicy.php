<?php

namespace App\Policies\Conversations;

use App\Models\Conversations\Conversation;
use App\Models\Users\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ConversationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        // Users with permission can view any conversation
        if ($user->hasPermissionTo('view any conversation')) {
            return true;
        }

        // Users can view their own conversations
        return $user->hasPermissionTo('view own conversations');
    }

    //Determine whether the user can view the conversation.
    public function view(User $user, Conversation $conversation): bool
    {
        // Users with permission can view any conversation
        if ($user->hasPermissionTo('view any conversation')) {
            return true;
        }

        // Users can only view their own conversations
        return $user->id === $conversation->user_id && $user->hasPermissionTo('view own conversations');
    }


    //Determine whether the user can create conversations.

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create conversation');
    }

    //Determine whether the user can update the conversation.
    public function update(User $user, Conversation $conversation): bool
    {
        // Users with permission can update any conversation
        if ($user->hasPermissionTo('update any conversation')) {
            return true;
        }

        // Users can only update their own conversations
        return $user->id === $conversation->user_id && $user->hasPermissionTo('update own conversations');
    }

    //Determine whether the user can delete the conversation.

    public function delete(User $user, Conversation $conversation): bool
    {
        // Users with permission can delete any conversation
        if ($user->hasPermissionTo('delete any conversation')) {
            return true;
        }

        // Users can only delete their own conversations
        return $user->id === $conversation->user_id && $user->hasPermissionTo('delete own conversations');
    }

    //Determine whether the user can restore the conversation.
    public function restore(User $user, Conversation $conversation): bool
    {
        // Users with permission can restore any conversation
        if ($user->hasPermissionTo('restore any conversation')) {
            return true;
        }

        // Users can only restore their own conversations
        return $user->id === $conversation->user_id && $user->hasPermissionTo('restore own conversations');
    }

    // Determine whether the user can permanently delete the conversation.

    public function forceDelete(User $user, Conversation $conversation): bool
    {
        // Only users with permission can permanently delete conversations
        return $user->hasPermissionTo('force delete conversation');
    }
}
