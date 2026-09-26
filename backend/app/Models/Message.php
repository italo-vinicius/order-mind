<?php

namespace App\Models;

use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['conversation_id', 'role', 'content', 'metadata'])]
class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function toolLogs(): HasMany
    {
        return $this->hasMany(ToolLog::class);
    }
}
