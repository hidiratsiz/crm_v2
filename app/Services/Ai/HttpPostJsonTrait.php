<?php

namespace App\Services\Ai;

/**
 * Small shared cURL helper used by every provider adapter.
 * Not part of the public interface — just avoids repeating the same
 * cURL boilerplate in every provider class.
 */
trait HttpPostJsonTrait
{
    private function postJson(string $url, array $headers, array $payload, int $timeoutSeconds = 30): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => $timeoutSeconds,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException('AI istegine baglanilamadi: ' . $curlError);
        }
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException("AI servisi hata dondu (HTTP {$httpCode}): " . substr((string) $response, 0, 500));
        }

        return $response;
    }
}
