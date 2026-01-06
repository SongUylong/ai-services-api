<?php

namespace App\Repositories;

use App\Models\Messages\Message;
use Illuminate\Database\Eloquent\Collection;

class MessageRepository
{
    // Get all messages for a conversation
    public function getConversationMessages(int $conversationId): Collection
    {
        // Exclude old regenerations - only show the latest version of each bot response
        return Message::where('conversation_id', $conversationId)
            ->where(function ($query) use ($conversationId) {
                // Include all user messages
                $query->where('sender', 'user')
                    // Include original bot messages that have never been regenerated
                    ->orWhere(function ($q) {
                        $q->where('sender', 'bot')
                          ->where('original_message_id', null)
                          ->whereDoesntHave('regenerations');
                    })
                    // Include only the latest regeneration of each bot response (highest ID per original_message_id)
                    ->orWhereIn('id', function ($subQuery) use ($conversationId) {
                        $subQuery->selectRaw('MAX(id)')
                            ->from('messages')
                            ->where('conversation_id', $conversationId)
                            ->where('sender', 'bot')
                            ->whereNotNull('original_message_id')
                            ->groupBy('original_message_id');
                    });
            })
            ->with([
                'attachments.media',
                'feedback',
                'aiModel',
                'originalMessage.feedback', // Load feedback from original message for regenerations
                'regenerations' => function ($query) {
                    $query->orderBy('regeneration_index', 'asc');
                }, // Load all version regenerations for efficient version tracking
                'originalMessage.regenerations' => function ($query) {
                    $query->orderBy('regeneration_index', 'asc');
                }, // Load regenerations from original message for regenerated messages
            ])
            ->orderBy('created_at', 'asc')
            ->orderByRaw("CASE WHEN sender = 'user' THEN 0 ELSE 1 END") // User messages first, then bot
            ->get();
    }

    // Find message by ID
    public function findById(int $id): ?Message
    {
        return Message::find($id);
    }

    // Create a new message
    public function create(array $data): Message
    {
        return Message::create($data);
    }

    // Update a message
    public function update(Message $message, array $data): bool
    {
        return $message->update($data);
    }

    // Delete a message
    public function delete(Message $message): bool
    {
        return $message->delete();
    }

    // Find the last bot message in a conversation
    public function getLastBotMessage(int $conversationId): ?Message
    {
        return Message::where('conversation_id', $conversationId)
            ->where('sender', 'bot')
            ->latest()
            ->first();
    }
}
