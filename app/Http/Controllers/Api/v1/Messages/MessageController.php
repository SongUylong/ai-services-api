<?php

namespace App\Http\Controllers\Api\v1\Messages;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Message\StoreMessageRequest;
use App\Http\Resources\Messages\MessageResource;
use App\Models\Conversations\Conversation;
use App\Models\Messages\Message;
use App\Services\Conversations\ConversationService;
use App\Services\Messages\MessageService;
use Illuminate\Support\Facades\Auth;

class MessageController extends ApiController
{
    public function __construct(
        protected MessageService $messageService,
        protected ConversationService $conversationService
    ) {}

    // Store a new message in a conversation (with optional attachments)
    public function store(StoreMessageRequest $request, Conversation $conversation)
    {
        // Check if user can create messages in this conversation
        $this->authorize('view', $conversation);
        $this->authorize('create', Message::class);

        // Create user message (handles attachments if provided)
        $message = $this->messageService->createUserMessage($conversation, $request->user(), $request->validated());

        // Generate bot response
        // TODO: Implement sending attachments to AI service when available
        $botMessage = $this->messageService->generateBotResponse($conversation, $message);

        return $this->created(
            [
                'user_message' => new MessageResource($message),
                'bot_message' => new MessageResource($botMessage),
            ],
            'Message sent successfully'
        );
    }

    // Store a new message and create a new conversation
    public function storeNewConversation(StoreMessageRequest $request)
    {
        $user = $request->user();
        
        // Authorization check
        $this->authorize('create', Conversation::class);
        $this->authorize('create', Message::class);

        // Create new conversation
        $conversation = $this->conversationService->createConversation(
            $user->id,
            ['title' => 'New Conversation']
        );

        // Create user message (handles attachments if provided)
        $message = $this->messageService->createUserMessage($conversation, $user, $request->validated());

        // Generate bot response
        $botMessage = $this->messageService->generateBotResponse($conversation, $message);

        return $this->created(
            [
                'conversation_id' => $conversation->id,
                'title' => 'Mock Title - New Conversation',
                'user_message' => new MessageResource($message),
                'bot_message' => new MessageResource($botMessage),
            ],
            'Message sent successfully'
        );
    }

    // Regenerate a bot message
    public function regenerate(Message $message)
    {
        $this->authorize('regenerate', $message);

        $newMessage = $this->messageService->regenerateMessage($message);

        return $this->okWithData(
            new MessageResource($newMessage),
            'Message regenerated successfully'
        );
    }
}
