<?php

namespace Database\Seeders;

use App\Domain\Catalog\Models\Brand;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Presentation;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Services\InventoryUnitConverter;
use App\Domain\Catalog\Services\ProductBarcodeService;
use Illuminate\Database\Seeder;

class ProductCatalogSeeder extends Seeder
{
    public function run(InventoryUnitConverter $converter, ProductBarcodeService $barcodes): void
    {
        $categories = Category::query()->pluck('id', 'code');
        $brands = Brand::query()->pluck('id', 'code');
        $presentations = Presentation::query()->with('measurementUnit')->get()->keyBy('code');

        foreach ($this->products() as $definition) {
            $product = Product::query()->firstOrCreate(
                ['code' => $definition['code']],
                [
                    'category_id' => $categories[$definition['category']],
                    'brand_id' => $brands[$definition['brand']],
                    'name' => $definition['name'],
                    'description' => 'Catálogo inicial. Verifica la etiqueta, el precio y el código de barras antes de vender.',
                    'product_kind' => $definition['kind'],
                    'alcohol_by_volume' => $definition['alcohol'],
                    'vat_treatment' => 'general',
                    'ice_treatment' => $definition['ice'],
                    'ice_code' => null,
                    'is_active' => true,
                ],
            );

            foreach ($definition['packages'] as $index => $presentationCode) {
                $presentation = $presentations->get($presentationCode);

                $unitsPerPackage = $converter->unitsPerPackage($presentation);
                $initialPrice = $this->packagePrice($definition['base_price'], $presentationCode, $unitsPerPackage);
                $package = $product->packages()->firstOrCreate(
                    ['presentation_id' => $presentation->getKey()],
                    [
                        'barcode' => null,
                        'price_before_tax' => $initialPrice,
                        'units_per_package' => $unitsPerPackage,
                        'is_base' => $index === 0 && ! $product->packages()->where('is_base', true)->exists(),
                        'is_active' => true,
                    ],
                );

                if ((float) $package->price_before_tax <= 0) {
                    $package->update(['price_before_tax' => $initialPrice]);
                }

                $barcodes->ensureInternal($package);
            }
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function products(): array
    {
        return [
            $this->product('PILSENER-330-BOT', 'Cerveza Pilsener 330 ml', 'CERVEZA', 'PILSENER', 4.20, 'industrial_beer', 0.80, ['BOT-330ML', 'SIX-6', 'CAJ-24']),
            $this->product('CLUB-PREMIUM-330-BOT', 'Cerveza Club Premium 330 ml', 'CERVEZA', 'CLUB', 4.40, 'industrial_beer', 0.95, ['BOT-330ML', 'SIX-6', 'CAJ-24']),
            $this->product('CORONA-EXTRA-355-BOT', 'Cerveza Corona Extra 355 ml', 'CERVEZA', 'CORONA', 4.50, 'industrial_beer', 1.45, ['BOT-355ML', 'SIX-6', 'CAJ-24']),
            $this->product('HEINEKEN-330-BOT', 'Cerveza Heineken 330 ml', 'CERVEZA', 'HEINEKEN', 5.00, 'industrial_beer', 1.40, ['BOT-330ML', 'SIX-6', 'CAJ-24']),
            $this->product('BUDWEISER-355-BOT', 'Cerveza Budweiser 355 ml', 'CERVEZA', 'BUDWEISER', 5.00, 'industrial_beer', 1.20, ['BOT-355ML', 'SIX-6', 'CAJ-24']),
            $this->product('STELLA-ARTOIS-330-BOT', 'Cerveza Stella Artois 330 ml', 'CERVEZA', 'STELLA-ARTOIS', 5.00, 'industrial_beer', 1.45, ['BOT-330ML', 'SIX-6', 'CAJ-24']),
            $this->product('MODELO-ESPECIAL-355-BOT', 'Cerveza Modelo Especial 355 ml', 'CERVEZA', 'MODELO', 4.40, 'industrial_beer', 1.55, ['BOT-355ML', 'SIX-6', 'CAJ-24']),

            $this->product('JW-RED-LABEL-750', 'Johnnie Walker Red Label 750 ml', 'WHISKY', 'JOHNNIE-WALKER', 40.00, 'alcoholic_beverage', 24.00, ['BOT-750ML', 'CAJ-12']),
            $this->product('JW-BLACK-LABEL-750', 'Johnnie Walker Black Label 750 ml', 'WHISKY', 'JOHNNIE-WALKER', 40.00, 'alcoholic_beverage', 52.00, ['BOT-750ML', 'CAJ-12']),
            $this->product('BUCHANANS-12-750', "Buchanan's Deluxe 12 años 750 ml", 'WHISKY', 'BUCHANANS', 40.00, 'alcoholic_beverage', 48.00, ['BOT-750ML', 'CAJ-12']),
            $this->product('OLD-PARR-12-750', 'Old Parr 12 años 750 ml', 'WHISKY', 'OLD-PARR', 40.00, 'alcoholic_beverage', 50.00, ['BOT-750ML', 'CAJ-12']),
            $this->product('CHIVAS-REGAL-12-750', 'Chivas Regal 12 años 750 ml', 'WHISKY', 'CHIVAS-REGAL', 40.00, 'alcoholic_beverage', 42.00, ['BOT-750ML', 'CAJ-12']),
            $this->product('JACK-DANIELS-OLD7-750', "Jack Daniel's Old No. 7 750 ml", 'WHISKY', 'JACK-DANIELS', 40.00, 'alcoholic_beverage', 31.00, ['BOT-750ML', 'CAJ-12']),
            $this->product('BALLANTINES-FINEST-750', "Ballantine's Finest 750 ml", 'WHISKY', 'BALLANTINES', 40.00, 'alcoholic_beverage', 22.00, ['BOT-750ML', 'CAJ-12']),

            $this->product('ABSOLUT-ORIGINAL-750', 'Vodka Absolut Original 750 ml', 'VODKA', 'ABSOLUT', 40.00, 'alcoholic_beverage', 21.00, ['BOT-750ML', 'CAJ-12']),
            $this->product('SMIRNOFF-NO21-750', 'Vodka Smirnoff No. 21 750 ml', 'VODKA', 'SMIRNOFF', 40.00, 'alcoholic_beverage', 18.00, ['BOT-750ML', 'CAJ-12']),
            $this->product('BACARDI-CARTA-BLANCA-750', 'Ron Bacardí Carta Blanca 750 ml', 'RON', 'BACARDI', 40.00, 'alcoholic_beverage', 18.00, ['BOT-750ML', 'CAJ-12']),
            $this->product('HAVANA-CLUB-3-750', 'Ron Havana Club 3 años 750 ml', 'RON', 'HAVANA-CLUB', 40.00, 'alcoholic_beverage', 20.00, ['BOT-750ML', 'CAJ-12']),
            $this->product('JOSE-CUERVO-GOLD-750', 'Tequila José Cuervo Especial Gold 750 ml', 'TEQUILA', 'JOSE-CUERVO', 38.00, 'alcoholic_beverage', 22.00, ['BOT-750ML', 'CAJ-12']),
            $this->product('PATRON-SILVER-750', 'Tequila Patrón Silver 750 ml', 'TEQUILA', 'PATRON', 40.00, 'alcoholic_beverage', 55.00, ['BOT-750ML', 'CAJ-12']),
            $this->product('TANQUERAY-LONDON-750', 'Gin Tanqueray London Dry 750 ml', 'GIN', 'TANQUERAY', 43.10, 'alcoholic_beverage', 30.00, ['BOT-750ML', 'CAJ-12']),
            $this->product('BOMBAY-SAPPHIRE-750', 'Gin Bombay Sapphire 750 ml', 'GIN', 'BOMBAY-SAPPHIRE', 40.00, 'alcoholic_beverage', 31.00, ['BOT-750ML', 'CAJ-12']),
            $this->product('JAGERMEISTER-700', 'Licor Jägermeister 700 ml', 'LICORES-CREMAS', 'JAGERMEISTER', 35.00, 'alcoholic_beverage', 25.00, ['BOT-700ML', 'CAJ-12']),
            $this->product('BAILEYS-ORIGINAL-750', 'Baileys Original Irish Cream 750 ml', 'LICORES-CREMAS', 'BAILEYS', 17.00, 'alcoholic_beverage', 24.00, ['BOT-750ML', 'CAJ-12']),
            $this->product('CASILLERO-CABERNET-750', 'Casillero del Diablo Cabernet Sauvignon 750 ml', 'VINO', 'CASILLERO-DIABLO', 13.50, 'alcoholic_beverage', 13.00, ['BOT-750ML', 'CAJ-12']),
            $this->product('CONCHA-TORO-CABERNET-750', 'Concha y Toro Reservado Cabernet Sauvignon 750 ml', 'VINO', 'CONCHA-Y-TORO', 12.50, 'alcoholic_beverage', 9.00, ['BOT-750ML', 'CAJ-12']),

            $this->product('COCA-COLA-1-5L', 'Coca-Cola 1,5 litros', 'GASEOSA', 'COCA-COLA', null, 'other', 1.75, ['BOT-1.5L', 'CAJ-12'], 'non_alcoholic_beverage'),
            $this->product('PEPSI-1-5L', 'Pepsi 1,5 litros', 'GASEOSA', 'PEPSI', null, 'other', 1.60, ['BOT-1.5L', 'CAJ-12'], 'non_alcoholic_beverage'),
            $this->product('RED-BULL-250', 'Red Bull Energy Drink 250 ml', 'ENERGIZANTE', 'RED-BULL', null, 'other', 2.20, ['LAT-250ML', 'SIX-6', 'CAJ-24'], 'non_alcoholic_beverage'),
            $this->product('MONSTER-ENERGY-473', 'Monster Energy 473 ml', 'ENERGIZANTE', 'MONSTER', null, 'other', 2.60, ['LAT-473ML', 'SIX-6', 'CAJ-24'], 'non_alcoholic_beverage'),
        ];
    }

    /** @return array<string, mixed> */
    private function product(
        string $code,
        string $name,
        string $category,
        string $brand,
        ?float $alcohol,
        string $ice,
        float $basePrice,
        array $packages,
        string $kind = 'alcoholic_beverage',
    ): array {
        return [
            ...compact('code', 'name', 'category', 'brand', 'alcohol', 'ice', 'packages', 'kind'),
            'base_price' => $basePrice,
        ];
    }

    private function packagePrice(float $basePrice, string $presentationCode, int $unitsPerPackage): float
    {
        $discount = match ($presentationCode) {
            'SIX-6' => 0.95,
            'CAJ-12' => 0.92,
            'CAJ-24' => 0.90,
            default => 1,
        };

        return round($basePrice * $unitsPerPackage * $discount, 4);
    }
}
