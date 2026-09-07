<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'title',
        'avatar',
        'created_by',
    ];

    /**
     * Users participating in this conversation.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_user')
            ->withPivot(['role', 'last_read_message_id', 'is_muted'])
            ->withTimestamps();
    }

    /**
     * Creator of the conversation / group.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * All messages in this conversation.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * The latest message in this conversation.
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * Determine conversation name relative to the viewing user.
     */
    public function getTitleFor(User $viewer): string
    {
        if ($this->type === 'group') {
            return $this->title ?? 'Grupo Anónimo';
        }

        $otherUser = $this->users->firstWhere('id', '!=', $viewer->id);

        return $otherUser ? $otherUser->name : 'Usuario';
    }

    /**
     * Determine conversation avatar relative to the viewing user.
     */
    public function getAvatarFor(User $viewer): string
    {
        if ($this->type === 'group') {
            return $this->avatar ? (str_starts_with($this->avatar, 'http') ? $this->avatar : asset('storage/'.$this->avatar)) : 'https://api.dicebear.com/7.x/identicon/svg?seed='.urlencode($this->id.($this->title ?? 'group'));
        }

        $otherUser = $this->users->firstWhere('id', '!=', $viewer->id);

        return $otherUser ? $otherUser->avatar_url : 'https://api.dicebear.com/7.x/bottts-neutral/svg?seed=user';
    }

    /**
     * Determine if other participant in a direct conversation is online.
     */
    public function isOtherUserOnline(User $viewer): bool
    {
        if ($this->type === 'group') {
            return false;
        }

        $otherUser = $this->users->firstWhere('id', '!=', $viewer->id);

        return $otherUser ? (bool) $otherUser->is_online : false;
    }

    /**
     * Get unread messages count for a specific user.
     */
    public function unreadCountFor(User $user): int
    {
        $pivot = $this->users->firstWhere('id', $user->id)?->pivot;
        $lastReadId = $pivot ? $pivot->last_read_message_id : 0;

        return $this->messages()
            ->where('sender_id', '!=', $user->id)
            ->where('id', '>', (int) $lastReadId)
            ->count();
    }
}
