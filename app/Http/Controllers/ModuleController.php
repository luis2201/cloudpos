<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ModuleController extends Controller
{
    private const MODULES = [
        'ingresos' => [
            'title' => 'Ingresos',
            'description' => 'Registro y trazabilidad de ingresos monetarios distintos a ventas.',
            'icon' => 'income',
            'features' => ['Tipos de ingreso', 'Formas de pago', 'Comprobantes y responsables'],
            'permission' => 'income.manage',
        ],
        'ventas' => [
            'title' => 'Ventas',
            'description' => 'Punto de venta ágil, cálculo tributario y emisión de comprobantes.',
            'icon' => 'cart',
            'features' => ['Catálogo y búsqueda', 'Carrito y cobro', 'Historial de comprobantes'],
            'permission' => 'sales.manage',
        ],
        'caja' => [
            'title' => 'Caja',
            'description' => 'Aperturas, movimientos, arqueos y cierres por responsable.',
            'icon' => 'cash',
            'features' => ['Apertura de turno', 'Entradas y salidas', 'Cuadre y diferencias'],
            'permission' => 'cash.manage',
        ],
        'gastos' => [
            'title' => 'Gastos',
            'description' => 'Control de egresos operativos con categorías y respaldo documental.',
            'icon' => 'receipt',
            'features' => ['Categorías de gasto', 'Registro de pagos', 'Consulta por período'],
            'permission' => 'expenses.manage',
        ],
    ];

    public function show(string $module): View
    {
        abort_unless(array_key_exists($module, self::MODULES), 404);
        Gate::authorize(self::MODULES[$module]['permission']);

        return view('modules.show', [
            'module' => $module,
            'definition' => self::MODULES[$module],
        ]);
    }
}
