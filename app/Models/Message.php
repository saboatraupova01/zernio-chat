<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'zernio_message_id',
        'sender_type',
        'text',
        'payload',
        'platform_message_id',
        'reply_to_message_id',
        'status',
        'sent_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'sent_at' => 'datetime',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function replyTo()
    {
        return $this->belongsTo(Message::class, 'reply_to_message_id');
    }

    public function replies()
    {
        return $this->hasMany(Message::class, 'reply_to_message_id');
    }
}
