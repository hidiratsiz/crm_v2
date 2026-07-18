<?php

namespace App\Services\Ai;

class GeminiProvider implements AiProviderInterface
{
    use HttpPostJsonTrait;

    public function __construct(
        private string $apiKey,
        private string $model,
        private string $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models'
    ) {
    }

    public function complete(string $systemPrompt, string $userMessage): string
    {
        $url = rtrim($this->apiUrl, '/') . '/' . $this->model . ':generateContent';

        $payload = [
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'contents' => [
                ['parts' => [['text' => $userMessage]]],
            ],
            'generationConfig' => [
                'temperature' => 0,
                // Gemini 2.x models "think" before answering by default, which
                // consumes part of the token budget and can truncate the actual
                // JSON answer for longer inputs. We don't need reasoning for a
                // structured-extraction task, so turn it off and give the real
                // answer plenty of room. (If you switch to a Gemini 3.x model
                // that rejects thinkingBudget=0, remove the thinkingConfig block.)
                'maxOutputTokens' => 4096,
                'thinkingConfig' => [
                    'thinkingBudget' => 0,
                ],
            ],
        ];

        $response = $this->postJson($url, [
            'Content-Type: application/json',
            'x-goog-api-key: ' . $this->apiKey,
        ], $payload);

        $data = json_decode($response, true);
        $parts = $data['candidates'][0]['content']['parts'] ?? [];

        // Concatenate every real (non-thought) text part rather than assuming
        // the answer is always a single parts[0] — more robust against
        // response shape variations across Gemini model versions.
        $text = '';
        foreach ($parts as $part) {
            if (!empty($part['thought'])) {
                continue;
            }
            $text .= $part['text'] ?? '';
        }

        return $text;
    }
}
