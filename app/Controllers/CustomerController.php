<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Customer;
use App\Models\Project;

class CustomerController extends Controller
{
    public function index(): void
    {
        if (!Auth::can('customers.view')) {
            $this->forbidden();
            return;
        }

        $search = $this->input('q');
        $customers = Customer::all($search);

        echo View::renderWithLayout('customers/index', [
            'customers' => $customers,
            'search' => $search,
        ]);
    }

    public function showCreate(): void
    {
        if (!Auth::can('customers.create')) {
            $this->forbidden();
            return;
        }
        echo View::renderWithLayout('customers/create', ['error' => null]);
    }

    public function store(): void
    {
        if (!Auth::can('customers.create')) {
            $this->forbidden();
            return;
        }
        if (!Csrf::verify($this->input('csrf_token'))) {
            echo View::renderWithLayout('customers/create', ['error' => 'Oturum suresi doldu, tekrar deneyin.']);
            return;
        }

        $name = trim((string) $this->input('name'));
        if ($name === '') {
            echo View::renderWithLayout('customers/create', ['error' => 'Musteri adi zorunludur.']);
            return;
        }

        Customer::create([
            'name' => $name,
            'phone' => $this->input('phone'),
            'email' => $this->input('email'),
            'address' => $this->input('address'),
            'notes' => $this->input('notes'),
        ], Auth::id());

        $this->redirect('/customers');
    }

    public function showEdit(): void
    {
        if (!Auth::can('customers.edit')) {
            $this->forbidden();
            return;
        }

        $id = (int) $this->input('id');
        $customer = Customer::find($id);

        if (!$customer) {
            http_response_code(404);
            echo View::renderWithLayout('errors/404');
            return;
        }

        $projects = Project::allForCustomer($id);

        echo View::renderWithLayout('customers/edit', ['customer' => $customer, 'projects' => $projects, 'error' => null]);
    }

    public function update(): void
    {
        if (!Auth::can('customers.edit')) {
            $this->forbidden();
            return;
        }
        if (!Csrf::verify($this->input('csrf_token'))) {
            $this->redirect('/customers');
            return;
        }

        $id = (int) $this->input('id');
        $name = trim((string) $this->input('name'));

        if ($name === '') {
            $customer = Customer::find($id);
            $projects = Project::allForCustomer($id);
            echo View::renderWithLayout('customers/edit', ['customer' => $customer, 'projects' => $projects, 'error' => 'Musteri adi zorunludur.']);
            return;
        }

        Customer::update($id, [
            'name' => $name,
            'phone' => $this->input('phone'),
            'email' => $this->input('email'),
            'address' => $this->input('address'),
            'notes' => $this->input('notes'),
        ]);

        $this->redirect('/customers');
    }

    public function delete(): void
    {
        if (!Auth::can('customers.delete')) {
            $this->forbidden();
            return;
        }
        if (!Csrf::verify($this->input('csrf_token'))) {
            $this->redirect('/customers');
            return;
        }

        Customer::softDelete((int) $this->input('id'));
        $this->redirect('/customers');
    }

    private function forbidden(): void
    {
        http_response_code(403);
        echo View::renderWithLayout('errors/403');
    }
}
