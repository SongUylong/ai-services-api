<?php

namespace App\Http\Controllers\Api\v1\Messages;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Message\StoreFeedbackRequest;
use App\Http\Resources\Messages\MessageFeedbackResource;
use App\Models\Messages\Message;
use App\Models\Messages\MessageFeedback;
use App\Services\Messages\MessageFeedbackService;
use Illuminate\Support\Facades\Auth;

class MessageFeedbackController extends ApiController
{
    public function __construct(
        protected MessageFeedbackService $feedbackService
    ) {}

    // Update feedback for a message (PUT - RESTful standard)
    public function update(StoreFeedbackRequest $request, Message $message)
    {
        $this->authorize('create', [MessageFeedback::class, $message]);

        $user = Auth::user();
        $feedbackType = $request->validated()['feedback_type'];

        $feedback = $this->feedbackService->giveFeedback($message, $user, $feedbackType);

        return $this->okWithData(
            new MessageFeedbackResource($feedback),
            'Feedback updated successfully'
        );
    }

    // Remove feedback from a message
    public function destroy(Message $message)
    {
        // Get the user's feedback for this message
        $feedback = $message->feedback()
            ->where('user_id', Auth::id())
            ->first();

        if (!$feedback) {
            return $this->notFound('Feedback not found');
        }

        $this->authorize('delete', $feedback);

        $this->feedbackService->removeFeedback($feedback);

        return $this->deleted('Feedback removed successfully');
    }
}
