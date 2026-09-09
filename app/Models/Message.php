<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'type',
        'body',
        'file_path',
        'file_name',
        'file_size',
        'reply_to_id',
        'is_deleted',
        'expires_at',
        'view_once',
        'viewed_at',
        'is_pinned',
        'pinned_at',
        'pinned_by',
    ];

    protected $appends = [
        'file_url',
    ];

    protected function casts(): array
    {
        return [
            'body' => 'encrypted',
            'file_size' => 'integer',
            'is_deleted' => 'boolean',
            'view_once' => 'boolean',
            'is_pinned' => 'boolean',
            'expires_at' => 'datetime',
            'viewed_at' => 'datetime',
            'pinned_at' => 'datetime',
        ];
    }

    /**
     * Get accessible URL for uploaded file.
     */
    public function getFileUrlAttribute(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        return str_starts_with($this->file_path, 'http') ? $this->file_path : asset('storage/'.$this->file_path);
    }

    /**
     * The conversation this message belongs to.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * User that sent the message.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Original message if this is a quote/reply.
     */
    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'reply_to_id')->with('sender:id,username,display_name');
    }

    /**
     * Receipts tracking who read this message.
     */
    public function receipts(): HasMany
    {
        return $this->hasMany(MessageReceipt::class);
    }

    /**
     * Reactions to this message.
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(MessageReaction::class);
    }

    /**
     * User that pinned this message.
     */
    public function pinnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pinned_by');
    }
}
