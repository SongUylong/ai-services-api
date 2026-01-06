<?php

namespace App\Services\Messages;

use App\Models\Conversations\Conversation;
use App\Models\Messages\Message;
use App\Models\Users\User;
use App\Repositories\MessageRepository;
use Illuminate\Database\Eloquent\Collection;

class MessageService
{
    public function __construct(
        protected MessageRepository $messageRepository
    ) {}

    // Get all messages for a conversation
    public function getConversationMessages(int $conversationId): Collection
    {
        return $this->messageRepository->getConversationMessages($conversationId);
    }

    // Create a new user message (with optional attachments)
    public function createUserMessage(Conversation $conversation, User $user, array $data): Message
    {
        // Get AI model from user preference or from request
        $aiModelId = $data['ai_model_id'] ?? $user->setting?->preferred_ai_model_id;
        
        // If no AI model specified and no preference set, throw error
        if (!$aiModelId) {
            throw new \InvalidArgumentException('No AI model specified. Please set a preferred AI model in your settings or specify one in the request.');
        }

        $messageData = [
            'conversation_id' => $conversation->id,
            'sender' => 'user',
            'content' => $data['content'],
            'ai_model_id' => $aiModelId,
            'status' => 'completed',
        ];

        $message = $this->messageRepository->create($messageData);

        // Handle attachments if provided
        if (!empty($data['attachments'])) {
            foreach ($data['attachments'] as $file) {
                // Create attachment record
                $attachment = $message->attachments()->create([]);
                
                // Add file to media library
                $attachment->addMedia($file)->toMediaCollection('attachments');
            }
        }

        // TODO: Trigger AI response generation here
        // When implemented, pass attachments to AI service if present
        // $this->generateBotResponse($conversation, $message);

        return $message->load('attachments.media');
    }

    // Generate a bot response (placeholder - integrate with AI service)
    public function generateBotResponse(Conversation $conversation, Message $userMessage): Message
    {
        // This would integrate with your AI service
        // For now, creating a placeholder bot message
        $botMessageData = [
            'conversation_id' => $conversation->id,
            'parent_id' => $userMessage->id, // Link bot response to user message
            'sender' => 'bot',
            'content' => 'This is a placeholder bot response. Integrate with AI service.',
            'ai_model_id' => $userMessage->ai_model_id, // Use same AI model as user message
            'status' => 'completed',
        ];

        return $this->messageRepository->create($botMessageData);
    }

    // Regenerate a bot message
    public function regenerateMessage(Message $message): Message
    {
        // Determine the original message ID and next regeneration index
        $originalMessageId = $message->original_message_id ?? $message->id;
        
        // Get the parent_id from the original message (if this is a regeneration) or from the current message
        $parentId = $message->original_message_id ? 
            Message::find($originalMessageId)->parent_id : 
            $message->parent_id;
        
        // Get the highest regeneration index for this original message
        $maxIndex = Message::where('original_message_id', $originalMessageId)
            ->orWhere('id', $originalMessageId)
            ->max('regeneration_index');
        
        $nextIndex = ($maxIndex ?? 0) + 1;

        // Generate a new bot response (keep the old one, don't delete)
        $botMessageData = [
            'conversation_id' => $message->conversation_id,
            'parent_id' => $parentId, // Keep the same parent as the original bot response
            'sender' => 'bot',
            'content' => 'This is regenerated response #' . $nextIndex . '. Integrate with AI service.',
            'ai_model_id' => $message->ai_model_id,
            'original_message_id' => $originalMessageId,
            'regeneration_index' => $nextIndex,
            'status' => 'completed',
        ];

        $newMessage = $this->messageRepository->create($botMessageData);

        // Load all versions for the response
        return $newMessage->load([
            'originalMessage.regenerations' => function ($query) {
                $query->select('id', 'original_message_id', 'regeneration_index', 'content')
                      ->orderBy('regeneration_index', 'asc');
            },
            'originalMessage.feedback',
            'aiModel',
        ]);
    }

    // Get message by ID
    public function getMessageById(int $id): ?Message
    {
        return $this->messageRepository->findById($id);
    }
}
