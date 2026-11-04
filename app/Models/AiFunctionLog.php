<?php

namespace App\Models;

use App\AiFunctionStatus;
use Database\Factories\AiFunctionLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['chat_message_id', 'user_id', 'function_name', 'arguments', 'result', 'status'])]
class AiFunctionLog extends Model
{
    /** @use HasFactory<AiFunctionLogFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'arguments' => 'array',
            'result' => 'array',
            'status' => AiFunctionStatus::class,
        ];
    }

    public function chatMessage(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
