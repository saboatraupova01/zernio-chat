<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Conversation extends Model
{
    protected $fillable = [
        'channel',
        'zernio_conversation_id',
        'zernio_account_id',
        'participant_id',
        'participant_name',
        'participant_phone',
        'status',
        'operator_status',
        'assigned_operator_id',
        'last_message_at',
        'sender_type',
        'sent_at',
        'payload',
        'text',
        'zernio_message_id',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];


    public function messages()
    {
        return $this->hasMany(Message::class);
    }
    public function assignedOperator()
    {
        return $this->belongsTo(User::class, 'assigned_operator_id');
    }
}
