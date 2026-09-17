<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function send(Request $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:5000'],
            'reply_to_message_id' => [
                'nullable',
                'integer',
                'exists:messages,id',
            ],
        ]);

        $replyToMessage = null;

        if (!empty($validated['reply_to_message_id'])) {
            $replyToMessage = $conversation->messages()
                ->where('id', $validated['reply_to_message_id'])
                ->firstOrFail();
        }

        $zernio = app(\App\Services\ZernioService::class);

        $response = $zernio->sendMessage(
            $conversation->zernio_conversation_id,
            $conversation->zernio_account_id,
            $validated['text'],
            $replyToMessage?->platform_message_id
        );

        $zernioMessage = $response['data'] ?? null;

        if ($zernioMessage) {
            $conversation->messages()->create([
                'zernio_message_id' => $zernioMessage['messageId'],
                'sender_type' => 'operator',
                'text' => $validated['text'],
                'payload' => $response,
                'platform_message_id' => $zernioMessage['messageId'] ?? null,
                'reply_to_message_id' => $replyToMessage?->id,
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        }

        return redirect()
            ->route('conversations.show', $conversation)
            ->with('success', 'Сообщение отправлено');
    }
}
