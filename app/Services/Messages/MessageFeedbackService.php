<?php

namespace App\Services\Messages;

use App\Models\Messages\Message;
use App\Models\Messages\MessageFeedback;
use App\Models\Users\User;

class MessageFeedbackService
{
    // Give feedback to a message (applies to original message if this is a regeneration)
    public function giveFeedback(Message $message, User $user, string $feedbackType): MessageFeedback
    {
        // If this is a regenerated message, apply feedback to the original message
        $targetMessageId = $message->original_message_id ?? $message->id;
        
        // Check if user already gave feedback to this message (or its original)
        $existingFeedback = MessageFeedback::where('message_id', $targetMessageId)
            ->where('user_id', $user->id)
            ->first();

        if ($existingFeedback) {
            // Update existing feedback
            $existingFeedback->update(['feedback_type' => $feedbackType]);
            return $existingFeedback->fresh();
        }

        // Create new feedback on the original message
        return MessageFeedback::create([
            'message_id' => $targetMessageId,
            'user_id' => $user->id,
            'feedback_type' => $feedbackType,
        ]);
    }

    // Remove feedback from a message
    public function removeFeedback(MessageFeedback $feedback): bool
    {
        return $feedback->delete();
    }

    // Get feedback by ID
    public function getFeedbackById(int $id): ?MessageFeedback
    {
        return MessageFeedback::find($id);
    }
}
