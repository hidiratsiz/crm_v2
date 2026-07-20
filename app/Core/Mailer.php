<?php

namespace App\Core;

/**
 * Minimal email sender using PHP's built-in mail() function — works out of
 * the box on most shared hosting (cPanel etc.) without any extra
 * configuration or Composer package. If your host's mail() doesn't deliver
 * reliably, this is the one place you'd swap in an SMTP client later.
 */
class Mailer
{
    public static function send(string $toEmail, string $toName, string $subject, string $bodyText): bool
    {
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $config = require APP_ROOT . '/config/config.php';
        $fromEmail = $config['app']['mail_from_address'] ?? 'no-reply@' . self::currentHost();
        $fromName = $config['app']['mail_from_name'] ?? ($config['app']['name'] ?? 'JobPro');

        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $encodedFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';

        $headers = [
            'From' => "{$encodedFromName} <{$fromEmail}>",
            'Reply-To' => $fromEmail,
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Transfer-Encoding' => '8bit',
        ];

        $headerString = '';
        foreach ($headers as $key => $value) {
            $headerString .= "{$key}: {$value}\r\n";
        }

        $to = '=?UTF-8?B?' . base64_encode($toName) . "?= <{$toEmail}>";

        return @mail($to, $encodedSubject, $bodyText, $headerString);
    }

    private static function currentHost(): string
    {
        return $_SERVER['HTTP_HOST'] ?? 'localhost';
    }
}
