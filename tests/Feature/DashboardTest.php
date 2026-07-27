<?php

namespace Tests\Feature;

use Database\Seeders\TaxRateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_suite_uses_an_isolated_sqlite_database(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
    }

    public function test_dashboard_displays_the_current_tax_rate(): void
    {
        $this->signInConfiguredAdministrator();
        $this->seed(TaxRateSeeder::class);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('CloudPOS')
            ->assertSee('RIMPE Negocio Popular')
            ->assertSee('Sin desglose')
            ->assertSee('15,00')
            ->assertSee('Decreto Ejecutivo No. 198');
    }

    public function test_main_modules_are_available_from_the_navigation(): void
    {
        $this->signInConfiguredAdministrator();

        foreach (['ingresos', 'ventas', 'caja', 'gastos'] as $module) {
            $this->get(route('modules.show', $module))->assertOk();
        }

        $this->get(route('inventory.index'))->assertOk();
    }
}
