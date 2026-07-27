<?php

namespace App\Domain\Access\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Role extends Model
{
    public const ADMINISTRATOR = 'administrador';

    public const PERMISSIONS = [
        'dashboard.view' => 'Ver el resumen operativo',
        'sales.manage' => 'Gestionar ventas',
        'cash.manage' => 'Gestionar cajas y cuadres',
        'inventory.manage' => 'Gestionar inventario y bodegas',
        'catalog.manage' => 'Gestionar catálogos y productos',
        'income.manage' => 'Gestionar ingresos',
        'expenses.manage' => 'Gestionar gastos',
        'organization.manage' => 'Configurar empresa y establecimientos',
        'users.manage' => 'Gestionar usuarios y roles',
        'taxes.manage' => 'Configurar impuestos',
    ];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'permissions',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_system' => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function grants(string $permission): bool
    {
        $permissions = $this->permissions ?? [];

        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }

    public static function slugFrom(string $name): string
    {
        return Str::slug($name);
    }
}
