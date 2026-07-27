<?php

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Presentation extends Model
{
    protected $fillable = [
        'measurement_unit_id',
        'code',
        'name',
        'quantity',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function measurementUnit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class);
    }

    public function productPackages(): HasMany
    {
        return $this->hasMany(ProductPackage::class);
    }
}
