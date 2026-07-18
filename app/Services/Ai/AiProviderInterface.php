<?php

namespace App\Services\Ai;

/**
 * Every AI provider (Anthropic, OpenAI, or any future provider) implements
 * this single method. The rest of the application never talks to a
 * specific provider directly — it only depends on this interface, so
 * switching providers is a config change, not a code change.
 */
interface AiProviderInterface
{
    /**
     * Sends a system prompt + user message to the AI provider and returns
     * the raw text of its reply (expected to contain a JSON object).
     *
     * @throws \RuntimeException on network/API errors
     */
    public function complete(string $systemPrompt, string $userMessage): string;
}
