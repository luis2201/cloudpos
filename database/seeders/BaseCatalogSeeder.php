<?php

namespace Database\Seeders;

use App\Domain\Catalog\Models\Brand;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\MeasurementUnit;
use App\Domain\Catalog\Models\PaymentMethod;
use App\Domain\Catalog\Models\Presentation;
use Illuminate\Database\Seeder;

class BaseCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCategories();
        $this->seedBrands();

        $units = [
            ['code' => 'UND', 'name' => 'Unidad', 'symbol' => 'u', 'dimension' => 'quantity', 'allows_decimals' => false],
            ['code' => 'ML', 'name' => 'Mililitro', 'symbol' => 'ml', 'dimension' => 'volume', 'allows_decimals' => true],
            ['code' => 'L', 'name' => 'Litro', 'symbol' => 'l', 'dimension' => 'volume', 'allows_decimals' => true],
            ['code' => 'G', 'name' => 'Gramo', 'symbol' => 'g', 'dimension' => 'weight', 'allows_decimals' => true],
            ['code' => 'KG', 'name' => 'Kilogramo', 'symbol' => 'kg', 'dimension' => 'weight', 'allows_decimals' => true],
        ];

        foreach ($units as $unit) {
            MeasurementUnit::query()->firstOrCreate(['code' => $unit['code']], [...$unit, 'is_active' => true]);
        }

        $unitIds = MeasurementUnit::query()->whereIn('code', ['UND', 'ML', 'L'])->pluck('id', 'code');
        $presentations = [
            ['code' => 'UND-1', 'name' => 'Unidad individual', 'quantity' => 1, 'unit' => 'UND'],
            ['code' => 'BOT-750ML', 'name' => 'Botella 750 ml', 'quantity' => 750, 'unit' => 'ML'],
            ['code' => 'BOT-1L', 'name' => 'Botella 1 litro', 'quantity' => 1, 'unit' => 'L'],
            ['code' => 'BOT-330ML', 'name' => 'Botella 330 ml', 'quantity' => 330, 'unit' => 'ML'],
            ['code' => 'BOT-355ML', 'name' => 'Botella 355 ml', 'quantity' => 355, 'unit' => 'ML'],
            ['code' => 'BOT-700ML', 'name' => 'Botella 700 ml', 'quantity' => 700, 'unit' => 'ML'],
            ['code' => 'BOT-1.5L', 'name' => 'Botella 1,5 litros', 'quantity' => 1.5, 'unit' => 'L'],
            ['code' => 'LAT-250ML', 'name' => 'Lata 250 ml', 'quantity' => 250, 'unit' => 'ML'],
            ['code' => 'LAT-330ML', 'name' => 'Lata 330 ml', 'quantity' => 330, 'unit' => 'ML'],
            ['code' => 'LAT-355ML', 'name' => 'Lata 355 ml', 'quantity' => 355, 'unit' => 'ML'],
            ['code' => 'LAT-473ML', 'name' => 'Lata 473 ml', 'quantity' => 473, 'unit' => 'ML'],
            ['code' => 'SIX-6', 'name' => 'Six pack 6 unidades', 'quantity' => 6, 'unit' => 'UND'],
            ['code' => 'CAJ-12', 'name' => 'Caja 12 unidades', 'quantity' => 12, 'unit' => 'UND'],
            ['code' => 'CAJ-24', 'name' => 'Caja 24 unidades', 'quantity' => 24, 'unit' => 'UND'],
        ];

        foreach ($presentations as $presentation) {
            Presentation::query()->firstOrCreate(
                ['code' => $presentation['code']],
                [
                    'name' => $presentation['name'],
                    'quantity' => $presentation['quantity'],
                    'measurement_unit_id' => $unitIds[$presentation['unit']],
                    'is_active' => true,
                ],
            );
        }

        foreach (PaymentMethod::SRI_CODES as $code => $name) {
            PaymentMethod::query()->firstOrCreate(
                ['code' => "SRI-{$code}"],
                [
                    'sri_code' => $code,
                    'name' => $name,
                    'requires_reference' => $code !== '01',
                    'affects_cash' => $code === '01',
                    'is_system' => true,
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedCategories(): void
    {
        $roots = [
            ['code' => 'BEB-ALC', 'name' => 'Bebidas alcohólicas'],
            ['code' => 'BEB-SIN-ALC', 'name' => 'Bebidas sin alcohol'],
            ['code' => 'COMPLEMENTOS', 'name' => 'Complementos'],
        ];

        foreach ($roots as $category) {
            Category::query()->firstOrCreate(['code' => $category['code']], [...$category, 'is_active' => true]);
        }

        $parentIds = Category::query()->whereIn('code', array_column($roots, 'code'))->pluck('id', 'code');
        $children = [
            ['parent' => 'BEB-ALC', 'code' => 'CERVEZA', 'name' => 'Cervezas'],
            ['parent' => 'BEB-ALC', 'code' => 'VINO', 'name' => 'Vinos'],
            ['parent' => 'BEB-ALC', 'code' => 'ESPUMANTE', 'name' => 'Vinos espumantes'],
            ['parent' => 'BEB-ALC', 'code' => 'WHISKY', 'name' => 'Whisky'],
            ['parent' => 'BEB-ALC', 'code' => 'RON', 'name' => 'Ron'],
            ['parent' => 'BEB-ALC', 'code' => 'VODKA', 'name' => 'Vodka'],
            ['parent' => 'BEB-ALC', 'code' => 'TEQUILA', 'name' => 'Tequila'],
            ['parent' => 'BEB-ALC', 'code' => 'GIN', 'name' => 'Gin'],
            ['parent' => 'BEB-ALC', 'code' => 'AGUARDIENTE', 'name' => 'Aguardiente'],
            ['parent' => 'BEB-ALC', 'code' => 'BRANDY-COGNAC', 'name' => 'Brandy y coñac'],
            ['parent' => 'BEB-ALC', 'code' => 'LICORES-CREMAS', 'name' => 'Licores y cremas'],
            ['parent' => 'BEB-SIN-ALC', 'code' => 'AGUA', 'name' => 'Agua'],
            ['parent' => 'BEB-SIN-ALC', 'code' => 'GASEOSA', 'name' => 'Gaseosas'],
            ['parent' => 'BEB-SIN-ALC', 'code' => 'ENERGIZANTE', 'name' => 'Energizantes'],
            ['parent' => 'BEB-SIN-ALC', 'code' => 'MEZCLADOR', 'name' => 'Mezcladores'],
            ['parent' => 'COMPLEMENTOS', 'code' => 'HIELO', 'name' => 'Hielo'],
            ['parent' => 'COMPLEMENTOS', 'code' => 'SNACK', 'name' => 'Snacks'],
            ['parent' => 'COMPLEMENTOS', 'code' => 'ACCESORIO', 'name' => 'Accesorios'],
        ];

        foreach ($children as $category) {
            Category::query()->firstOrCreate(
                ['code' => $category['code']],
                [
                    'parent_id' => $parentIds[$category['parent']],
                    'name' => $category['name'],
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedBrands(): void
    {
        $brands = [
            ['code' => 'SIN-MARCA', 'name' => 'Sin marca'],
            ['code' => 'PILSENER', 'name' => 'Pilsener'],
            ['code' => 'CLUB', 'name' => 'Club'],
            ['code' => 'CORONA', 'name' => 'Corona'],
            ['code' => 'HEINEKEN', 'name' => 'Heineken'],
            ['code' => 'BUDWEISER', 'name' => 'Budweiser'],
            ['code' => 'STELLA-ARTOIS', 'name' => 'Stella Artois'],
            ['code' => 'MODELO', 'name' => 'Modelo'],
            ['code' => 'JOHNNIE-WALKER', 'name' => 'Johnnie Walker'],
            ['code' => 'BUCHANANS', 'name' => "Buchanan's"],
            ['code' => 'OLD-PARR', 'name' => 'Old Parr'],
            ['code' => 'CHIVAS-REGAL', 'name' => 'Chivas Regal'],
            ['code' => 'JACK-DANIELS', 'name' => "Jack Daniel's"],
            ['code' => 'BALLANTINES', 'name' => "Ballantine's"],
            ['code' => 'ABSOLUT', 'name' => 'Absolut'],
            ['code' => 'SMIRNOFF', 'name' => 'Smirnoff'],
            ['code' => 'GREY-GOOSE', 'name' => 'Grey Goose'],
            ['code' => 'BACARDI', 'name' => 'Bacardí'],
            ['code' => 'HAVANA-CLUB', 'name' => 'Havana Club'],
            ['code' => 'JOSE-CUERVO', 'name' => 'José Cuervo'],
            ['code' => 'PATRON', 'name' => 'Patrón'],
            ['code' => 'TANQUERAY', 'name' => 'Tanqueray'],
            ['code' => 'BOMBAY-SAPPHIRE', 'name' => 'Bombay Sapphire'],
            ['code' => 'JAGERMEISTER', 'name' => 'Jägermeister'],
            ['code' => 'BAILEYS', 'name' => 'Baileys'],
            ['code' => 'CASILLERO-DIABLO', 'name' => 'Casillero del Diablo'],
            ['code' => 'CONCHA-Y-TORO', 'name' => 'Concha y Toro'],
            ['code' => 'COCA-COLA', 'name' => 'Coca-Cola'],
            ['code' => 'PEPSI', 'name' => 'Pepsi'],
            ['code' => 'RED-BULL', 'name' => 'Red Bull'],
            ['code' => 'MONSTER', 'name' => 'Monster'],
        ];

        foreach ($brands as $brand) {
            $alreadyRegistered = Brand::query()
                ->where('code', $brand['code'])
                ->orWhere('name', $brand['name'])
                ->exists();

            if (! $alreadyRegistered) {
                Brand::query()->create([...$brand, 'is_active' => true]);
            }
        }
    }
}
