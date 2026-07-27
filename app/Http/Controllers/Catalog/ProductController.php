<?php

namespace App\Http\Controllers\Catalog;

use App\Domain\Catalog\Models\Brand;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Presentation;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductPackage;
use App\Domain\Catalog\Services\InventoryUnitConverter;
use App\Domain\Catalog\Services\ProductBarcodeService;
use App\Domain\Organization\Models\Company;
use App\Domain\Tax\Services\CompanyTaxPolicy;
use App\Domain\Tax\Services\TaxRateResolver;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request, TaxRateResolver $taxRates, CompanyTaxPolicy $taxPolicy): View
    {
        $company = Company::query()->firstOrFail();
        $search = trim($request->string('search')->toString());
        $status = in_array($request->string('status')->toString(), ['active', 'inactive'], true)
            ? $request->string('status')->toString()
            : '';
        $categoryId = $request->integer('category_id') ?: null;
        $tax = in_array($request->string('tax')->toString(), ['vat', 'ice', 'none'], true)
            ? $request->string('tax')->toString()
            : '';
        $sort = in_array($request->string('sort')->toString(), ['code', 'name', 'created_at'], true)
            ? $request->string('sort')->toString()
            : 'name';
        $direction = $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc';

        $products = Product::query()
            ->with(['category.parent', 'brand', 'basePackage.presentation'])
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhereHas('packages', fn ($query) => $query
                    ->where('barcode', 'like', "%{$search}%")
                    ->orWhere('internal_barcode', 'like', "%{$search}%"))))
            ->when($status !== '', fn ($query) => $query->where('is_active', $status === 'active'))
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->when($tax === 'vat', fn ($query) => $query->where('vat_treatment', 'general'))
            ->when($tax === 'ice', fn ($query) => $query->where('ice_treatment', '!=', 'none'))
            ->when($tax === 'none', fn ($query) => $query->where('vat_treatment', '!=', 'general')->where('ice_treatment', 'none'))
            ->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();

        return view('products.index', [
            'brands' => $this->activeBrands(),
            'categories' => $this->activeCategories(),
            'company' => $company,
            'currentTaxRate' => $taxRates->ivaForDate(),
            'filters' => compact('search', 'status', 'categoryId', 'tax', 'sort', 'direction'),
            'iceTreatments' => Product::ICE_TREATMENTS,
            'presentations' => $this->activePresentations(),
            'productKinds' => Product::KINDS,
            'products' => $products,
            'shouldCalculateProductIce' => $taxPolicy->shouldCalculateProductIce($company),
            'shouldBreakDownVat' => $taxPolicy->shouldBreakDownVat($company),
            'vatTreatments' => Product::VAT_TREATMENTS,
        ]);
    }

    public function store(Request $request, InventoryUnitConverter $converter, ProductBarcodeService $barcodes): RedirectResponse
    {
        $this->normalizeAndValidateBarcode($request, $barcodes);
        $productData = $this->validateProduct($request);
        $packageData = $request->validate([
            'presentation_id' => ['required', Rule::exists('presentations', 'id')->where('is_active', true)],
            'barcode' => ['nullable', 'string', 'max:80', 'unique:product_packages,barcode'],
            'price_before_tax' => ['required', 'numeric', 'min:0', 'decimal:0,4'],
        ]);
        $presentation = Presentation::query()->with('measurementUnit')->findOrFail($packageData['presentation_id']);

        $product = DB::transaction(function () use ($request, $productData, $packageData, $presentation, $converter, $barcodes): Product {
            $product = Product::query()->create([
                ...$productData,
                'code' => Str::upper($productData['code']),
                'alcohol_by_volume' => $productData['product_kind'] === 'alcoholic_beverage'
                    ? $productData['alcohol_by_volume']
                    : null,
                'ice_code' => $productData['ice_treatment'] !== 'none' ? ($productData['ice_code'] ?? null) : null,
                'is_active' => $request->boolean('is_active', true),
            ]);

            $package = $product->packages()->create([
                ...$packageData,
                'units_per_package' => $converter->unitsPerPackage($presentation),
                'is_base' => true,
                'is_active' => true,
            ]);
            $barcodes->ensureInternal($package);

            return $product;
        });

        return to_route('products.show', $product)
            ->with('success', 'Producto creado. Puedes agregar cajas u otras presentaciones de venta.');
    }

    public function show(Product $product, TaxRateResolver $taxRates, CompanyTaxPolicy $taxPolicy): View
    {
        $company = Company::query()->firstOrFail();
        $product->load(['category.parent', 'brand', 'packages.presentation.measurementUnit']);

        return view('products.show', [
            'availablePresentations' => $this->activePresentations(),
            'brands' => $this->activeBrands(),
            'categories' => $this->activeCategories(),
            'company' => $company,
            'currentTaxRate' => $taxRates->ivaForDate(),
            'iceTreatments' => Product::ICE_TREATMENTS,
            'product' => $product,
            'productKinds' => Product::KINDS,
            'shouldCalculateProductIce' => $taxPolicy->shouldCalculateProductIce($company),
            'shouldBreakDownVat' => $taxPolicy->shouldBreakDownVat($company),
            'vatTreatments' => Product::VAT_TREATMENTS,
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $this->validateProduct($request, $product);
        $product->update([
            ...$validated,
            'code' => Str::upper($validated['code']),
            'alcohol_by_volume' => $validated['product_kind'] === 'alcoholic_beverage'
                ? $validated['alcohol_by_volume']
                : null,
            'ice_code' => $validated['ice_treatment'] !== 'none' ? ($validated['ice_code'] ?? null) : null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Producto actualizado correctamente.');
    }

    public function storePackage(Request $request, Product $product, InventoryUnitConverter $converter, ProductBarcodeService $barcodes): RedirectResponse
    {
        $this->normalizeAndValidateBarcode($request, $barcodes);
        $validated = $this->validatePackage($request, $product);
        $presentation = Presentation::query()->with('measurementUnit')->findOrFail($validated['presentation_id']);

        $package = $product->packages()->create([
            ...$validated,
            'units_per_package' => $converter->unitsPerPackage($presentation),
            'is_base' => false,
            'is_active' => $request->boolean('is_active', true),
        ]);
        $barcodes->ensureInternal($package);

        return back()->with('success', 'Presentación agregada al producto.');
    }

    public function updatePackage(Request $request, Product $product, ProductPackage $package, ProductBarcodeService $barcodes): RedirectResponse
    {
        abort_unless($package->product_id === $product->getKey(), 404);
        $this->normalizeAndValidateBarcode($request, $barcodes);
        $validated = $request->validate([
            'barcode' => ['nullable', 'string', 'max:80', Rule::unique('product_packages', 'barcode')->ignore($package)],
            'price_before_tax' => ['required', 'numeric', 'min:0', 'decimal:0,4'],
        ]);

        if ($package->is_base && ! $request->boolean('is_active')) {
            throw ValidationException::withMessages([
                'is_active' => 'La presentación base del producto debe permanecer activa.',
            ]);
        }

        $package->update([
            ...$validated,
            'is_active' => $package->is_base ? true : $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Precio y presentación actualizados.');
    }

    public function generateInternalBarcode(Product $product, ProductPackage $package, ProductBarcodeService $barcodes): RedirectResponse
    {
        abort_unless($package->product_id === $product->getKey(), 404);
        $barcodes->ensureInternal($package);

        return back()->with('success', 'Código interno Code 128 generado correctamente.');
    }

    public function internalBarcodeSvg(Product $product, ProductPackage $package, ProductBarcodeService $barcodes): Response
    {
        abort_unless($package->product_id === $product->getKey(), 404);
        abort_if(blank($package->internal_barcode), 404);

        return response($barcodes->svg($package->internal_barcode), 200, [
            'Content-Disposition' => 'inline; filename="'.$package->internal_barcode.'.svg"',
            'Content-Type' => 'image/svg+xml',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** @return array<string, mixed> */
    private function validateProduct(Request $request, ?Product $product = null): array
    {
        $validated = $request->validate([
            'category_id' => ['required', Rule::exists('catalog_categories', 'id')->where('is_active', true)],
            'brand_id' => ['nullable', Rule::exists('brands', 'id')->where('is_active', true)],
            'code' => ['required', 'alpha_dash:ascii', 'max:40', Rule::unique('products', 'code')->ignore($product)],
            'name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:255'],
            'product_kind' => ['required', Rule::in(array_keys(Product::KINDS))],
            'alcohol_by_volume' => ['nullable', 'numeric', 'min:0.01', 'max:100', 'decimal:0,2'],
            'vat_treatment' => ['required', Rule::in(array_keys(Product::VAT_TREATMENTS))],
            'ice_treatment' => ['required', Rule::in(array_keys(Product::ICE_TREATMENTS))],
            'ice_code' => ['nullable', 'string', 'max:40'],
        ]);

        if ($validated['product_kind'] === 'alcoholic_beverage' && blank($validated['alcohol_by_volume'] ?? null)) {
            throw ValidationException::withMessages([
                'alcohol_by_volume' => 'Registra el grado alcohólico de la bebida.',
            ]);
        }

        $alcoholIceTreatments = ['alcoholic_beverage', 'industrial_beer', 'craft_beer'];

        if ($validated['product_kind'] !== 'alcoholic_beverage' && in_array($validated['ice_treatment'], $alcoholIceTreatments, true)) {
            throw ValidationException::withMessages([
                'ice_treatment' => 'El tratamiento ICE seleccionado corresponde a una bebida alcohólica.',
            ]);
        }

        return $validated;
    }

    /** @return array<string, mixed> */
    private function validatePackage(Request $request, Product $product): array
    {
        return $request->validate([
            'presentation_id' => [
                'required',
                Rule::exists('presentations', 'id')->where('is_active', true),
                Rule::unique('product_packages', 'presentation_id')->where('product_id', $product->getKey()),
            ],
            'barcode' => ['nullable', 'string', 'max:80', 'unique:product_packages,barcode'],
            'price_before_tax' => ['required', 'numeric', 'min:0', 'decimal:0,4'],
        ]);
    }

    private function normalizeAndValidateBarcode(Request $request, ProductBarcodeService $barcodes): void
    {
        $barcode = $barcodes->normalizeManufacturer($request->input('barcode'));
        $request->merge(['barcode' => $barcode]);
        $barcodes->validateManufacturer($barcode);
    }

    private function activeCategories()
    {
        return Category::query()->with('parent')->where('is_active', true)->orderBy('name')->get();
    }

    private function activeBrands()
    {
        return Brand::query()->where('is_active', true)->orderBy('name')->get();
    }

    private function activePresentations()
    {
        return Presentation::query()->with('measurementUnit')->where('is_active', true)->orderBy('name')->get();
    }
}
