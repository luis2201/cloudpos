<?php

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    public const SRI_CODES = [
        '01' => 'Sin utilización del sistema financiero',
        '15' => 'Compensación de deudas',
        '16' => 'Tarjeta de débito',
        '17' => 'Dinero electrónico',
        '18' => 'Tarjeta prepago',
        '19' => 'Tarjeta de crédito',
        '20' => 'Otros con utilización del sistema financiero',
        '21' => 'Endoso de títulos',
    ];

    protected $fillable = [
        'code',
        'sri_code',
        'name',
        'requires_reference',
        'affects_cash',
        'is_system',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'requires_reference' => 'boolean',
            'affects_cash' => 'boolean',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
