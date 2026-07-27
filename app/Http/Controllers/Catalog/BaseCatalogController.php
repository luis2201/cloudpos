<?php

namespace App\Http\Controllers\Catalog;

use App\Domain\Catalog\Models\Brand;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\MeasurementUnit;
use App\Domain\Catalog\Models\PaymentMethod;
use App\Domain\Catalog\Models\Presentation;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BaseCatalogController extends Controller
{
    private const SECTIONS = [
        'categories' => ['label' => 'Categorías', 'singular' => 'categoría', 'icon' => 'category', 'sorts' => ['code', 'name', 'is_active']],
        'brands' => ['label' => 'Marcas', 'singular' => 'marca', 'icon' => 'tag', 'sorts' => ['code', 'name', 'is_active']],
        'units' => ['label' => 'Unidades', 'singular' => 'unidad', 'icon' => 'ruler', 'sorts' => ['code', 'name', 'dimension', 'is_active']],
        'presentations' => ['label' => 'Presentaciones', 'singular' => 'presentación', 'icon' => 'package', 'sorts' => ['code', 'name', 'quantity', 'is_active']],
        'payment-methods' => ['label' => 'Formas de pago', 'singular' => 'forma de pago', 'icon' => 'card', 'sorts' => ['code', 'name', 'sri_code', 'is_active']],
    ];

    public function index(Request $request): View
    {
        $section = array_key_exists($request->string('section')->toString(), self::SECTIONS)
            ? $request->string('section')->toString()
            : 'categories';
        $search = trim($request->string('search')->toString());
        $status = in_array($request->string('status')->toString(), ['active', 'inactive'], true)
            ? $request->string('status')->toString()
            : '';
        $sort = in_array($request->string('sort')->toString(), self::SECTIONS[$section]['sorts'], true)
            ? $request->string('sort')->toString()
            : 'name';
        $direction = $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc';

        $query = $this->queryFor($section);
        $this->applySearch($query, $section, $search);
        $query->when($status !== '', fn (Builder $query) => $query->where('is_active', $status === 'active'));

        return view('catalogs.index', [
            'counts' => [
                'categories' => Category::query()->count(),
                'brands' => Brand::query()->count(),
                'units' => MeasurementUnit::query()->count(),
                'presentations' => Presentation::query()->count(),
                'payment-methods' => PaymentMethod::query()->count(),
            ],
            'filters' => compact('search', 'status', 'sort', 'direction'),
            'measurementDimensions' => MeasurementUnit::DIMENSIONS,
            'parents' => Category::query()->where('is_active', true)->orderBy('name')->get(),
            'records' => $query->orderBy($sort, $direction)->paginate(10)->withQueryString(),
            'section' => $section,
            'sections' => self::SECTIONS,
            'sriPaymentCodes' => PaymentMethod::SRI_CODES,
            'units' => MeasurementUnit::query()->where('is_active', true)->orderBy('dimension')->orderBy('name')->get(),
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $validated = $this->validateCategory($request);
        Category::query()->create([
            ...$validated,
            'code' => Str::upper($validated['code']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->saved('categories', 'Categoría creada correctamente.');
    }

    public function updateCategory(Request $request, Category $category): RedirectResponse
    {
        $validated = $this->validateCategory($request, $category);
        $this->ensureValidParent($category, $validated['parent_id'] ?? null);

        if (! $request->boolean('is_active') && $category->children()->where('is_active', true)->exists()) {
            throw ValidationException::withMessages([
                'is_active' => 'No se puede desactivar una categoría que tiene subcategorías activas.',
            ]);
        }

        if (! $request->boolean('is_active') && $category->products()->where('is_active', true)->exists()) {
            throw ValidationException::withMessages([
                'is_active' => 'No se puede desactivar una categoría utilizada por productos activos.',
            ]);
        }

        $category->update([
            ...$validated,
            'code' => Str::upper($validated['code']),
            'is_active' => $request->boolean('is_active'),
        ]);

        return $this->saved('categories', 'Categoría actualizada correctamente.');
    }

    public function storeBrand(Request $request): RedirectResponse
    {
        $validated = $this->validateBrand($request);
        Brand::query()->create([
            ...$validated,
            'code' => Str::upper($validated['code']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->saved('brands', 'Marca creada correctamente.');
    }

    public function updateBrand(Request $request, Brand $brand): RedirectResponse
    {
        $validated = $this->validateBrand($request, $brand);

        if (! $request->boolean('is_active') && $brand->products()->where('is_active', true)->exists()) {
            throw ValidationException::withMessages([
                'is_active' => 'No se puede desactivar una marca utilizada por productos activos.',
            ]);
        }

        $brand->update([
            ...$validated,
            'code' => Str::upper($validated['code']),
            'is_active' => $request->boolean('is_active'),
        ]);

        return $this->saved('brands', 'Marca actualizada correctamente.');
    }

    public function storeUnit(Request $request): RedirectResponse
    {
        $validated = $this->validateUnit($request);
        MeasurementUnit::query()->create([
            ...$validated,
            'code' => Str::upper($validated['code']),
            'allows_decimals' => $request->boolean('allows_decimals'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->saved('units', 'Unidad de medida creada correctamente.');
    }

    public function updateUnit(Request $request, MeasurementUnit $unit): RedirectResponse
    {
        $validated = $this->validateUnit($request, $unit);

        if (! $request->boolean('is_active') && $unit->presentations()->where('is_active', true)->exists()) {
            throw ValidationException::withMessages([
                'is_active' => 'No se puede desactivar una unidad utilizada por presentaciones activas.',
            ]);
        }

        $unit->update([
            ...$validated,
            'code' => Str::upper($validated['code']),
            'allows_decimals' => $request->boolean('allows_decimals'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return $this->saved('units', 'Unidad de medida actualizada correctamente.');
    }

    public function storePresentation(Request $request): RedirectResponse
    {
        $validated = $this->validatePresentation($request);
        Presentation::query()->create([
            ...$validated,
            'code' => Str::upper($validated['code']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->saved('presentations', 'Presentación creada correctamente.');
    }

    public function updatePresentation(Request $request, Presentation $presentation): RedirectResponse
    {
        $validated = $this->validatePresentation($request, $presentation);

        $contentChanged = (int) $validated['measurement_unit_id'] !== $presentation->measurement_unit_id
            || bccomp((string) $validated['quantity'], (string) $presentation->quantity, 4) !== 0;

        if ($contentChanged && $presentation->productPackages()->exists()) {
            throw ValidationException::withMessages([
                'quantity' => 'No se puede cambiar el contenido de una presentación que ya está asociada a productos.',
            ]);
        }

        if (! $request->boolean('is_active') && $presentation->productPackages()->where('is_active', true)->exists()) {
            throw ValidationException::withMessages([
                'is_active' => 'No se puede desactivar una presentación utilizada por productos activos.',
            ]);
        }

        $presentation->update([
            ...$validated,
            'code' => Str::upper($validated['code']),
            'is_active' => $request->boolean('is_active'),
        ]);

        return $this->saved('presentations', 'Presentación actualizada correctamente.');
    }

    public function storePaymentMethod(Request $request): RedirectResponse
    {
        $validated = $this->validatePaymentMethod($request);
        PaymentMethod::query()->create([
            ...$validated,
            'code' => Str::upper($validated['code']),
            'requires_reference' => $request->boolean('requires_reference'),
            'affects_cash' => $request->boolean('affects_cash'),
            'is_system' => false,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->saved('payment-methods', 'Forma de pago creada correctamente.');
    }

    public function updatePaymentMethod(Request $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $validated = $this->validatePaymentMethod($request, $paymentMethod);
        $paymentMethod->update([
            ...$validated,
            ...($paymentMethod->is_system ? [
                'code' => $paymentMethod->code,
                'sri_code' => $paymentMethod->sri_code,
                'name' => $paymentMethod->name,
            ] : ['code' => Str::upper($validated['code'])]),
            'requires_reference' => $request->boolean('requires_reference'),
            'affects_cash' => $request->boolean('affects_cash'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return $this->saved('payment-methods', 'Forma de pago actualizada correctamente.');
    }

    private function queryFor(string $section): Builder
    {
        return match ($section) {
            'brands' => Brand::query(),
            'units' => MeasurementUnit::query()->withCount('presentations'),
            'presentations' => Presentation::query()->with('measurementUnit')->withCount('productPackages'),
            'payment-methods' => PaymentMethod::query(),
            default => Category::query()->with('parent')->withCount('children'),
        };
    }

    private function applySearch(Builder $query, string $section, string $search): void
    {
        if ($search === '') {
            return;
        }

        $columns = match ($section) {
            'units' => ['code', 'name', 'symbol'],
            'payment-methods' => ['code', 'name', 'sri_code'],
            default => ['code', 'name', 'description'],
        };

        $query->where(function (Builder $query) use ($columns, $search): void {
            foreach ($columns as $index => $column) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $query->{$method}($column, 'like', "%{$search}%");
            }
        });
    }

    /** @return array<string, mixed> */
    private function validateCategory(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'parent_id' => ['nullable', Rule::exists('catalog_categories', 'id')->where('is_active', true)],
            'code' => ['required', 'alpha_dash:ascii', 'max:30', Rule::unique('catalog_categories', 'code')->ignore($category)],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
    }

    /** @return array<string, mixed> */
    private function validateBrand(Request $request, ?Brand $brand = null): array
    {
        return $request->validate([
            'code' => ['required', 'alpha_dash:ascii', 'max:30', Rule::unique('brands', 'code')->ignore($brand)],
            'name' => ['required', 'string', 'max:120', Rule::unique('brands', 'name')->ignore($brand)],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
    }

    /** @return array<string, mixed> */
    private function validateUnit(Request $request, ?MeasurementUnit $unit = null): array
    {
        return $request->validate([
            'code' => ['required', 'alpha_dash:ascii', 'max:12', Rule::unique('measurement_units', 'code')->ignore($unit)],
            'name' => ['required', 'string', 'max:80', Rule::unique('measurement_units', 'name')->ignore($unit)],
            'symbol' => ['required', 'string', 'max:12'],
            'dimension' => ['required', Rule::in(array_keys(MeasurementUnit::DIMENSIONS))],
        ]);
    }

    /** @return array<string, mixed> */
    private function validatePresentation(Request $request, ?Presentation $presentation = null): array
    {
        $validated = $request->validate([
            'measurement_unit_id' => ['required', Rule::exists('measurement_units', 'id')->where('is_active', true)],
            'code' => ['required', 'alpha_dash:ascii', 'max:30', Rule::unique('presentations', 'code')->ignore($presentation)],
            'name' => ['required', 'string', 'max:120'],
            'quantity' => ['required', 'numeric', 'min:0.0001', 'decimal:0,4'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $unit = MeasurementUnit::query()->findOrFail($validated['measurement_unit_id']);
        $quantity = (float) $validated['quantity'];

        if ($unit->dimension === 'quantity' && floor($quantity) !== $quantity) {
            throw ValidationException::withMessages([
                'quantity' => 'Una presentación por unidades debe contener una cantidad entera.',
            ]);
        }

        return $validated;
    }

    /** @return array<string, mixed> */
    private function validatePaymentMethod(Request $request, ?PaymentMethod $paymentMethod = null): array
    {
        return $request->validate([
            'code' => ['required', 'alpha_dash:ascii', 'max:30', Rule::unique('payment_methods', 'code')->ignore($paymentMethod)],
            'sri_code' => ['required', Rule::in(array_keys(PaymentMethod::SRI_CODES))],
            'name' => ['required', 'string', 'max:150'],
        ]);
    }

    private function ensureValidParent(Category $category, mixed $parentId): void
    {
        $parent = filled($parentId) ? Category::query()->find($parentId) : null;

        while ($parent) {
            if ($parent->is($category)) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Una categoría no puede depender de sí misma ni de una subcategoría.',
                ]);
            }

            $parent = $parent->parent;
        }
    }

    private function saved(string $section, string $message): RedirectResponse
    {
        return to_route('catalogs.index', ['section' => $section])->with('success', $message);
    }
}
