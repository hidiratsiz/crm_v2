<?php

namespace App\Core;

/**
 * Generates URLs that work whether JobPro is installed at the domain root
 * (https://example.com/) or in a subdirectory (https://example.com/jobpro/).
 *
 * BASE_URL is auto-detected in public/index.php from the request itself,
 * so no manual configuration is needed even for subdirectory installs.
 */
class Url
{
    public static function to(string $path): string
    {
        $base = defined('BASE_URL') ? BASE_URL : '';
        $path = '/' . ltrim($path, '/');
        return $base . $path;
    }
}
