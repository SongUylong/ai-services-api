<?php

namespace App\Support\Media;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class UserPathGenerator implements PathGenerator
{
	// Main file storage path: users/{user_id}/
	public function getPath(Media $media): string
	{
		return $this->basePath($media);
	}

	// Conversions path: users/{user_id}/conversions/
	public function getPathForConversions(Media $media): string
	{
		return $this->basePath($media) . 'conversions/';
	}

	// Responsive images path: users/{user_id}/responsive-images/
	public function getPathForResponsiveImages(Media $media): string
	{
		return $this->basePath($media) . 'responsive-images/';
	}

	private function basePath(Media $media): string
	{
		$userId = $media->model_id ?? 'unknown';

		return "users/{$userId}/";
	}
}

