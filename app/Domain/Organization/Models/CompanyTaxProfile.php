<?php

namespace App\Domain\Organization\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyTaxProfile extends Model
{
    public const REGIMES = [
        'rimpe_popular_business' => 'RIMPE Negocio Popular',
        'rimpe_entrepreneur' => 'RIMPE Emprendedor',
        'general' => 'Régimen general',
    ];

    protected $fillable = [
        'company_id',
        'tax_regime',
        'taxpayer_type',
        'accounting_required',
        'is_franchise',
        'effective_from',
        'legal_reference',
        'notes',
        'created_by',
    ];

    public function isRimpePopularBusiness(): bool
    {
        return $this->tax_regime === 'rimpe_popular_business';
    }

    protected function casts(): array
    {
        return [
            'accounting_required' => 'boolean',
            'is_franchise' => 'boolean',
            'effective_from' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
