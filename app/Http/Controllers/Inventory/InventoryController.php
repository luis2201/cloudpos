<?php

namespace App\Http\Controllers\Inventory;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductPackage;
use App\Domain\Inventory\Models\InventoryMovement;
use App\Domain\Inventory\Models\InventoryOperation;
use App\Domain\Inventory\Models\InventoryStock;
use App\Domain\Inventory\Services\InventoryManager;
use App\Domain\Organization\Models\Company;
use App\Domain\Organization\Models\Warehouse;
use App\Http\Controllers\Controller;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $company = Company::query()->firstOrFail();
        $warehouses = $company->warehouses()
            ->with('establishment')
            ->where('is_active', true)
            ->orderByDesc('is_main')
            ->orderBy('code')
            ->get();
        $selectedWarehouse = $warehouses->firstWhere('id', $request->integer('warehouse_id'))
            ?? $warehouses->firstOrFail();

        $search = trim($request->string('search')->toString());
        $stockStatus = in_array($request->string('stock_status')->toString(), ['positive', 'zero', 'negative'], true)
            ? $request->string('stock_status')->toString()
            : '';
        $sort = in_array($request->string('sort')->toString(), ['code', 'name', 'stock'], true)
            ? $request->string('sort')->toString()
            : 'name';
        $direction = $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc';

        $stocks = Product::query()
            ->leftJoin('inventory_stocks as selected_stock', function (JoinClause $join) use ($selectedWarehouse): void {
                $join->on('selected_stock.product_id', '=', 'products.id')
                    ->where('selected_stock.warehouse_id', $selectedWarehouse->getKey());
            })
            ->select('products.*')
            ->selectRaw('COALESCE(selected_stock.quantity, 0) as stock_quantity')
            ->with(['brand', 'category', 'basePackage.presentation'])
            ->where('products.is_active', true)
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('products.code', 'like', "%{$search}%")
                ->orWhere('products.name', 'like', "%{$search}%")
                ->orWhereHas('packages', fn ($query) => $query
                    ->where('barcode', 'like', "%{$search}%")
                    ->orWhere('internal_barcode', 'like', "%{$search}%"))))
            ->when($stockStatus === 'positive', fn ($query) => $query->whereRaw('COALESCE(selected_stock.quantity, 0) > 0'))
            ->when($stockStatus === 'zero', fn ($query) => $query->whereRaw('COALESCE(selected_stock.quantity, 0) = 0'))
            ->when($stockStatus === 'negative', fn ($query) => $query->whereRaw('COALESCE(selected_stock.quantity, 0) < 0'));

        $sort === 'stock'
            ? $stocks->orderByRaw("COALESCE(selected_stock.quantity, 0) {$direction}")
            : $stocks->orderBy("products.{$sort}", $direction);

        $ledgerSearch = trim($request->string('ledger_search')->toString());
        $ledgerType = array_key_exists($request->string('ledger_type')->toString(), InventoryOperation::TYPES)
            ? $request->string('ledger_type')->toString()
            : '';
        $ledgerProductId = $request->integer('ledger_product_id') ?: null;
        $dateFrom = $this->validDate($request->string('date_from')->toString());
        $dateTo = $this->validDate($request->string('date_to')->toString());

        $ledger = InventoryMovement::query()
            ->with([
                'operation.creator',
                'operation.sourceWarehouse',
                'operation.destinationWarehouse',
                'product',
                'productPackage.presentation',
                'warehouse',
            ])
            ->where('warehouse_id', $selectedWarehouse->getKey())
            ->when($ledgerType !== '', fn ($query) => $query->whereHas('operation', fn ($query) => $query->where('type', $ledgerType)))
            ->when($ledgerProductId, fn ($query) => $query->where('product_id', $ledgerProductId))
            ->when($dateFrom, fn ($query) => $query->whereHas('operation', fn ($query) => $query->whereDate('occurred_at', '>=', $dateFrom)))
            ->when($dateTo, fn ($query) => $query->whereHas('operation', fn ($query) => $query->whereDate('occurred_at', '<=', $dateTo)))
            ->when($ledgerSearch !== '', fn ($query) => $query->where(fn ($query) => $query
                ->whereHas('product', fn ($query) => $query
                    ->where('code', 'like', "%{$ledgerSearch}%")
                    ->orWhere('name', 'like', "%{$ledgerSearch}%"))
                ->orWhereHas('operation', fn ($query) => $query
                    ->where('reference', 'like', "%{$ledgerSearch}%")
                    ->orWhere('reason', 'like', "%{$ledgerSearch}%"))))
            ->orderByDesc(
                InventoryOperation::query()
                    ->select('occurred_at')
                    ->whereColumn('inventory_operations.id', 'inventory_movements.inventory_operation_id')
                    ->limit(1),
            )
            ->orderByDesc('id')
            ->paginate(12, ['inventory_movements.*'], 'ledger_page')
            ->withQueryString();

        $totalProducts = Product::query()->where('is_active', true)->count();
        $stockRows = InventoryStock::query()
            ->where('warehouse_id', $selectedWarehouse->getKey())
            ->whereHas('product', fn ($query) => $query->where('is_active', true));
        $positiveProducts = (clone $stockRows)->where('quantity', '>', 0)->count();
        $negativeProducts = (clone $stockRows)->where('quantity', '<', 0)->count();

        return view('inventory.index', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'direction' => $direction,
            'ledger' => $ledger,
            'ledgerProductId' => $ledgerProductId,
            'ledgerSearch' => $ledgerSearch,
            'ledgerType' => $ledgerType,
            'movementTypes' => InventoryOperation::TYPES,
            'negativeProducts' => $negativeProducts,
            'packages' => ProductPackage::query()
                ->with(['presentation', 'product'])
                ->where('is_active', true)
                ->whereHas('product', fn ($query) => $query->where('is_active', true))
                ->get()
                ->sortBy(fn (ProductPackage $package) => $package->product->name.' '.$package->presentation->name),
            'positiveProducts' => $positiveProducts,
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(),
            'search' => $search,
            'selectedWarehouse' => $selectedWarehouse,
            'sort' => $sort,
            'stockStatus' => $stockStatus,
            'stocks' => $stocks->paginate(12, ['products.*'], 'stock_page')->withQueryString(),
            'totalProducts' => $totalProducts,
            'totalUnits' => (int) (clone $stockRows)->sum('quantity'),
            'warehouses' => $warehouses,
            'zeroProducts' => $totalProducts - $positiveProducts - $negativeProducts,
        ]);
    }

    public function storeInitial(Request $request, InventoryManager $inventory): RedirectResponse
    {
        $validated = $request->validate([
            ...$this->warehouseRules(),
            'product_id' => ['required', Rule::exists('products', 'id')->where('is_active', true)],
            'lines' => ['required', 'array', 'min:1', 'max:20'],
            'lines.*.product_package_id' => [
                'required',
                'integer',
                'distinct:strict',
                Rule::exists('product_packages', 'id')->where('is_active', true),
            ],
            'lines.*.package_quantity' => ['required', 'integer', 'min:1'],
            ...$this->auditRules(),
        ]);
        $packages = ProductPackage::query()
            ->with('product')
            ->whereIn('id', collect($validated['lines'])->pluck('product_package_id'))
            ->get()
            ->keyBy('id');
        $lines = collect($validated['lines'])->map(fn (array $line): array => [
            'package' => $packages->get((int) $line['product_package_id']),
            'package_quantity' => (int) $line['package_quantity'],
        ]);
        $operation = $inventory->loadInitialBatch(
            Warehouse::query()->findOrFail($validated['warehouse_id']),
            Product::query()->findOrFail($validated['product_id']),
            $lines,
            $request->user(),
            $validated['reason'],
            $validated['reference'] ?? null,
            $validated['occurred_at'] ?? null,
        );

        return back()->with('success', $this->operationSaved($operation));
    }

    public function storeMovement(Request $request, InventoryManager $inventory): RedirectResponse
    {
        $validated = [
            ...$this->validatePackageMovement($request),
            ...$request->validate(['type' => ['required', Rule::in(['entry', 'exit'])]]),
        ];
        $operation = $inventory->postMovement(
            $validated['type'],
            Warehouse::query()->findOrFail($validated['warehouse_id']),
            ProductPackage::query()->with('product')->findOrFail($validated['product_package_id']),
            (int) $validated['package_quantity'],
            $request->user(),
            $validated['reason'],
            $validated['reference'] ?? null,
            $validated['occurred_at'] ?? null,
        );

        return back()->with('success', $this->operationSaved($operation));
    }

    public function storeAdjustment(Request $request, InventoryManager $inventory): RedirectResponse
    {
        $validated = $request->validate([
            ...$this->warehouseRules(),
            'product_id' => ['required', Rule::exists('products', 'id')->where('is_active', true)],
            'counted_quantity' => ['required', 'integer', 'min:0'],
            ...$this->auditRules(),
        ]);
        $operation = $inventory->adjust(
            Warehouse::query()->findOrFail($validated['warehouse_id']),
            Product::query()->findOrFail($validated['product_id']),
            (int) $validated['counted_quantity'],
            $request->user(),
            $validated['reason'],
            $validated['reference'] ?? null,
            $validated['occurred_at'] ?? null,
        );

        return back()->with('success', $this->operationSaved($operation));
    }

    public function storeTransfer(Request $request, InventoryManager $inventory): RedirectResponse
    {
        $company = Company::query()->firstOrFail();
        $warehouseRule = Rule::exists('warehouses', 'id')->where(fn ($query) => $query
            ->where('company_id', $company->getKey())
            ->where('is_active', true));
        $validated = $request->validate([
            'source_warehouse_id' => ['required', $warehouseRule],
            'destination_warehouse_id' => ['required', 'different:source_warehouse_id', $warehouseRule],
            'product_package_id' => ['required', Rule::exists('product_packages', 'id')->where('is_active', true)],
            'package_quantity' => ['required', 'integer', 'min:1'],
            ...$this->auditRules(),
        ]);
        $operation = $inventory->transfer(
            Warehouse::query()->findOrFail($validated['source_warehouse_id']),
            Warehouse::query()->findOrFail($validated['destination_warehouse_id']),
            ProductPackage::query()->with('product')->findOrFail($validated['product_package_id']),
            (int) $validated['package_quantity'],
            $request->user(),
            $validated['reason'],
            $validated['reference'] ?? null,
            $validated['occurred_at'] ?? null,
        );

        return back()->with('success', $this->operationSaved($operation));
    }

    /** @return array<string, mixed> */
    private function validatePackageMovement(Request $request): array
    {
        return $request->validate([
            ...$this->warehouseRules(),
            'product_package_id' => ['required', Rule::exists('product_packages', 'id')->where('is_active', true)],
            'package_quantity' => ['required', 'integer', 'min:1'],
            ...$this->auditRules(),
        ]);
    }

    /** @return array<string, mixed> */
    private function warehouseRules(): array
    {
        $company = Company::query()->firstOrFail();

        return [
            'warehouse_id' => [
                'required',
                Rule::exists('warehouses', 'id')->where(fn ($query) => $query
                    ->where('company_id', $company->getKey())
                    ->where('is_active', true)),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function auditRules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:3', 'max:255'],
            'reference' => ['nullable', 'string', 'max:60'],
            'occurred_at' => ['nullable', 'date', 'before_or_equal:now'],
        ];
    }

    private function validDate(string $date): ?string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 ? $date : null;
    }

    private function operationSaved(InventoryOperation $operation): string
    {
        return 'Movimiento INV-'.str_pad((string) $operation->getKey(), 8, '0', STR_PAD_LEFT).' registrado en el kárdex.';
    }
}
