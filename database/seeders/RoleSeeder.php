<?php

namespace Database\Seeders;

use App\Domain\Access\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::query()->updateOrCreate(
            ['slug' => Role::ADMINISTRATOR],
            [
                'name' => 'Administrador',
                'description' => 'Acceso total y protegido a la configuración de CloudPOS.',
                'permissions' => ['*'],
                'is_system' => true,
            ],
        );
    }
}
