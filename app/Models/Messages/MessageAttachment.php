<?php

namespace App\Models\Messages;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\File;

class MessageAttachment extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'message_id',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')
            ->acceptsMimeTypes([
                // Images
                'image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp', 'image/svg+xml',
                // Documents
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'text/plain',
                'text/csv',
                // Archives
                'application/zip',
                'application/x-rar-compressed',
                'application/x-7z-compressed',
                // Audio
                'audio/mpeg', 'audio/wav', 'audio/ogg',
                // Video
                'video/mp4', 'video/mpeg', 'video/quicktime', 'video/webm',
            ])
            ->acceptsFile(fn(File $file) => $file->size <= config('support.maximum_attachment_size') * 1024);
    }
}
