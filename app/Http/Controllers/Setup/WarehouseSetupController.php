<?php

namespace App\Http\Controllers\Setup;

use App\Domain\Organization\Models\Company;
use App\Domain\Organization\Models\Establishment;
use App\Domain\Organization\Models\Warehouse;
use App\Domain\Setup\OnboardingManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WarehouseSetupController extends Controller
{
    public function create(OnboardingManager $onboarding): View|RedirectResponse
    {
        if (! Establishment::query()->exists() || Warehouse::query()->exists()) {
            return to_route($onboarding->nextRouteName());
        }

        return view('setup.warehouse', [
            'company' => Company::query()->firstOrFail(),
            'establishment' => Establishment::query()->where('is_main', true)->firstOrFail(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $company = Company::query()->firstOrFail();
        $establishment = Establishment::query()->where('is_main', true)->firstOrFail();
        abort_if(Warehouse::query()->exists(), 409, 'La bodega principal ya fue configurada.');

        $validated = $request->validate([
            'code' => ['required', 'alpha_dash:ascii', 'max:20'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);

        $company->warehouses()->create([
            ...$validated,
            'code' => Str::upper($validated['code']),
            'establishment_id' => $establishment->getKey(),
            'is_main' => true,
            'is_active' => true,
            'allow_negative_stock' => false,
        ]);

        return to_route('dashboard')
            ->with('success', 'Configuración principal completada. CloudPOS está listo para continuar.');
    }
}
