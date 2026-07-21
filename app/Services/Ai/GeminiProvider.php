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

        // Gemini models "think" before answering by default, which consumes
        // part of the token budget and can truncate the actual JSON answer
        // for longer inputs. We don't need reasoning for a structured-
        // extraction task, so we turn it down/off — but the parameter name
        // is NOT the same across model generations:
        //   - Gemini 2.x (2.5 Flash etc.): 'thinkingBudget' (0 = off)
        //   - Gemini 3.x (3.5 Flash etc.): 'thinkingLevel' ('minimal'|'low'|
        //     'medium'|'high') — 'thinkingBudget' is NOT accepted here and,
        //     combined with structured output, has been observed to make
        //     the request hang indefinitely instead of erroring cleanly.
        $isGemini3 = str_starts_with($this->model, 'gemini-3');
        $thinkingConfig = $isGemini3
            ? ['thinkingLevel' => 'low']
            : ['thinkingBudget' => 0];

        $payload = [
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'contents' => [
                ['parts' => [['text' => $userMessage]]],
            ],
            'generationConfig' => [
                'temperature' => 0,
                'maxOutputTokens' => 4096,
                'thinkingConfig' => $thinkingConfig,
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
