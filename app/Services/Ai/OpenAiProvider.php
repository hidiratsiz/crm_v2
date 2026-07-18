<?php

namespace App\Services\Ai;

class OpenAiProvider implements AiProviderInterface
{
    use HttpPostJsonTrait;

    public function __construct(
        private string $apiKey,
        private string $model,
        private string $apiUrl = 'https://api.openai.com/v1/chat/completions'
    ) {
    }

    public function complete(string $systemPrompt, string $userMessage): string
    {
        $payload = [
            'model' => $this->model,
            'temperature' => 0,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userMessage],
            ],
        ];

        $response = $this->postJson($this->apiUrl, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ], $payload);

        $data = json_decode($response, true);

        return $data['choices'][0]['message']['content'] ?? '';
    }
}
