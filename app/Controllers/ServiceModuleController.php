<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\View;
use App\Models\ServiceModule;
use App\Models\ServiceModuleField;
use App\Services\PricingEngine;

class ServiceModuleController extends Controller
{
    private const FIELD_TYPES = ['number', 'checkbox', 'dropdown', 'text'];
    private const PRICING_METHODS = ['per_unit', 'tiered', 'fixed', 'dropdown_priced', 'none'];

    public function index(): void
    {
        if (!Auth::can('service_modules.manage')) {
            $this->forbidden();
            return;
        }

        echo View::renderWithLayout('service-modules/index', ['modules' => ServiceModule::all()]);
    }

    public function showCreate(): void
    {
        if (!Auth::can('service_modules.manage')) {
            $this->forbidden();
            return;
        }

        echo View::renderWithLayout('service-modules/create', ['error' => null]);
    }

    public function store(): void
    {
        if (!Auth::can('service_modules.manage')) {
            $this->forbidden();
            return;
        }

        if (!Csrf::verify($this->input('csrf_token'))) {
            echo View::renderWithLayout('service-modules/create', ['error' => 'Oturum suresi doldu, tekrar deneyin.']);
            return;
        }

        $name = trim((string) $this->input('name'));
        if ($name === '') {
            echo View::renderWithLayout('service-modules/create', ['error' => 'Servis adi zorunludur.']);
            return;
        }

        $id = ServiceModule::create($name, $this->input('description'));
        $this->redirect('/service-modules/edit?id=' . $id);
    }

    public function showEdit(): void
    {
        if (!Auth::can('service_modules.manage')) {
            $this->forbidden();
            return;
        }

        $module = $this->loadModuleOr404();
        if (!$module) {
            return;
        }

        echo View::renderWithLayout('service-modules/edit', [
            'module' => $module,
            'fields' => ServiceModuleField::allForModule($module['id']),
            'error' => null,
        ]);
    }

    public function update(): void
    {
        if (!Auth::can('service_modules.manage')) {
            $this->forbidden();
            return;
        }

        $module = $this->loadModuleOr404();
        if (!$module) {
            return;
        }

        if (!Csrf::verify($this->input('csrf_token'))) {
            $this->redirect('/service-modules/edit?id=' . $module['id']);
            return;
        }

        $name = trim((string) $this->input('name'));
        if ($name !== '') {
            ServiceModule::update((int) $module['id'], $name, $this->input('description'));
        }

        $this->redirect('/service-modules/edit?id=' . $module['id']);
    }

    public function toggleActive(): void
    {
        if (!Auth::can('service_modules.manage')) {
            $this->forbidden();
            return;
        }

        $module = $this->loadModuleOr404();
        if (!$module) {
            return;
        }

        if (!Csrf::verify($this->input('csrf_token'))) {
            $this->redirect('/service-modules');
            return;
        }

        ServiceModule::setActive((int) $module['id'], !$module['is_active']);
        $this->redirect('/service-modules');
    }

    public function delete(): void
    {
        if (!Auth::can('service_modules.manage')) {
            $this->forbidden();
            return;
        }

        $module = $this->loadModuleOr404();
        if (!$module) {
            return;
        }

        if (!Csrf::verify($this->input('csrf_token'))) {
            $this->redirect('/service-modules');
            return;
        }

        ServiceModule::softDelete((int) $module['id']);
        $this->redirect('/service-modules');
    }

    // ---- Fields ----

    public function addField(): void
    {
        if (!Auth::can('service_modules.manage')) {
            $this->forbidden();
            return;
        }

        $module = ServiceModule::find((int) $this->input('service_module_id'));
        if (!$module) {
            http_response_code(404);
            echo View::renderWithLayout('errors/404');
            return;
        }

        if (!Csrf::verify($this->input('csrf_token'))) {
            $this->redirect('/service-modules/edit?id=' . $module['id']);
            return;
        }

        $label = trim((string) $this->input('label'));
        $fieldType = (string) $this->input('field_type');
        $pricingMethod = (string) $this->input('pricing_method');

        if ($label === '' || !in_array($fieldType, self::FIELD_TYPES, true) || !in_array($pricingMethod, self::PRICING_METHODS, true)) {
            $this->redirect('/service-modules/edit?id=' . $module['id']);
            return;
        }

        $fieldKey = $this->slugifyKey($label);

        ServiceModuleField::create([
            'service_module_id' => $module['id'],
            'field_key' => $fieldKey,
            'label' => $label,
            'field_type' => $fieldType,
            'pricing_method' => $pricingMethod,
            'unit_price' => $this->parseDecimal($this->input('unit_price')),
            'fixed_price' => $this->parseDecimal($this->input('fixed_price')),
            'tiers_json' => $this->parseTiersInput(),
            'options_json' => $this->parseOptionsInput(),
            'is_required' => $this->input('is_required') === '1',
        ]);

        $this->redirect('/service-modules/edit?id=' . $module['id']);
    }

    public function deleteField(): void
    {
        if (!Auth::can('service_modules.manage')) {
            $this->forbidden();
            return;
        }

        $field = ServiceModuleField::find((int) $this->input('id'));
        if (!$field) {
            http_response_code(404);
            echo View::renderWithLayout('errors/404');
            return;
        }

        if (!Csrf::verify($this->input('csrf_token'))) {
            $this->redirect('/service-modules/edit?id=' . $field['service_module_id']);
            return;
        }

        ServiceModuleField::delete((int) $field['id']);
        $this->redirect('/service-modules/edit?id=' . $field['service_module_id']);
    }

    /**
     * AJAX endpoint: returns a module's field definitions as JSON so the
     * estimate form can render the right inputs when a service is selected.
     */
    public function fieldsJson(): void
    {
        if (!Auth::can('customers.create')) {
            $this->json(['error' => 'forbidden'], 403);
            return;
        }

        $moduleId = (int) $this->input('service_module_id');
        $fields = ServiceModuleField::allForModule($moduleId);

        $output = array_map(function ($field) {
            return [
                'id' => (int) $field['id'],
                'label' => $field['label'],
                'field_type' => $field['field_type'],
                'pricing_method' => $field['pricing_method'],
                'is_required' => (bool) $field['is_required'],
                'options' => !empty($field['options_json']) ? json_decode($field['options_json'], true) : [],
            ];
        }, $fields);

        $this->json(['fields' => $output]);
    }

    /**
     * AJAX endpoint: given a service module and the currently-entered field
     * values, returns the live-calculated per-field price + total as JSON.
     * Used by the estimate form while the user is filling it out. The FINAL
     * price on save is always recomputed server-side too — this endpoint is
     * for preview only, never trusted as the source of truth on its own.
     */
    public function calculate(): void
    {
        if (!Auth::can('customers.create')) {
            $this->json(['error' => 'forbidden'], 403);
            return;
        }

        $moduleId = (int) $this->input('service_module_id');
        $fields = ServiceModuleField::allForModule($moduleId);

        $values = [];
        foreach ($fields as $field) {
            $paramName = 'field_' . $field['id'];
            $values[$field['field_key']] = $this->input($paramName);
        }

        $result = PricingEngine::calculate($fields, $values);
        $this->json($result);
    }

    // ---- Helpers ----

    private function loadModuleOr404(): ?array
    {
        $id = (int) $this->input('id');
        $module = ServiceModule::find($id);

        if (!$module) {
            http_response_code(404);
            echo View::renderWithLayout('errors/404');
            return null;
        }

        return $module;
    }

    private function slugifyKey(string $label): string
    {
        $key = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $label), '_'));
        return $key !== '' ? $key : 'alan_' . substr(md5($label . microtime()), 0, 6);
    }

    private function parseDecimal(?string $value): ?float
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        $cleaned = preg_replace('/[^0-9.\-]/', '', $value);
        return $cleaned === '' ? null : (float) $cleaned;
    }

    /**
     * Reads tier_min[], tier_max[], tier_price[] arrays from the fixed-row
     * tier builder in the create-field form and encodes non-empty rows as
     * JSON. Rows where price is blank are skipped.
     */
    private function parseTiersInput(): ?string
    {
        $mins = $_POST['tier_min'] ?? [];
        $maxs = $_POST['tier_max'] ?? [];
        $prices = $_POST['tier_price'] ?? [];

        $tiers = [];
        foreach ($prices as $i => $price) {
            if (trim((string) $price) === '') {
                continue;
            }
            $tiers[] = [
                'min' => $this->parseDecimal($mins[$i] ?? '') ?? 0,
                'max' => $this->parseDecimal($maxs[$i] ?? ''),
                'price' => $this->parseDecimal($price),
            ];
        }

        if (empty($tiers)) {
            return null;
        }

        // Sort by min ascending so PricingEngine's first-match-wins logic is predictable
        usort($tiers, fn($a, $b) => $a['min'] <=> $b['min']);

        return json_encode($tiers, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Reads option_label[], option_value[], option_price[] arrays from the
     * fixed-row dropdown-option builder and encodes non-empty rows as JSON.
     */
    private function parseOptionsInput(): ?string
    {
        $labels = $_POST['option_label'] ?? [];
        $prices = $_POST['option_price'] ?? [];

        $options = [];
        foreach ($labels as $i => $label) {
            if (trim((string) $label) === '') {
                continue;
            }
            $value = $this->slugifyKey($label);
            $options[] = [
                'label' => $label,
                'value' => $value,
                'price' => $this->parseDecimal($prices[$i] ?? '') ?? 0,
            ];
        }

        if (empty($options)) {
            return null;
        }

        return json_encode($options, JSON_UNESCAPED_UNICODE);
    }

    private function forbidden(): void
    {
        http_response_code(403);
        echo View::renderWithLayout('errors/403');
    }
}
