<?php

use App\Http\Controllers\Access\RoleController;
use App\Http\Controllers\Access\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Catalog\BaseCatalogController;
use App\Http\Controllers\Catalog\ProductController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Inventory\InventoryController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\Organization\EstablishmentController;
use App\Http\Controllers\Organization\WarehouseController;
use App\Http\Controllers\Settings\CompanyController;
use App\Http\Controllers\Settings\CompanyTaxProfileController;
use App\Http\Controllers\Settings\TaxRateController;
use App\Http\Controllers\Setup\CompanySetupController;
use App\Http\Controllers\Setup\EstablishmentSetupController;
use App\Http\Controllers\Setup\FirstAdminController;
use App\Http\Controllers\Setup\WarehouseSetupController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/instalacion/administrador', [FirstAdminController::class, 'create'])->name('setup.admin.create');
    Route::post('/instalacion/administrador', [FirstAdminController::class, 'store'])->middleware('throttle:5,1')->name('setup.admin.store');

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/instalacion/empresa', [CompanySetupController::class, 'create'])->name('setup.company.create');
    Route::post('/instalacion/empresa', [CompanySetupController::class, 'store'])->name('setup.company.store');
    Route::get('/instalacion/establecimiento', [EstablishmentSetupController::class, 'create'])->name('setup.establishment.create');
    Route::post('/instalacion/establecimiento', [EstablishmentSetupController::class, 'store'])->name('setup.establishment.store');
    Route::get('/instalacion/bodega', [WarehouseSetupController::class, 'create'])->name('setup.warehouse.create');
    Route::post('/instalacion/bodega', [WarehouseSetupController::class, 'store'])->name('setup.warehouse.store');

    Route::middleware('system.configured')->group(function (): void {
        Route::get('/', DashboardController::class)->middleware('can:dashboard.view')->name('dashboard');

        Route::get('/modulos/{module}', [ModuleController::class, 'show'])
            ->whereIn('module', ['ingresos', 'ventas', 'caja', 'gastos'])
            ->name('modules.show');

        Route::prefix('inventario')->name('inventory.')->middleware('can:inventory.manage')->group(function (): void {
            Route::get('/', [InventoryController::class, 'index'])->name('index');
            Route::post('/inicial', [InventoryController::class, 'storeInitial'])->name('initial.store');
            Route::post('/movimientos', [InventoryController::class, 'storeMovement'])->name('movements.store');
            Route::post('/ajustes', [InventoryController::class, 'storeAdjustment'])->name('adjustments.store');
            Route::post('/transferencias', [InventoryController::class, 'storeTransfer'])->name('transfers.store');
        });

        Route::prefix('configuracion')->name('settings.')->group(function (): void {
            Route::get('/empresa', [CompanyController::class, 'edit'])->middleware('can:organization.manage')->name('company.edit');
            Route::put('/empresa', [CompanyController::class, 'update'])->middleware('can:organization.manage')->name('company.update');
            Route::post('/empresa/perfiles-tributarios', [CompanyTaxProfileController::class, 'store'])->middleware('can:taxes.manage')->name('company-tax-profiles.store');
            Route::get('/impuestos', [TaxRateController::class, 'index'])->middleware('can:taxes.manage')->name('tax-rates.index');
            Route::post('/impuestos', [TaxRateController::class, 'store'])->middleware('can:taxes.manage')->name('tax-rates.store');
        });

        Route::middleware('can:organization.manage')->group(function (): void {
            Route::get('/establecimientos', [EstablishmentController::class, 'index'])->name('establishments.index');
            Route::post('/establecimientos', [EstablishmentController::class, 'store'])->name('establishments.store');
            Route::put('/establecimientos/{establishment}', [EstablishmentController::class, 'update'])->name('establishments.update');
            Route::get('/bodegas', [WarehouseController::class, 'index'])->name('warehouses.index');
            Route::post('/bodegas', [WarehouseController::class, 'store'])->name('warehouses.store');
            Route::put('/bodegas/{warehouse}', [WarehouseController::class, 'update'])->name('warehouses.update');
        });

        Route::prefix('catalogos')->name('catalogs.')->middleware('can:catalog.manage')->group(function (): void {
            Route::get('/', [BaseCatalogController::class, 'index'])->name('index');
            Route::post('/categorias', [BaseCatalogController::class, 'storeCategory'])->name('categories.store');
            Route::put('/categorias/{category}', [BaseCatalogController::class, 'updateCategory'])->name('categories.update');
            Route::post('/marcas', [BaseCatalogController::class, 'storeBrand'])->name('brands.store');
            Route::put('/marcas/{brand}', [BaseCatalogController::class, 'updateBrand'])->name('brands.update');
            Route::post('/unidades', [BaseCatalogController::class, 'storeUnit'])->name('units.store');
            Route::put('/unidades/{unit}', [BaseCatalogController::class, 'updateUnit'])->name('units.update');
            Route::post('/presentaciones', [BaseCatalogController::class, 'storePresentation'])->name('presentations.store');
            Route::put('/presentaciones/{presentation}', [BaseCatalogController::class, 'updatePresentation'])->name('presentations.update');
            Route::post('/formas-pago', [BaseCatalogController::class, 'storePaymentMethod'])->name('payment-methods.store');
            Route::put('/formas-pago/{paymentMethod}', [BaseCatalogController::class, 'updatePaymentMethod'])->name('payment-methods.update');
        });

        Route::prefix('productos')->name('products.')->middleware('can:catalog.manage')->group(function (): void {
            Route::get('/', [ProductController::class, 'index'])->name('index');
            Route::post('/', [ProductController::class, 'store'])->name('store');
            Route::get('/{product}', [ProductController::class, 'show'])->name('show');
            Route::put('/{product}', [ProductController::class, 'update'])->name('update');
            Route::post('/{product}/presentaciones', [ProductController::class, 'storePackage'])->name('packages.store');
            Route::put('/{product}/presentaciones/{package}', [ProductController::class, 'updatePackage'])->name('packages.update');
            Route::post('/{product}/presentaciones/{package}/codigo-interno', [ProductController::class, 'generateInternalBarcode'])->name('packages.internal-barcode');
            Route::get('/{product}/presentaciones/{package}/codigo-interno.svg', [ProductController::class, 'internalBarcodeSvg'])->name('packages.internal-barcode.svg');
        });

        Route::middleware('can:users.manage')->group(function (): void {
            Route::get('/usuarios', [UserController::class, 'index'])->name('users.index');
            Route::post('/usuarios', [UserController::class, 'store'])->name('users.store');
            Route::put('/usuarios/{user}', [UserController::class, 'update'])->name('users.update');
            Route::patch('/usuarios/{user}/estado', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
            Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
            Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
            Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        });
    });
});
