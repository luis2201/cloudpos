<?php

namespace App\Domain\Tax\Models;

use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    public const IVA_GENERAL = 'IVA_GENERAL';

    protected $fillable = [
        'tax_type',
        'name',
        'rate',
        'effective_from',
        'legal_reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'effective_from' => 'date',
        ];
    }
}
