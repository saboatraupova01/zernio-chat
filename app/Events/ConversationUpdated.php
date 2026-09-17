<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Conversation $conversation
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('conversations'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'conversation.updated';
    }

    public function broadcastWith(): array
    {
        $this->conversation->load('assignedOperator');

        return [
            'conversation' => [
                'id' => $this->conversation->id,
                'channel' => $this->conversation->channel,
                'participant_name' => $this->conversation->participant_name,
                'operator_status' => $this->conversation->operator_status,
                'assigned_operator_id' => $this->conversation->assigned_operator_id,
                'assigned_operator_name' => $this->conversation->assignedOperator?->name,
            ],
        ];
    }
}
