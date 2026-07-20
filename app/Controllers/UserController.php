<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\View;
use App\Models\User;

class UserController extends Controller
{
    public function index(): void
    {
        if (!Auth::can('users.manage')) {
            $this->forbidden();
            return;
        }

        echo View::renderWithLayout('users/index', ['users' => User::all()]);
    }

    public function showCreate(): void
    {
        if (!Auth::can('users.manage')) {
            $this->forbidden();
            return;
        }

        echo View::renderWithLayout('users/create', ['roles' => User::allRoles(), 'error' => null]);
    }

    public function store(): void
    {
        if (!Auth::can('users.manage')) {
            $this->forbidden();
            return;
        }

        if (!Csrf::verify($this->input('csrf_token'))) {
            echo View::renderWithLayout('users/create', ['roles' => User::allRoles(), 'error' => 'Oturum suresi doldu, tekrar deneyin.']);
            return;
        }

        $name = trim((string) $this->input('name'));
        $email = trim((string) $this->input('email'));
        $password = (string) $this->input('password');
        $roleId = (int) $this->input('role_id');

        if ($name === '' || $email === '' || $password === '') {
            echo View::renderWithLayout('users/create', ['roles' => User::allRoles(), 'error' => 'Ad, e-posta ve sifre zorunludur.']);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo View::renderWithLayout('users/create', ['roles' => User::allRoles(), 'error' => 'Gecerli bir e-posta adresi girin.']);
            return;
        }

        if (strlen($password) < 8) {
            echo View::renderWithLayout('users/create', ['roles' => User::allRoles(), 'error' => 'Sifre en az 8 karakter olmalidir.']);
            return;
        }

        if (User::findByEmail($email)) {
            echo View::renderWithLayout('users/create', ['roles' => User::allRoles(), 'error' => 'Bu e-posta adresi zaten kayitli.']);
            return;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role_id' => $roleId,
        ]);

        $this->redirect('/users');
    }

    private function forbidden(): void
    {
        http_response_code(403);
        echo View::renderWithLayout('errors/403');
    }
}
