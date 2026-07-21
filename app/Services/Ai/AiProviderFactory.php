<?php

namespace App\Services\Ai;

use RuntimeException;

/**
 * Reads config/config.php's 'ai' section and builds the right provider
 * adapter. This is the ONLY place in the codebase that branches on which
 * AI provider is configured — everything else (AiIntakeParser, controllers)
 * only ever talks to the AiProviderInterface.
 *
 * To add a new provider later: create a class implementing
 * AiProviderInterface, add one case below, done — nothing else changes.
 */
class AiProviderFactory
{
    public static function make(array $config): AiProviderInterface
    {
        $aiConfig = $config['ai'] ?? [];
        $provider = $aiConfig['provider'] ?? 'anthropic';

        switch ($provider) {
            case 'anthropic':
                $settings = $aiConfig['anthropic'] ?? [];
                self::assertKey($settings, 'anthropic');
                return new AnthropicProvider(
                    $settings['api_key'],
                    $settings['model'] ?? 'claude-haiku-4-5-20251001',
                    $settings['api_url'] ?? 'https://api.anthropic.com/v1/messages'
                );

            case 'openai':
                $settings = $aiConfig['openai'] ?? [];
                self::assertKey($settings, 'openai');
                return new OpenAiProvider(
                    $settings['api_key'],
                    $settings['model'] ?? 'gpt-4o-mini'
                );

            case 'gemini':
                $settings = $aiConfig['gemini'] ?? [];
                self::assertKey($settings, 'gemini');
                return new GeminiProvider(
                    $settings['api_key'],
                    $settings['model'] ?? 'gemini-3.5-flash',
                    $settings['api_url'] ?? 'https://generativelanguage.googleapis.com/v1beta/models'
                );

            case 'openai_compatible':
                // Any provider exposing an OpenAI-compatible /chat/completions
                // endpoint (self-hosted models, other vendors, etc.) — just
                // point 'api_url' at their endpoint, no new code needed.
                $settings = $aiConfig['openai_compatible'] ?? [];
                self::assertKey($settings, 'openai_compatible');
                if (empty($settings['api_url'])) {
                    throw new RuntimeException("'openai_compatible' saglayicisi icin 'api_url' config/config.php icinde belirtilmeli.");
                }
                return new OpenAiProvider(
                    $settings['api_key'],
                    $settings['model'] ?? '',
                    $settings['api_url']
                );

            default:
                throw new RuntimeException("Bilinmeyen AI saglayici: '{$provider}'. Gecerli degerler: anthropic, openai, gemini, openai_compatible.");
        }
    }

    private static function assertKey(array $settings, string $providerName): void
    {
        $key = $settings['api_key'] ?? '';
        if ($key === '' || strpos($key, 'CHANGE_ME') === 0) {
            throw new RuntimeException("'{$providerName}' icin API anahtari config/config.php icinde ayarlanmamis (ai.{$providerName}.api_key).");
        }
    }
}
