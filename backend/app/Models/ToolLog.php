<?php

namespace App\Models;

use Database\Factories\ToolLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'conversation_id', 'message_id', 'tool_name', 'input', 'output', 'succeeded'])]
class ToolLog extends Model
{
    /** @use HasFactory<ToolLogFactory> */
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'input' => 'array',
            'output' => 'array',
            'succeeded' => 'boolean',
            'created_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}
