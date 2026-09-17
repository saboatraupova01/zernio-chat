<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Message $message
    ) {
        $this->message->load('conversation');
    }

    public function broadcastOn(): array
    {
        return [
            new Channel(
                'conversation.' . $this->message->conversation_id
            ),

            new Channel('conversations'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.received';
    }

    public function broadcastWith(): array
    {
        $conversation = $this->message->conversation;

        return [
            'message' => [
                'id' => $this->message->id,
                'conversation_id' => $this->message->conversation_id,
                'sender_type' => $this->message->sender_type,
                'text' => $this->message->text,
                'status' => $this->message->status,
                'sent_at' => $this->message->sent_at?->toISOString(),
            ],

            'conversation' => [
                'id' => $conversation->id,
                'channel' => $conversation->channel,
                'participant_name' => $conversation->participant_name,
                'participant_id' => $conversation->participant_id,
                'status' => $conversation->status,
                'last_message_at' => $conversation->last_message_at?->toISOString(),
            ],
        ];
    }
}
