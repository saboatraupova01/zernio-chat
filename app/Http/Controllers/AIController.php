<?php


namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Services\AIService;
use App\Services\ZernioService;
use Illuminate\Support\Facades\Cache;

class AIController extends Controller
{
    public function start(Conversation $conversation)
    {
        Cache::forever('ai_enabled', true);

        $ai = app(AIService::class);
        $zernio = app(ZernioService::class);

        $greeting = $ai->generateReply(
            'Ты AI-помощник службы поддержки такси. '
            . 'Поздоровайся с клиентом и коротко скажи, что ты готов помочь. '
            . 'Ответ должен быть на русском языке, дружелюбным и очень коротким.'
        );

        $response = $zernio->sendMessage(
            $conversation->zernio_conversation_id,
            $conversation->zernio_account_id,
            $greeting
        );

        $zernioMessage = $response['data'] ?? null;

        if ($zernioMessage) {
            $conversation->messages()->create([
                'zernio_message_id' => $zernioMessage['messageId'],
                'sender_type' => 'operator',
                'text' => $greeting,
                'payload' => $response,
                'platform_message_id' => $zernioMessage['messageId'] ?? null,
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        }

        return redirect()
            ->route('conversations.show', $conversation)
            ->with('success', 'AI-помощник запущен');
    }
}
