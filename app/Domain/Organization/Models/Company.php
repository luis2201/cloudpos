<?php

namespace App\Domain\Organization\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    public const ICE_TAXPAYER_ROLES = [
        'retailer' => 'Comercializador minorista',
        'manufacturer' => 'Fabricante',
        'importer' => 'Importador directo',
        'manufacturer_importer' => 'Fabricante e importador',
    ];

    protected $fillable = [
        'ruc',
        'legal_name',
        'trade_name',
        'taxpayer_type',
        'ice_taxpayer_role',
        'is_franchise',
        'rimpe_category',
        'accounting_required',
        'withholding_agent',
        'withholding_agent_resolution',
        'special_taxpayer',
        'special_taxpayer_resolution',
        'email',
        'phone',
        'address',
        'currency',
        'timezone',
    ];

    public function isDirectIceTaxpayer(): bool
    {
        return $this->ice_taxpayer_role !== 'retailer';
    }

    public function isRimpePopularBusiness(): bool
    {
        return $this->taxpayer_type === 'natural_person'
            && $this->rimpe_category === 'popular_business';
    }

    protected function casts(): array
    {
        return [
            'accounting_required' => 'boolean',
            'is_franchise' => 'boolean',
            'withholding_agent' => 'boolean',
            'special_taxpayer' => 'boolean',
        ];
    }

    public function establishments(): HasMany
    {
        return $this->hasMany(Establishment::class);
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    public function taxProfiles(): HasMany
    {
        return $this->hasMany(CompanyTaxProfile::class);
    }
}
