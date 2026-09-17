<?php

namespace App\Http\Controllers;

use App\Events\MessageReceived;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Events\ConversationUpdated;

class WebhookController extends Controller
{
    public function zernio(Request $request)
    {
        $rawBody = $request->getContent();

        $signature = $request->header('X-Zernio-Signature');

        $expectedSignature = hash_hmac(
            'sha256',
            $rawBody,
            config('services.zernio.webhook_secret')
        );

        if (!$signature || !hash_equals($expectedSignature, $signature)) {
            return response()->json([
                'message' => 'Invalid signature',
            ], 401);
        }

        $payload = json_decode($rawBody, true);

        $eventId = $payload['id'] ?? null;
        $event = $payload['event'] ?? null;

        if (!$eventId || !$event) {
            return response()->json([
                'message' => 'Invalid webhook payload',
            ], 400);
        }

        $webhookEvent = WebhookEvent::firstOrCreate(
            [
                'zernio_event_id' => $eventId,
            ],
            [
                'event' => $event,
                'payload' => $payload,
            ]
        );

        if ($webhookEvent->wasRecentlyCreated === false) {
            return response()->json([
                'message' => 'Event already processed',
            ]);
        }

        if ($event === 'message.received') {
            $this->handleMessageReceived($payload);
        }

        $webhookEvent->update([
            'processed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Webhook received',
        ]);
    }

    private function handleMessageReceived(array $payload): void
    {
        $conversationId = $payload['conversation']['id'] ?? null;
        $messageData = $payload['message'] ?? [];
        $accountData = $payload['account'] ?? [];

        $messageId = $messageData['id'] ?? null;

        $accountId = $accountData['accountId']
            ?? $accountData['id']
            ?? null;

        $participantId = $payload['conversation']['participantId']
            ?? $messageData['sender']['id']
            ?? null;

        if (!$conversationId || !$messageId || !$accountId || !$participantId) {
            Log::warning('Invalid Zernio webhook payload', [
                'payload' => $payload,
            ]);

            return;
        }

        $conversation = Conversation::where(
            'zernio_account_id',
            $accountId
        )
            ->where(
                'participant_id',
                $participantId
            )
            ->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'channel' => $messageData['platform']
                    ?? $accountData['platform']
                        ?? 'unknown',

                'zernio_conversation_id' => $conversationId,

                'zernio_account_id' => $accountId,

                'participant_id' => $participantId,

                'participant_name' => $payload['conversation']['participantName']
                    ?? $messageData['sender']['name']
                        ?? null,

                'participant_phone' => $payload['conversation']['participantUsername']
                    ?? $messageData['sender']['phoneNumber']
                        ?? $messageData['sender']['username']
                        ?? null,

                'status' => $payload['conversation']['status'] ?? 'active',

                'last_message_at' => isset($messageData['sentAt'])
                    ? \Carbon\Carbon::parse($messageData['sentAt'])->addHours(5)
                    : now(),
            ]);

            Log::info('New conversation created from webhook', [
                'conversation_id' => $conversation->id,
                'channel' => $conversation->channel,
                'participant_id' => $participantId,
                'zernio_conversation_id' => $conversationId,
            ]);
        } else {

            if ($conversation->zernio_conversation_id !== $conversationId) {
                $conversation->update([
                    'zernio_conversation_id' => $conversationId,
                ]);
            }
        }

        Log::info('Zernio message timestamp', [
            'sentAt' => $messageData['sentAt'] ?? null,
            'timestamp' => $payload['timestamp'] ?? null,
        ]);

        $sentAt = isset($messageData['sentAt'])
            ? \Carbon\Carbon::parse($messageData['sentAt'])->addHours(5)
            : (isset($payload['timestamp'])
                ? \Carbon\Carbon::parse($payload['timestamp'])->addHours(5)
                : now());

        $message = $conversation->messages()->updateOrCreate(
            [
                'zernio_message_id' => $messageId,
            ],
            [
                'sender_type' => 'customer',
                'text' => $messageData['text'] ?? null,
                'payload' => $payload,
                'platform_message_id' => $messageData['platformMessageId'] ?? null,
                'status' => 'received',
                'sent_at' => $sentAt,
            ]
        );

        Log::info('Zernio message saved', [
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
            'zernio_message_id' => $messageId,
            'text' => $message->text,
        ]);

        MessageReceived::dispatch($message);
        ConversationUpdated::dispatch($conversation);

        $conversation->update([
            'last_message_at' => $sentAt,
        ]);
    }
    }
