<?php

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeasurementUnit extends Model
{
    public const DIMENSIONS = [
        'quantity' => 'Cantidad',
        'volume' => 'Volumen',
        'weight' => 'Peso',
    ];

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'dimension',
        'allows_decimals',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'allows_decimals' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function presentations(): HasMany
    {
        return $this->hasMany(Presentation::class);
    }
}
