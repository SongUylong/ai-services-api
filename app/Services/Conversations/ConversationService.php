<?php

namespace App\Services\Conversations;

use App\Models\Conversations\Conversation;
use App\Repositories\ConversationRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ConversationService
{
    public function __construct(
        protected ConversationRepository $conversationRepository
    ) {}

    // List user conversations with pagination
    public function listUserConversations(int $userId): LengthAwarePaginator
    {
        return $this->conversationRepository->getConversations($userId);
    }

    // Create a new conversation
    public function createConversation(int $userId, array $data): Conversation
    {
        $conversationData = [
            'user_id' => $userId,
            'title' => $data['title'] ?? 'New Conversation',
        ];

        return $this->conversationRepository->create($conversationData);
    }

    // Get a conversation with its messages (legacy - uses ID)
    public function getConversation(int $id, ?int $perPage = null): ?Conversation
    {
        return $this->conversationRepository->getWithMessages($id, $perPage);
    }

    // Get a conversation with its messages (optimized - no duplicate query)
    public function getConversationWithMessages(Conversation $conversation, ?int $perPage = null): Conversation
    {
        return $this->conversationRepository->loadMessages($conversation, $perPage);
    }

    // Update a conversation
    public function updateConversation(Conversation $conversation, array $data): bool
    {
        return $this->conversationRepository->update($conversation, $data);
    }

    // Delete a conversation
    public function deleteConversation(Conversation $conversation): bool
    {
        return $this->conversationRepository->delete($conversation);
    }
}
