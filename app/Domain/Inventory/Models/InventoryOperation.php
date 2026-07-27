<?php

namespace App\Domain\Inventory\Models;

use App\Domain\Organization\Models\Company;
use App\Domain\Organization\Models\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class InventoryOperation extends Model
{
    public const TYPES = [
        'initial' => 'Inventario inicial',
        'entry' => 'Entrada',
        'exit' => 'Salida',
        'adjustment' => 'Ajuste',
        'transfer' => 'Transferencia',
    ];

    protected $fillable = [
        'company_id',
        'type',
        'source_warehouse_id',
        'destination_warehouse_id',
        'reference',
        'reason',
        'occurred_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return ['occurred_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('El kárdex es inmutable.'));
        static::deleting(fn () => throw new LogicException('El kárdex es inmutable.'));
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function sourceWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
