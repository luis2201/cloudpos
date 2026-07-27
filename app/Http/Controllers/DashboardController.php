<?php

namespace App\Http\Controllers;

use App\Domain\Organization\Models\Company;
use App\Domain\Tax\Services\CompanyTaxPolicy;
use App\Domain\Tax\Services\TaxRateResolver;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(TaxRateResolver $taxRates, CompanyTaxPolicy $taxPolicy): View
    {
        $company = Company::query()->withCount(['establishments', 'warehouses'])->firstOrFail();

        return view('dashboard', [
            'company' => $company,
            'currentTaxRate' => $taxRates->ivaForDate(),
            'shouldBreakDownVat' => $taxPolicy->shouldBreakDownVat($company),
            'userCount' => User::query()->count(),
            'metrics' => [
                ['label' => 'Ventas de hoy', 'value' => '$ 0,00', 'detail' => '0 comprobantes', 'icon' => 'cart', 'tone' => 'blue'],
                ['label' => 'Caja disponible', 'value' => '$ 0,00', 'detail' => 'Caja aún no aperturada', 'icon' => 'cash', 'tone' => 'navy'],
                ['label' => 'Productos con alerta', 'value' => '0', 'detail' => 'Inventario al día', 'icon' => 'box', 'tone' => 'orange'],
                ['label' => 'Gastos de hoy', 'value' => '$ 0,00', 'detail' => '0 movimientos', 'icon' => 'receipt', 'tone' => 'green'],
            ],
        ]);
    }
}
