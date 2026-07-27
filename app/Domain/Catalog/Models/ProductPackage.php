<?php

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPackage extends Model
{
    protected $fillable = [
        'product_id',
        'presentation_id',
        'barcode',
        'internal_barcode',
        'price_before_tax',
        'units_per_package',
        'is_base',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_before_tax' => 'decimal:4',
            'units_per_package' => 'integer',
            'is_base' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function presentation(): BelongsTo
    {
        return $this->belongsTo(Presentation::class);
    }
}
