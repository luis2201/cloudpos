<?php

namespace App\Domain\Inventory\Models;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductPackage;
use App\Domain\Organization\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class InventoryMovement extends Model
{
    protected $fillable = [
        'inventory_operation_id',
        'warehouse_id',
        'product_id',
        'product_package_id',
        'quantity_delta',
        'balance_before',
        'balance_after',
        'package_quantity',
        'units_per_package',
    ];

    protected function casts(): array
    {
        return [
            'quantity_delta' => 'integer',
            'balance_before' => 'integer',
            'balance_after' => 'integer',
            'package_quantity' => 'integer',
            'units_per_package' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('El kárdex es inmutable.'));
        static::deleting(fn () => throw new LogicException('El kárdex es inmutable.'));
    }

    public function operation(): BelongsTo
    {
        return $this->belongsTo(InventoryOperation::class, 'inventory_operation_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productPackage(): BelongsTo
    {
        return $this->belongsTo(ProductPackage::class);
    }
}
