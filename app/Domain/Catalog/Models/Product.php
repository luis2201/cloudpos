<?php

namespace App\Domain\Catalog\Models;

use App\Domain\Inventory\Models\InventoryMovement;
use App\Domain\Inventory\Models\InventoryStock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    public const KINDS = [
        'alcoholic_beverage' => 'Bebida alcohólica',
        'non_alcoholic_beverage' => 'Bebida sin alcohol',
        'other' => 'Otro producto',
    ];

    public const VAT_TREATMENTS = [
        'general' => 'IVA general vigente',
        'zero' => 'IVA 0%',
        'exempt' => 'Exento de IVA',
        'not_subject' => 'No objeto de IVA',
    ];

    public const ICE_TREATMENTS = [
        'none' => 'Sin referencia ICE',
        'alcoholic_beverage' => 'ICE en origen · Bebida alcohólica',
        'industrial_beer' => 'ICE en origen · Cerveza industrial',
        'craft_beer' => 'ICE en origen · Cerveza artesanal',
        'other' => 'ICE en origen · Otro bien gravado',
    ];

    protected $fillable = [
        'category_id',
        'brand_id',
        'code',
        'name',
        'description',
        'product_kind',
        'alcohol_by_volume',
        'vat_treatment',
        'ice_treatment',
        'ice_code',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'alcohol_by_volume' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(ProductPackage::class);
    }

    public function basePackage(): HasOne
    {
        return $this->hasOne(ProductPackage::class)->where('is_base', true);
    }

    public function inventoryStocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
