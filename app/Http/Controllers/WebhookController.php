<?php

namespace App\Http\Controllers;

use App\Events\ConversationUpdated;
use App\Events\MessageReceived;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\WebhookEvent;
use App\Services\AIService;
use App\Services\ZernioService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class WebhookController extends Controller
{
    protected AIService $aiService;
    protected ZernioService $zernioService;

    public function __construct(
        AIService $aiService,
        ZernioService $zernioService
    ) {
        $this->aiService = $aiService;
        $this->zernioService = $zernioService;
    }

    /**
     * Webhook от Zernio.
     */
    public function zernio(Request $request)
    {
        $rawBody = $request->getContent();
        $signature = $request->header('X-Zernio-Signature');

        /*
         * Проверяем подпись webhook.
         */
        $webhookSecret = config('services.zernio.webhook_secret');

        if (!$webhookSecret) {
            Log::error('Zernio webhook secret is not configured.');

            return response()->json([
                'message' => 'Webhook configuration error',
            ], 500);
        }

        $expectedSignature = hash_hmac(
            'sha256',
            $rawBody,
            $webhookSecret
        );

        if (
            !$signature ||
            !hash_equals($expectedSignature, $signature)
        ) {
            Log::warning('Invalid Zernio webhook signature.');

            return response()->json([
                'message' => 'Invalid signature',
            ], 401);
        }

        /*
         * Расшифровываем JSON.
         */
        $payload = json_decode($rawBody, true);

        if (!is_array($payload)) {
            Log::warning('Invalid Zernio webhook JSON.', [
                'body' => $rawBody,
            ]);

            return response()->json([
                'message' => 'Invalid JSON payload',
            ], 400);
        }

        $eventId = $payload['id'] ?? null;
        $event = $payload['event'] ?? null;

        if (!$eventId || !$event) {
            Log::warning('Invalid Zernio webhook payload.', [
                'payload' => $payload,
            ]);

            return response()->json([
                'message' => 'Invalid webhook payload',
            ], 400);
        }

        /*
         * Защита от повторной обработки одного webhook.
         */
        $webhookEvent = WebhookEvent::firstOrCreate(
            [
                'zernio_event_id' => $eventId,
            ],
            [
                'event' => $event,
                'payload' => $payload,
            ]
        );

        if (!$webhookEvent->wasRecentlyCreated) {
            Log::info('Zernio webhook already processed.', [
                'event_id' => $eventId,
            ]);

            return response()->json([
                'message' => 'Event already processed',
            ], 200);
        }

        /*
         * Обрабатываем только нужный тип события.
         */
        if ($event === 'message.received') {
            $this->handleMessageReceived($payload);
        }

        /*
         * Помечаем webhook как обработанный.
         */
        $webhookEvent->update([
            'processed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Webhook received',
        ], 200);
    }

    /**
     * Обработка входящего сообщения клиента.
     */
    private function handleMessageReceived(array $payload): void
    {
        $conversationData = $payload['conversation'] ?? [];
        $messageData = $payload['message'] ?? [];
        $accountData = $payload['account'] ?? [];

        /*
         * Получаем основные ID.
         */
        $conversationId = $conversationData['id'] ?? null;
        $messageId = $messageData['id'] ?? null;

        $accountId = $accountData['accountId']
            ?? $accountData['id']
            ?? null;

        $participantId = $conversationData['participantId']
            ?? $messageData['sender']['id']
            ?? null;

        /*
         * Проверяем обязательные данные.
         */
        if (
            !$conversationId ||
            !$messageId ||
            !$accountId ||
            !$participantId
        ) {
            Log::warning('Invalid Zernio message webhook payload.', [
                'conversation_id' => $conversationId,
                'message_id' => $messageId,
                'account_id' => $accountId,
                'participant_id' => $participantId,
                'payload' => $payload,
            ]);

            return;
        }

        $sentAt = $this->parseMessageTime(
            $messageData['sentAt'] ?? null,
            $payload['timestamp'] ?? null
        )->setTimezone(config('app.timezone'));

        /*
         * Ищем существующий диалог.
         */
        $conversation = Conversation::where(
            'zernio_account_id',
            $accountId
        )
            ->where(
                'participant_id',
                $participantId
            )
            ->first();

        /*
         * Если диалога ещё нет — создаём.
         */
        if (!$conversation) {
            $conversation = Conversation::create([
                'channel' => $messageData['platform']
                    ?? $accountData['platform']
                        ?? 'unknown',

                'zernio_conversation_id' => $conversationId,

                'zernio_account_id' => $accountId,

                'participant_id' => $participantId,

                'participant_name' => $conversationData['participantName']
                    ?? $messageData['sender']['name']
                        ?? null,

                'participant_phone' => $conversationData['participantUsername']
                    ?? $messageData['sender']['phoneNumber']
                        ?? $messageData['sender']['username']
                        ?? null,

                'status' => $conversationData['status']
                    ?? 'active',

                'last_message_at' => $sentAt,
            ]);

            Log::info('New conversation created from Zernio webhook.', [
                'conversation_id' => $conversation->id,
                'zernio_conversation_id' => $conversationId,
                'account_id' => $accountId,
                'participant_id' => $participantId,
                'channel' => $conversation->channel,
            ]);
        } else {
            /*
             * На всякий случай обновляем Zernio conversation ID.
             */
            if (
                $conversation->zernio_conversation_id !== $conversationId
            ) {
                $conversation->update([
                    'zernio_conversation_id' => $conversationId,
                ]);
            }
        }

        Log::info('Zernio message timestamp.', [
            'sentAt' => $messageData['sentAt'] ?? null,
            'timestamp' => $payload['timestamp'] ?? null,
            'parsed' => $sentAt?->toDateTimeString(),
        ]);

        /*
         * Сохраняем входящее сообщение.
         *
         * updateOrCreate защищает от повторного
         * сохранения одного и того же сообщения.
         */
        $message = $conversation->messages()->updateOrCreate(
            [
                'zernio_message_id' => $messageId,
            ],
            [
                'sender_type' => 'customer',

                'text' => $messageData['text'] ?? null,

                'payload' => $payload,

                'platform_message_id' =>
                    $messageData['platformMessageId'] ?? null,

                'status' => 'received',

                'sent_at' => $sentAt,
            ]
        );

        /*
         * Обновляем время последнего сообщения.
         */
        $conversation->update([
            'last_message_at' => $sentAt,
        ]);

        Log::info('Zernio message saved.', [
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
            'zernio_message_id' => $messageId,
            'text' => $message->text,
        ]);

        /*
         * Обновляем интерфейс через WebSocket.
         */
        MessageReceived::dispatch($message);
        ConversationUpdated::dispatch($conversation);

        /*
         * ==========================================================
         * AI AUTO REPLY
         * ==========================================================
         */

        $customerText = trim((string) $message->text);

        /*
         * Если сообщения без текста
         * (например, изображение), AI не запускаем.
         */
        if ($customerText === '') {
            return;
        }

        try {
            /*
             * 1. Генерируем ответ через AI.
             */
            $aiReplyText = $this->aiService->generateResponse(
                $customerText
            );

            if (!$aiReplyText) {
                Log::warning('AI returned empty response.', [
                    'conversation_id' => $conversation->id,
                    'message_id' => $message->id,
                ]);

                return;
            }

            /*
             * 2. Отправляем ответ через Zernio.
             */
            $zernioResponse = $this->zernioService->sendMessage(
                $conversationId,
                $accountId,
                $aiReplyText
            );

            /*
             * 3. Получаем ID сообщения Zernio.
             */
            $aiMessageId = $zernioResponse['id']
                ?? 'ai_' . uniqid();

            /*
             * 4. Сохраняем ответ AI в нашей БД.
             */
            $aiMessage = $conversation->messages()->create([
                'zernio_message_id' => $aiMessageId,

                'sender_type' => 'operator',

                'text' => $aiReplyText,

                'status' => 'sent',

                'sent_at' => now(),
            ]);

            /*
             * 5. Обновляем WebSocket.
             */
            MessageReceived::dispatch($aiMessage);
            ConversationUpdated::dispatch($conversation);

            /*
             * 6. Обновляем last_message_at.
             */
            $conversation->update([
                'last_message_at' => now(),
            ]);

            Log::info('AI automatic reply sent successfully.', [
                'conversation_id' => $conversation->id,
                'ai_message_id' => $aiMessage->id,
                'text' => $aiReplyText,
            ]);
        } catch (Throwable $e) {
            /*
             * Ошибка AI или Zernio не должна ломать webhook.
             */
            Log::error('AI auto-reply failed.', [
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }

    /**
     * Определяет время сообщения.
     */
    private function parseMessageTime(
        ?string $sentAt,
        ?string $fallbackTimestamp
    ): Carbon {
        try {
            if ($sentAt) {
                return Carbon::parse($sentAt);
            }

            if ($fallbackTimestamp) {
                return Carbon::parse($fallbackTimestamp);
            }
        } catch (Throwable $e) {
            Log::warning('Failed to parse Zernio message timestamp.', [
                'sentAt' => $sentAt,
                'timestamp' => $fallbackTimestamp,
                'error' => $e->getMessage(),
            ]);
        }

        return now();
    }
}

