<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ZernioService
{
    public function getConversations(): array
    {
        $response = Http::withToken(config('services.zernio.api_key'))
            ->get(config('services.zernio.base_url') . '/inbox/conversations');

        $response->throw();

        return $response->json();
    }

public function getMessages(string $conversationId, string $accountId): array
{
    $response = Http::withToken(config('services.zernio.api_key'))
        ->get(
            config('services.zernio.base_url')
            . "/inbox/conversations/{$conversationId}/messages",
            [
                'accountId' => $accountId,
            ]
        );

    $response->throw();

    return $response->json();
}

    public function sendMessage(
        string $conversationId,
        string $accountId,
        string $text,
        ?string $replyTo = null
    ): array {
        $data = [
            'accountId' => $accountId,
            'message' => $text,
        ];

        if ($replyTo) {
            $data['replyTo'] = $replyTo;
        }

        $response = Http::withToken(config('services.zernio.api_key'))
            ->post(
                config('services.zernio.base_url')
                . "/inbox/conversations/{$conversationId}/messages",
                $data
            );

        $response->throw();

        return $response->json();
    }
}
