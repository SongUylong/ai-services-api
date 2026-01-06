<?php

use App\Http\Controllers\Api\v1\AiModels\AiModelController;
use App\Http\Controllers\Api\v1\Conversations\ConversationController;
use App\Http\Controllers\Api\v1\Messages\MessageController;
use App\Http\Controllers\Api\v1\Messages\MessageFeedbackController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth.api', 'throttle:conversation'])->prefix('v1')->group(function () {
    // --- AI Models ---
    Route::get('ai-models', [AiModelController::class, 'index'])
        ->name('ai-models.index'); // List all available AI models

    // --- Conversations ---
    Route::get('conversations', [ConversationController::class, 'index'])
        ->name('conversations.index'); // List all chats for user

    Route::get('conversations/{conversation}', [ConversationController::class, 'show'])
        ->name('conversations.show'); // Get chat history

    Route::patch('conversations/{conversation}', [ConversationController::class, 'update'])
        ->name('conversations.update'); // Rename chat

    Route::delete('conversations/{conversation}', [ConversationController::class, 'destroy'])
        ->name('conversations.destroy'); // Soft delete chat

    // --- Messages ---
    Route::post('conversations/{conversation}/messages', [MessageController::class, 'store'])
        ->name('messages.store'); // Send a message (with optional attachments)

    Route::post('conversation/new-message', [MessageController::class, 'storeNewConversation'])
        ->name('messages.store.new'); // Send a message and create new conversation

    Route::post('messages/{message}/regenerate', [MessageController::class, 'regenerate'])
        ->name('messages.regenerate'); // Retry a bot response

    // --- Feedback ---
    Route::put('messages/{message}/feedback', [MessageFeedbackController::class, 'update'])
        ->name('messages.feedback.update'); // Set feedback (like/dislike)

    Route::delete('messages/{message}/feedback', [MessageFeedbackController::class, 'destroy'])
        ->name('messages.feedback.destroy'); // Remove feedback
});
