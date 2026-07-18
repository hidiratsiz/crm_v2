<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }
        $this->view('auth/login', ['error' => null]);
    }

    public function login(): void
    {
        if (!Csrf::verify($this->input('csrf_token'))) {
            $this->view('auth/login', ['error' => 'Oturum suresi doldu, lutfen tekrar deneyin.']);
            return;
        }

        $email = trim((string) $this->input('email'));
        $password = (string) $this->input('password');

        if ($email === '' || $password === '') {
            $this->view('auth/login', ['error' => 'E-posta ve sifre gereklidir.']);
            return;
        }

        // Basic throttling against brute force (per-session attempt counter)
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        if ($_SESSION['login_attempts'] > 10) {
            $this->view('auth/login', ['error' => 'Cok fazla deneme yapildi. Lutfen birkac dakika sonra tekrar deneyin.']);
            return;
        }

        if (Auth::attempt($email, $password)) {
            unset($_SESSION['login_attempts']);
            $this->redirect('/dashboard');
            return;
        }

        $this->view('auth/login', ['error' => 'E-posta veya sifre hatali.']);
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/login');
    }
}
