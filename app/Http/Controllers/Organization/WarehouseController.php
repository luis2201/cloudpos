<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Organization\Models\Company;
use App\Domain\Organization\Models\Warehouse;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function index(): View
    {
        $company = Company::query()->firstOrFail();

        return view('organization.warehouses.index', [
            'company' => $company,
            'establishments' => $company->establishments()->where('is_active', true)->orderBy('code')->get(),
            'warehouses' => $company->warehouses()->with('establishment')->orderByDesc('is_main')->orderBy('code')->paginate(10),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $company = Company::query()->firstOrFail();
        $validated = $this->validateWarehouse($request, $company);

        DB::transaction(function () use ($company, $request, $validated): void {
            if ($request->boolean('is_main')) {
                $company->warehouses()->update(['is_main' => false]);
            }

            $company->warehouses()->create([
                ...$validated,
                'code' => Str::upper($validated['code']),
                'is_main' => $request->boolean('is_main'),
                'is_active' => $request->boolean('is_active', true),
                'allow_negative_stock' => $request->boolean('allow_negative_stock'),
            ]);
        });

        return back()->with('success', 'Bodega registrada correctamente.');
    }

    public function update(Request $request, Warehouse $warehouse): RedirectResponse
    {
        $company = Company::query()->firstOrFail();
        abort_unless($warehouse->company_id === $company->getKey(), 404);
        $validated = $this->validateWarehouse($request, $company, $warehouse);

        if (! $request->boolean('is_active') && $warehouse->inventoryStocks()->where('quantity', '!=', 0)->exists()) {
            throw ValidationException::withMessages([
                'is_active' => 'No se puede desactivar una bodega que conserva existencias.',
            ]);
        }

        if (! $request->boolean('allow_negative_stock') && $warehouse->inventoryStocks()->where('quantity', '<', 0)->exists()) {
            throw ValidationException::withMessages([
                'allow_negative_stock' => 'Regulariza las existencias negativas antes de bloquearlas.',
            ]);
        }

        DB::transaction(function () use ($company, $warehouse, $request, $validated): void {
            if ($request->boolean('is_main')) {
                $company->warehouses()->whereKeyNot($warehouse->getKey())->update(['is_main' => false]);
            }

            $warehouse->update([
                ...$validated,
                'code' => Str::upper($validated['code']),
                'is_main' => $request->boolean('is_main'),
                'is_active' => $request->boolean('is_active'),
                'allow_negative_stock' => $request->boolean('allow_negative_stock'),
            ]);
        });

        return back()->with('success', 'Bodega actualizada correctamente.');
    }

    /** @return array<string, mixed> */
    private function validateWarehouse(Request $request, Company $company, ?Warehouse $warehouse = null): array
    {
        return $request->validate([
            'establishment_id' => [
                'required',
                Rule::exists('establishments', 'id')->where(fn ($query) => $query->where('company_id', $company->getKey())),
            ],
            'code' => [
                'required',
                'alpha_dash:ascii',
                'max:20',
                Rule::unique('warehouses', 'code')
                    ->where(fn ($query) => $query->where('company_id', $company->getKey()))
                    ->ignore($warehouse),
            ],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
