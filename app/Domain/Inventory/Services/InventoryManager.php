<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductPackage;
use App\Domain\Catalog\Services\InventoryUnitConverter;
use App\Domain\Inventory\Models\InventoryMovement;
use App\Domain\Inventory\Models\InventoryOperation;
use App\Domain\Inventory\Models\InventoryStock;
use App\Domain\Organization\Models\Warehouse;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryManager
{
    public function __construct(private readonly InventoryUnitConverter $converter) {}

    public function loadInitial(
        Warehouse $warehouse,
        ProductPackage $package,
        int $packageQuantity,
        User $actor,
        string $reason,
        ?string $reference = null,
        ?string $occurredAt = null,
    ): InventoryOperation {
        return $this->loadInitialBatch(
            $warehouse,
            $package->product,
            collect([['package' => $package, 'package_quantity' => $packageQuantity]]),
            $actor,
            $reason,
            $reference,
            $occurredAt,
        );
    }

    /**
     * @param  Collection<int, array{package: ProductPackage, package_quantity: int}>  $lines
     */
    public function loadInitialBatch(
        Warehouse $warehouse,
        Product $product,
        Collection $lines,
        User $actor,
        string $reason,
        ?string $reference = null,
        ?string $occurredAt = null,
    ): InventoryOperation {
        $this->ensureWarehouseAndProductAreActive($warehouse, $product);

        if ($lines->isEmpty()) {
            throw ValidationException::withMessages(['lines' => 'Agrega al menos una presentación al inventario inicial.']);
        }

        $normalizedLines = $lines->values()->map(function (array $line, int $index) use ($product): array {
            $package = $line['package'];
            $packageQuantity = (int) $line['package_quantity'];
            $this->ensurePackageIsActive($package);

            if ($package->product_id !== $product->getKey()) {
                throw ValidationException::withMessages([
                    "lines.{$index}.product_package_id" => 'Todas las presentaciones deben pertenecer al producto seleccionado.',
                ]);
            }

            if ($packageQuantity < 1) {
                throw ValidationException::withMessages([
                    "lines.{$index}.package_quantity" => 'La cantidad debe ser al menos uno.',
                ]);
            }

            return [
                'package' => $package,
                'package_quantity' => $packageQuantity,
                'units' => $this->converter->convert($package, $packageQuantity),
            ];
        });

        if ($normalizedLines->pluck('package.id')->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['lines' => 'No repitas una presentación; consolida su cantidad en una sola línea.']);
        }

        return DB::transaction(function () use ($warehouse, $product, $normalizedLines, $actor, $reason, $reference, $occurredAt): InventoryOperation {
            $stock = $this->lockedStock($warehouse, $product);

            if (InventoryMovement::query()
                ->where('warehouse_id', $warehouse->getKey())
                ->where('product_id', $product->getKey())
                ->lockForUpdate()
                ->exists()) {
                throw ValidationException::withMessages([
                    'product_id' => 'Este producto ya tiene movimientos en la bodega. Utiliza una entrada o un ajuste.',
                ]);
            }

            $operation = $this->createOperation(
                'initial',
                null,
                $warehouse,
                $actor,
                $reason,
                $reference,
                $occurredAt,
            );

            foreach ($normalizedLines as $line) {
                $this->applyLockedMovement(
                    $stock,
                    $warehouse,
                    $product,
                    $operation,
                    $line['units'],
                    $line['package'],
                    $line['package_quantity'],
                );
            }

            return $operation;
        }, 3);
    }

    public function postMovement(
        string $type,
        Warehouse $warehouse,
        ProductPackage $package,
        int $packageQuantity,
        User $actor,
        string $reason,
        ?string $reference = null,
        ?string $occurredAt = null,
    ): InventoryOperation {
        if (! in_array($type, ['entry', 'exit'], true)) {
            throw ValidationException::withMessages(['type' => 'El tipo de movimiento no es válido.']);
        }

        return $this->postPackageMovement($type, $warehouse, $package, $packageQuantity, $actor, $reason, $reference, $occurredAt);
    }

    public function adjust(
        Warehouse $warehouse,
        Product $product,
        int $countedQuantity,
        User $actor,
        string $reason,
        ?string $reference = null,
        ?string $occurredAt = null,
    ): InventoryOperation {
        $this->ensureWarehouseAndProductAreActive($warehouse, $product);

        return DB::transaction(function () use ($warehouse, $product, $countedQuantity, $actor, $reason, $reference, $occurredAt): InventoryOperation {
            $stock = $this->lockedStock($warehouse, $product);
            $delta = $countedQuantity - $stock->quantity;

            if ($delta === 0) {
                throw ValidationException::withMessages([
                    'counted_quantity' => 'El conteo coincide con la existencia actual; no existe diferencia que ajustar.',
                ]);
            }

            $operation = $this->createOperation(
                'adjustment',
                $warehouse,
                null,
                $actor,
                $reason,
                $reference,
                $occurredAt,
            );
            $this->applyLockedMovement($stock, $warehouse, $product, $operation, $delta);

            return $operation;
        }, 3);
    }

    public function transfer(
        Warehouse $source,
        Warehouse $destination,
        ProductPackage $package,
        int $packageQuantity,
        User $actor,
        string $reason,
        ?string $reference = null,
        ?string $occurredAt = null,
    ): InventoryOperation {
        if ($source->is($destination)) {
            throw ValidationException::withMessages(['destination_warehouse_id' => 'Selecciona una bodega de destino diferente.']);
        }

        if ($source->company_id !== $destination->company_id) {
            throw ValidationException::withMessages(['destination_warehouse_id' => 'Las bodegas deben pertenecer a la misma empresa.']);
        }

        $product = $package->product;
        $this->ensureWarehouseAndProductAreActive($source, $product);
        $this->ensureWarehouseAndProductAreActive($destination, $product);
        $this->ensurePackageIsActive($package);
        $units = $this->converter->convert($package, $packageQuantity);

        return DB::transaction(function () use ($source, $destination, $package, $packageQuantity, $actor, $reason, $reference, $occurredAt, $product, $units): InventoryOperation {
            $stocks = $this->lockedStocks(collect([$source, $destination]), $product);
            $sourceStock = $stocks->get($source->getKey());
            $destinationStock = $stocks->get($destination->getKey());
            $this->assertNegativeStockPolicy($source, $sourceStock->quantity - $units);

            $operation = $this->createOperation(
                'transfer',
                $source,
                $destination,
                $actor,
                $reason,
                $reference,
                $occurredAt,
            );
            $this->applyLockedMovement($sourceStock, $source, $product, $operation, -$units, $package, $packageQuantity);
            $this->applyLockedMovement($destinationStock, $destination, $product, $operation, $units, $package, $packageQuantity);

            return $operation;
        }, 3);
    }

    private function postPackageMovement(
        string $type,
        Warehouse $warehouse,
        ProductPackage $package,
        int $packageQuantity,
        User $actor,
        string $reason,
        ?string $reference,
        ?string $occurredAt,
    ): InventoryOperation {
        $product = $package->product;
        $this->ensureWarehouseAndProductAreActive($warehouse, $product);
        $this->ensurePackageIsActive($package);
        $units = $this->converter->convert($package, $packageQuantity);
        $delta = $type === 'exit' ? -$units : $units;

        return DB::transaction(function () use ($type, $warehouse, $package, $packageQuantity, $actor, $reason, $reference, $occurredAt, $product, $delta): InventoryOperation {
            $stock = $this->lockedStock($warehouse, $product);

            $this->assertNegativeStockPolicy($warehouse, $stock->quantity + $delta);
            $operation = $this->createOperation(
                $type,
                $type === 'exit' ? $warehouse : null,
                $type === 'exit' ? null : $warehouse,
                $actor,
                $reason,
                $reference,
                $occurredAt,
            );
            $this->applyLockedMovement($stock, $warehouse, $product, $operation, $delta, $package, $packageQuantity);

            return $operation;
        }, 3);
    }

    private function lockedStock(Warehouse $warehouse, Product $product): InventoryStock
    {
        $this->ensureStockRows(collect([$warehouse]), $product);

        return InventoryStock::query()
            ->where('warehouse_id', $warehouse->getKey())
            ->where('product_id', $product->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    /** @param Collection<int, Warehouse> $warehouses */
    private function lockedStocks(Collection $warehouses, Product $product): Collection
    {
        $this->ensureStockRows($warehouses, $product);

        return InventoryStock::query()
            ->whereIn('warehouse_id', $warehouses->pluck('id'))
            ->where('product_id', $product->getKey())
            ->orderBy('warehouse_id')
            ->lockForUpdate()
            ->get()
            ->keyBy('warehouse_id');
    }

    /** @param Collection<int, Warehouse> $warehouses */
    private function ensureStockRows(Collection $warehouses, Product $product): void
    {
        $now = now();

        foreach ($warehouses->sortBy('id') as $warehouse) {
            DB::table('inventory_stocks')->insertOrIgnore([
                'warehouse_id' => $warehouse->getKey(),
                'product_id' => $product->getKey(),
                'quantity' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function applyLockedMovement(
        InventoryStock $stock,
        Warehouse $warehouse,
        Product $product,
        InventoryOperation $operation,
        int $delta,
        ?ProductPackage $package = null,
        ?int $packageQuantity = null,
    ): InventoryMovement {
        $before = $stock->quantity;
        $after = $before + $delta;
        $this->assertNegativeStockPolicy($warehouse, $after);
        $stock->update(['quantity' => $after]);

        return $operation->movements()->create([
            'warehouse_id' => $warehouse->getKey(),
            'product_id' => $product->getKey(),
            'product_package_id' => $package?->getKey(),
            'quantity_delta' => $delta,
            'balance_before' => $before,
            'balance_after' => $after,
            'package_quantity' => $packageQuantity,
            'units_per_package' => $package?->units_per_package ?? 1,
        ]);
    }

    private function createOperation(
        string $type,
        ?Warehouse $source,
        ?Warehouse $destination,
        User $actor,
        string $reason,
        ?string $reference,
        ?string $occurredAt,
    ): InventoryOperation {
        $warehouse = $source ?? $destination;

        return InventoryOperation::query()->create([
            'company_id' => $warehouse->company_id,
            'type' => $type,
            'source_warehouse_id' => $source?->getKey(),
            'destination_warehouse_id' => $destination?->getKey(),
            'reference' => filled($reference) ? trim($reference) : null,
            'reason' => trim($reason),
            'occurred_at' => CarbonImmutable::parse($occurredAt ?: 'now', config('app.timezone')),
            'created_by' => $actor->getKey(),
        ]);
    }

    private function assertNegativeStockPolicy(Warehouse $warehouse, int $newBalance): void
    {
        if ($newBalance < 0 && ! $warehouse->allow_negative_stock) {
            throw ValidationException::withMessages([
                'package_quantity' => "Stock insuficiente en {$warehouse->name}. La bodega bloquea existencias negativas.",
            ]);
        }
    }

    private function ensureWarehouseAndProductAreActive(Warehouse $warehouse, Product $product): void
    {
        if (! $warehouse->is_active) {
            throw ValidationException::withMessages(['warehouse_id' => 'La bodega seleccionada está inactiva.']);
        }

        if (! $product->is_active) {
            throw ValidationException::withMessages(['product_id' => 'El producto seleccionado está inactivo.']);
        }
    }

    private function ensurePackageIsActive(ProductPackage $package): void
    {
        if (! $package->is_active) {
            throw ValidationException::withMessages(['product_package_id' => 'La presentación seleccionada está inactiva.']);
        }
    }
}
