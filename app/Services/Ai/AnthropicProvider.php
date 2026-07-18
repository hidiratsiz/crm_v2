<?php

namespace App\Services\Ai;

class AnthropicProvider implements AiProviderInterface
{
    use HttpPostJsonTrait;

    public function __construct(
        private string $apiKey,
        private string $model,
        private string $apiUrl = 'https://api.anthropic.com/v1/messages'
    ) {
    }

    public function complete(string $systemPrompt, string $userMessage): string
    {
        $payload = [
            'model' => $this->model,
            'max_tokens' => 1024,
            'system' => $systemPrompt,
            'messages' => [
                ['role' => 'user', 'content' => $userMessage],
            ],
        ];

        $response = $this->postJson($this->apiUrl, [
            'Content-Type: application/json',
            'x-api-key: ' . $this->apiKey,
            'anthropic-version: 2023-06-01',
        ], $payload);

        $data = json_decode($response, true);

        return $data['content'][0]['text'] ?? '';
    }
}
