<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message)
    {
        $this->message = $message->load([
            'sender:id,username,display_name,avatar,is_online',
            'replyTo',
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PresenceChannel('conversation.'.$this->message->conversation_id),
        ];

        // Also broadcast to each recipient's private user channel (for global notification badge)
        $participantIds = $this->message->conversation->users()
            ->where('users.id', '!=', $this->message->sender_id)
            ->pluck('users.id');

        foreach ($participantIds as $userId) {
            $channels[] = new PrivateChannel('user.'.$userId);
        }

        return $channels;
    }

    /**
     * Broadcast as custom event name.
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * Payload for broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'conversation_id' => $this->message->conversation_id,
                'sender_id' => $this->message->sender_id,
                'type' => $this->message->type,
                'body' => $this->message->body,
                'file_path' => $this->message->file_path,
                'file_name' => $this->message->file_name,
                'file_size' => $this->message->file_size,
                'file_url' => $this->message->file_url,
                'reply_to' => $this->message->replyTo,
                'created_at' => $this->message->created_at->toIso8601String(),
                'sender' => [
                    'id' => $this->message->sender->id,
                    'username' => $this->message->sender->username,
                    'name' => $this->message->sender->name,
                    'avatar_url' => $this->message->sender->avatar_url,
                ],
            ],
        ];
    }
}
