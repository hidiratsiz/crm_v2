<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Url;

class AuthMiddleware
{
    public function handle(): bool
    {
        if (!Auth::check()) {
            header('Location: ' . Url::to('/login'));
            exit;
        }
        return true;
    }
}
