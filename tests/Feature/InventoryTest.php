<?php

namespace Tests\Feature;

use App\Domain\Access\Models\Role;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductPackage;
use App\Domain\Inventory\Models\InventoryMovement;
use App\Domain\Inventory\Models\InventoryOperation;
use App\Domain\Inventory\Models\InventoryStock;
use App\Domain\Organization\Models\Warehouse;
use App\Models\User;
use Database\Seeders\BaseCatalogSeeder;
use Database\Seeders\ProductCatalogSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_inventory_combines_packages_and_loose_units_in_one_operation(): void
    {
        [$warehouse, $product, $case] = $this->configuredInventory();
        $unit = $product->packages()->where('is_base', true)->firstOrFail();
        $payload = $this->initialPayload($warehouse, $product, [
            [$case, 10],
            [$unit, 3],
        ], 'Conteo de apertura');

        $this->post(route('inventory.initial.store'), $payload)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('inventory_stocks', [
            'warehouse_id' => $warehouse->getKey(),
            'product_id' => $product->getKey(),
            'quantity' => 243,
        ]);
        $movements = InventoryMovement::query()->orderBy('id')->get();
        $this->assertCount(2, $movements);
        $this->assertSame([240, 3], $movements->pluck('quantity_delta')->all());
        $this->assertSame([0, 240], $movements->pluck('balance_before')->all());
        $this->assertSame([240, 243], $movements->pluck('balance_after')->all());
        $this->assertSame([10, 3], $movements->pluck('package_quantity')->all());
        $this->assertSame([24, 1], $movements->pluck('units_per_package')->all());
        $this->assertSame('initial', $movements->first()->operation->type);
        $this->assertSame($movements->first()->inventory_operation_id, $movements->last()->inventory_operation_id);

        $this->post(route('inventory.initial.store'), $payload)
            ->assertSessionHasErrors('product_id');

        $this->assertSame(1, InventoryOperation::query()->count());
        $this->assertSame(2, InventoryMovement::query()->count());
        $this->assertSame(243, InventoryStock::query()->firstOrFail()->quantity);
    }

    public function test_initial_inventory_rejects_presentations_from_another_product_atomically(): void
    {
        [$warehouse, $product, $case] = $this->configuredInventory();
        $otherPackage = ProductPackage::query()
            ->where('product_id', '!=', $product->getKey())
            ->where('is_base', true)
            ->firstOrFail();

        $this->post(route('inventory.initial.store'), $this->initialPayload($warehouse, $product, [
            [$case, 1],
            [$otherPackage, 2],
        ], 'Conteo inválido'))->assertSessionHasErrors('lines.1.product_package_id');

        $this->assertSame(0, InventoryOperation::query()->count());
        $this->assertSame(0, InventoryMovement::query()->count());
        $this->assertSame(0, InventoryStock::query()->count());
    }

    public function test_negative_stock_obeys_each_warehouse_configuration(): void
    {
        [$warehouse, $product, $case] = $this->configuredInventory();
        $payload = [
            ...$this->packagePayload($warehouse, $case, 1, 'Salida de prueba'),
            'type' => 'exit',
        ];

        $this->post(route('inventory.movements.store'), $payload)
            ->assertSessionHasErrors('package_quantity');

        $this->assertSame(0, InventoryOperation::query()->count());
        $this->assertDatabaseMissing('inventory_stocks', [
            'warehouse_id' => $warehouse->getKey(),
            'product_id' => $product->getKey(),
            'quantity' => -24,
        ]);

        $warehouse->update(['allow_negative_stock' => true]);
        $this->post(route('inventory.movements.store'), $payload)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('inventory_stocks', [
            'warehouse_id' => $warehouse->getKey(),
            'product_id' => $product->getKey(),
            'quantity' => -24,
        ]);
    }

    public function test_transfer_is_atomic_and_writes_two_ledger_rows(): void
    {
        [$source, $product, $case] = $this->configuredInventory();
        $destination = Warehouse::query()->create([
            'company_id' => $source->company_id,
            'establishment_id' => $source->establishment_id,
            'code' => 'BOD-DESTINO',
            'name' => 'Bodega destino',
            'is_main' => false,
            'is_active' => true,
            'allow_negative_stock' => false,
        ]);
        $this->post(route('inventory.initial.store'), $this->initialPayload($source, $product, [[$case, 2]], 'Saldo inicial'))
            ->assertSessionHas('success');

        $this->post(route('inventory.transfers.store'), [
            'source_warehouse_id' => $source->getKey(),
            'destination_warehouse_id' => $destination->getKey(),
            'product_package_id' => $case->getKey(),
            'package_quantity' => 1,
            'reason' => 'Reposición de bodega destino',
            'reference' => 'TR-001',
            'occurred_at' => now()->subMinute()->format('Y-m-d H:i:s'),
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('inventory_stocks', [
            'warehouse_id' => $source->getKey(),
            'product_id' => $product->getKey(),
            'quantity' => 24,
        ]);
        $this->assertDatabaseHas('inventory_stocks', [
            'warehouse_id' => $destination->getKey(),
            'product_id' => $product->getKey(),
            'quantity' => 24,
        ]);

        $transfer = InventoryOperation::query()->where('type', 'transfer')->firstOrFail();
        $this->assertSame($source->getKey(), $transfer->source_warehouse_id);
        $this->assertSame($destination->getKey(), $transfer->destination_warehouse_id);
        $this->assertSame(2, $transfer->movements()->count());
        $this->assertEqualsCanonicalizing([-24, 24], $transfer->movements()->pluck('quantity_delta')->all());
    }

    public function test_adjustment_records_the_difference_and_database_blocks_ledger_mutation(): void
    {
        [$warehouse, $product, $case] = $this->configuredInventory();
        $this->post(route('inventory.initial.store'), $this->initialPayload($warehouse, $product, [[$case, 1]], 'Saldo inicial'));

        $this->post(route('inventory.adjustments.store'), [
            'warehouse_id' => $warehouse->getKey(),
            'product_id' => $product->getKey(),
            'counted_quantity' => 20,
            'reason' => 'Diferencia encontrada en conteo físico',
            'reference' => 'AJ-001',
            'occurred_at' => now()->subMinute()->format('Y-m-d H:i:s'),
        ])->assertSessionHas('success');

        $adjustment = InventoryOperation::query()->where('type', 'adjustment')->firstOrFail();
        $movement = $adjustment->movements()->firstOrFail();
        $this->assertSame(-4, $movement->quantity_delta);
        $this->assertSame(24, $movement->balance_before);
        $this->assertSame(20, $movement->balance_after);

        try {
            DB::table('inventory_movements')->where('id', $movement->getKey())->update(['balance_after' => 999]);
            $this->fail('La base de datos permitió modificar el kárdex.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('inmutable', mb_strtolower($exception->getMessage()));
        }

        try {
            DB::table('inventory_operations')->where('id', $adjustment->getKey())->delete();
            $this->fail('La base de datos permitió eliminar una operación del kárdex.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('inmutable', mb_strtolower($exception->getMessage()));
        }

        $this->assertSame(20, InventoryStock::query()->whereBelongsTo($warehouse)->whereBelongsTo($product)->firstOrFail()->quantity);
    }

    public function test_inventory_screen_filters_by_warehouse_and_permission_is_enforced(): void
    {
        [$warehouse, $product, $case] = $this->configuredInventory();
        $this->post(route('inventory.initial.store'), $this->initialPayload($warehouse, $product, [[$case, 1]], 'Saldo inicial'));

        $this->get(route('inventory.index', ['warehouse_id' => $warehouse->getKey()]))
            ->assertOk()
            ->assertSee('Existencias en '.$warehouse->name)
            ->assertSee($product->name)
            ->assertSee('Kárdex de '.$warehouse->name)
            ->assertSee('INV-00000001');

        $role = Role::query()->create([
            'name' => 'Sin inventario',
            'slug' => 'sin-inventario',
            'permissions' => ['dashboard.view'],
            'is_system' => false,
        ]);
        $user = User::query()->create([
            'name' => 'Usuario restringido',
            'email' => 'sin-inventario@example.test',
            'password' => 'SecurePass123',
            'is_active' => true,
        ]);
        $user->roles()->attach($role);
        $this->actingAs($user);

        $this->get(route('inventory.index'))->assertForbidden();
        $this->post(route('inventory.initial.store'), [])->assertForbidden();
    }

    /** @return array{Warehouse, Product, ProductPackage} */
    private function configuredInventory(): array
    {
        $this->signInConfiguredAdministrator();
        $this->seed([BaseCatalogSeeder::class, ProductCatalogSeeder::class]);
        $warehouse = Warehouse::query()->where('is_main', true)->firstOrFail();
        $product = Product::query()->where('code', 'PILSENER-330-BOT')->firstOrFail();
        $case = $product->packages()
            ->with('product')
            ->whereHas('presentation', fn ($query) => $query->where('code', 'CAJ-24'))
            ->firstOrFail();

        return [$warehouse, $product, $case];
    }

    /** @return array<string, mixed> */
    private function packagePayload(Warehouse $warehouse, ProductPackage $package, int $quantity, string $reason): array
    {
        return [
            'warehouse_id' => $warehouse->getKey(),
            'product_package_id' => $package->getKey(),
            'package_quantity' => $quantity,
            'reason' => $reason,
            'reference' => 'DOC-001',
            'occurred_at' => now()->subMinute()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @param  array<int, array{0: ProductPackage, 1: int}>  $lines
     * @return array<string, mixed>
     */
    private function initialPayload(Warehouse $warehouse, Product $product, array $lines, string $reason): array
    {
        return [
            'warehouse_id' => $warehouse->getKey(),
            'product_id' => $product->getKey(),
            'lines' => collect($lines)->map(fn (array $line): array => [
                'product_package_id' => $line[0]->getKey(),
                'package_quantity' => $line[1],
            ])->all(),
            'reason' => $reason,
            'reference' => 'DOC-001',
            'occurred_at' => now()->subMinute()->format('Y-m-d H:i:s'),
        ];
    }
}
