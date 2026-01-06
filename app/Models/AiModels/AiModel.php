<?php

namespace App\Models\AiModels;

use App\Models\Messages\Message;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiModel extends Model
{
    protected $fillable = ['name', 'description'];

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
