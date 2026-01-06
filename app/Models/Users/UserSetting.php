<?php

namespace App\Models\Users;

use App\Models\AiModels\AiModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    // The table associated with the model
    protected $table = 'user_settings';

    // The primary key for the model
    protected $primaryKey = 'user_id';

    // Indicates if the IDs are auto-incrementing
    public $incrementing = false;

    // The attributes that are mass assignable
    protected $fillable = [
        'user_id',
        'theme',
        'language',
        'dark_mode',
        'preferred_ai_model_id',
    ];

    // The attributes that should be cast
    protected function casts(): array
    {
        return [
            'dark_mode' => 'boolean',
            'theme' => 'string',
            'language' => 'string',
            'preferred_ai_model_id' => 'integer',
        ];
    }

    // Get the user that owns the setting
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Get the preferred AI model
    public function preferredAiModel(): BelongsTo
    {
        return $this->belongsTo(AiModel::class, 'preferred_ai_model_id');
    }

    // Get default settings
    public static function defaults(): array
    {
        return [
            'dark_mode' => false,
            'theme' => 'light',
            'language' => 'en',
            'preferred_ai_model_id' => 1,
        ];
    }
}
