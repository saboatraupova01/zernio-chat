<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Services\ZernioService;
use Illuminate\Http\Request;
use App\Events\ConversationUpdated;

class ConversationController extends Controller
{
    public function sync(ZernioService $zernio)
    {
        $response = $zernio->getConversations();

        foreach ($response['data'] as $item) {
            Conversation::updateOrCreate(
                [
                    'zernio_conversation_id' => $item['id'],
                ],
                [
                    'channel' => $item['platform'],
                    'zernio_account_id' => $item['accountId'],
                    'participant_id' => $item['participantId'] ?? null,
                    'participant_name' => $item['participantName'] ?? null,
                    'participant_phone' => $item['participantUsername'] ?? null,
                    'status' => $item['status'] ?? 'active',
                    'last_message_at' => $item['updatedTime'] ?? null,
                ]
            );
        }

        return response()->json([
            'message' => 'Conversations synchronized',
            'count' => count($response['data']),
        ]);
    }

    public function syncMessages(Conversation $conversation, ZernioService $zernio)
    {
        $response = $zernio->getMessages(
            $conversation->zernio_conversation_id,
            $conversation->zernio_account_id
        );

        foreach ($response['messages'] as $item) {
            $conversation->messages()->updateOrCreate(
                [
                    'zernio_message_id' => $item['id'],
                ],
                [
                    'sender_type' => $item['direction'] === 'incoming'
                        ? 'customer'
                        : 'operator',

                    'text' => $item['message'] ?? null,

                    'payload' => $item,

                    'status' => $item['deliveryStatus'] ?? null,

                    'sent_at' => $item['sentAt'] ?? null,
                ]
            );
        }

        return response()->json([
            'message' => 'Messages synchronized',
            'count' => count($response['messages']),
        ]);
    }

    public function index(Request $request)
    {
        $query = Conversation::query();
        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }
        $conversations = $query
            ->orderByDesc('last_message_at')
            ->get();
        return view('conversations.index', compact('conversations'));
    }

    public function show(Conversation $conversation)
    {
        $conversation->load([
            'messages' => fn ($query) => $query->orderBy('sent_at'),
        ]);

        return view('conversations.show', compact('conversation'));
    }

    public function updateStatus(
        Request $request,
        Conversation $conversation
    ) {
        $validated = $request->validate([
            'operator_status' => [
                'required',
                'in:new,in_progress,waiting,closed',
            ],
        ]);

        $conversation->update([
            'operator_status' => $validated['operator_status'],
        ]);
        ConversationUpdated::dispatch($conversation);
        return redirect()
            ->route('conversations.show', $conversation)
            ->with('success', 'Статус обновлён');
    }
    public function assignOperator(Request $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'assigned_operator_id' => [
                'nullable',
                'exists:users,id',
            ],
        ]);

        $conversation->update([
            'assigned_operator_id' => $validated['assigned_operator_id'] ?? null,
        ]);
        ConversationUpdated::dispatch($conversation);
        return redirect()
            ->route('conversations.show', $conversation)
            ->with('success', 'Оператор назначен');
    }

    public function show_operator(Conversation $conversation)
    {
        $conversation->load([
            'messages' => fn ($query) => $query
                ->with('replyTo')
                ->orderBy('sent_at'),
            'assignedOperator',
        ]);

        return view('conversations.show', compact('conversation'));
    }
}
