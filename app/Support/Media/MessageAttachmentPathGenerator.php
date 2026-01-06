<?php

namespace App\Support\Media;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class MessageAttachmentPathGenerator implements PathGenerator
{
	// Main file storage path: users/{user_id}/conversations/{conversation_id}/messages/{message_id}/
	public function getPath(Media $media): string
	{
		return $this->basePath($media);
	}

	// Conversions path: users/{user_id}/conversations/{conversation_id}/messages/{message_id}/conversions/
	public function getPathForConversions(Media $media): string
	{
		return $this->basePath($media) . 'conversions/';
	}

	// Responsive images path: users/{user_id}/conversations/{conversation_id}/messages/{message_id}/responsive-images/
	public function getPathForResponsiveImages(Media $media): string
	{
		return $this->basePath($media) . 'responsive-images/';
	}

	private function basePath(Media $media): string
	{
		// Get the MessageAttachment model
		$messageAttachment = $media->model;
		
		if (!$messageAttachment || !$messageAttachment->message) {
			return 'unknown/';
		}

		$message = $messageAttachment->message;
		$conversation = $message->conversation;
		
		if (!$conversation) {
			return 'unknown/';
		}

		$userId = $conversation->user_id ?? 'unknown';
		$conversationId = $conversation->id ?? 'unknown';
		$messageId = $message->id ?? 'unknown';

		return "users/{$userId}/conversations/{$conversationId}/messages/{$messageId}/";
	}
}
