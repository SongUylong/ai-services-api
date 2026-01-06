<?php

namespace App\Http\Controllers\Api\v1\Conversations;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Conversation\GetConversationRequest;
use App\Http\Requests\Conversation\GetConversationsRequest;
use App\Http\Requests\Conversation\StoreConversationRequest;
use App\Http\Requests\Conversation\UpdateConversationRequest;
use App\Http\Resources\Conversations\ConversationResource;
use App\Models\Conversations\Conversation;
use App\Services\Conversations\ConversationService;
use Illuminate\Support\Facades\Auth;

class ConversationController extends ApiController
{
    public function __construct(
        protected ConversationService $conversationService
    ) {}

    // List all conversations for authenticated user
    public function index(GetConversationsRequest $request)
    {

        $this->authorize('viewAny', Conversation::class);

        $userId = Auth::id();

        $conversations = $this->conversationService->listUserConversations($userId);

        return $this->okWithData(ConversationResource::collection($conversations));
    }

    // Create a new conversation
    public function store(StoreConversationRequest $request)
    {
        $this->authorize('create', Conversation::class);

        $userId = Auth::id();

        $conversation = $this->conversationService->createConversation($userId, $request->validated());

        return $this->created(
            new ConversationResource($conversation),
            'Conversation created successfully'
        );
    }

    // Get a specific conversation with messages
    public function show(GetConversationRequest $request, Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        $validated = $request->validated();
        $perPage = $validated['per_page'] ?? null;

        $conversationWithMessages = $this->conversationService->getConversationWithMessages($conversation, $perPage);

        return $this->okWithData(new ConversationResource($conversationWithMessages));
    }

    // Update conversation (rename)
    public function update(UpdateConversationRequest $request, Conversation $conversation)
    {
        $this->authorize('update', $conversation);

        $this->conversationService->updateConversation($conversation, $request->validated());

        return $this->updated(
            new ConversationResource($conversation->fresh()),
            'Conversation updated successfully'
        );
    }

    // Soft delete conversation
    public function destroy(Conversation $conversation)
    {
        $this->authorize('delete', $conversation);

        $this->conversationService->deleteConversation($conversation);

        return $this->deleted('Conversation deleted successfully');
    }
}
