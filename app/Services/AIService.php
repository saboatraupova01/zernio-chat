<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AIService
{
    public function generateResponse(string $clientMessage): string
    {
        $response = Http::timeout(60)
            ->withToken(env('OPENROUTER_API_KEY'))
            ->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => 'dots-studio/dots-3-note-preview:free',

                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Ты — вежливый менеджер службы поддержки. '
                            . 'Отвечай клиенту на русском языке. '
                            . 'Отвечай кратко, максимум 2-3 предложения.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $clientMessage,
                    ],
                ],
            ]);

        if (!$response->successful()) {
            throw new \Exception(
                'OpenRouter error: '
                . $response->status()
                . ' '
                . $response->body()
            );
        }

        $answer = $response->json('choices.0.message.content');

        if (!is_string($answer) || trim($answer) === '') {
            throw new \Exception(
                'OpenRouter returned empty answer: '
                . $response->body()
            );
        }

        return trim($answer);
    }
}
