<?php

namespace App\Repositories;

use App\Models\Conversations\Conversation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class ConversationRepository
{
    // Get paginated conversations for a user
    public function getConversations(int $userId): LengthAwarePaginator
    {
        $query = QueryBuilder::for(Conversation::class)
            ->where('user_id', $userId)
            ->allowedFilters([
                AllowedFilter::partial('title'), // Partial match (LIKE %value%)
            ])
            ->allowedSorts(['created_at', 'updated_at'])
            ->defaultSort('-updated_at');
        return $query->paginate(request()->query('per_page', 15));
    }

    // Find conversation by ID
    public function findById(int $id): ?Conversation
    {
        return Conversation::find($id);
    }

    // Create a new conversation
    public function create(array $data): Conversation
    {
        return Conversation::create($data);
    }

    // Update a conversation
    public function update(Conversation $conversation, array $data): bool
    {
        return $conversation->update($data);
    }

    // Soft delete a conversation
    public function delete(Conversation $conversation): bool
    {
        return $conversation->delete();
    }

    // Get conversation with paginated messages
    public function getWithMessages(int $id, ?int $perPage = null): ?Conversation
    {
        $conversation = Conversation::find($id);
        
        if (!$conversation) {
            return null;
        }

        // Set default per_page if not provided
        $perPage = $perPage ?? 20;

        // Get paginated messages (most recent first) with relationships
        // Exclude old regenerations - only show the latest version of each bot response
        $messages = $conversation->messages()
            ->where(function ($query) use ($conversation) {
                // Include all user messages
                $query->where('sender', 'user')
                    // Include original bot messages that have never been regenerated
                    ->orWhere(function ($q) {
                        $q->where('sender', 'bot')
                          ->where('original_message_id', null)
                          ->whereDoesntHave('regenerations');
                    })
                    // Include only the latest regeneration of each bot response (highest ID per original_message_id)
                    ->orWhereIn('id', function ($subQuery) use ($conversation) {
                        $subQuery->selectRaw('MAX(id)')
                            ->from('messages')
                            ->where('conversation_id', $conversation->id)
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
                'originalMessage.regenerations' => function ($query) {
                    $query->select('id', 'original_message_id', 'regeneration_index', 'content')
                          ->orderBy('regeneration_index', 'asc');
                }, // Load all versions from original message for regenerated messages
                'regenerations' => function ($query) {
                    $query->select('id', 'original_message_id', 'regeneration_index', 'content')
                          ->orderBy('regeneration_index', 'asc');
                }, // Load all version regenerations for original messages
            ])
            ->orderBy('created_at', 'asc')
            ->orderByRaw("CASE WHEN sender = 'user' THEN 0 ELSE 1 END") // User messages first, then bot
            ->paginate($perPage);

        // Set the paginated messages to the conversation
        $conversation->setRelation('messages', $messages);

        return $conversation;
    }

    // Load messages for an already-fetched conversation (optimized - no duplicate query)
    public function loadMessages(Conversation $conversation, ?int $perPage = null): Conversation
    {
        // Set default per_page if not provided
        $perPage = $perPage ?? 20;

        // Get paginated messages (most recent first) with relationships
        // Exclude old regenerations - only show the latest version of each bot response
        $messages = $conversation->messages()
            ->where(function ($query) use ($conversation) {
                // Include all user messages
                $query->where('sender', 'user')
                    // Include original bot messages that have never been regenerated
                    ->orWhere(function ($q) {
                        $q->where('sender', 'bot')
                          ->where('original_message_id', null)
                          ->whereDoesntHave('regenerations');
                    })
                    // Include only the latest regeneration of each bot response (highest ID per original_message_id)
                    ->orWhereIn('id', function ($subQuery) use ($conversation) {
                        $subQuery->selectRaw('MAX(id)')
                            ->from('messages')
                            ->where('conversation_id', $conversation->id)
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
                'originalMessage.regenerations' => function ($query) {
                    $query->select('id', 'original_message_id', 'regeneration_index', 'content')
                          ->orderBy('regeneration_index', 'asc');
                }, // Load all versions from original message for regenerated messages
                'regenerations' => function ($query) {
                    $query->select('id', 'original_message_id', 'regeneration_index', 'content')
                          ->orderBy('regeneration_index', 'asc');
                }, // Load all version regenerations for original messages
            ])
            ->orderBy('created_at', 'asc')
            ->orderByRaw("CASE WHEN sender = 'user' THEN 0 ELSE 1 END") // User messages first, then bot
            ->paginate($perPage);

        // Set the paginated messages to the conversation
        $conversation->setRelation('messages', $messages);

        return $conversation;
    }
}
