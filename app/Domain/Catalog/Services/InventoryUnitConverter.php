<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\Presentation;
use App\Domain\Catalog\Models\ProductPackage;
use InvalidArgumentException;

class InventoryUnitConverter
{
    public function unitsPerPackage(Presentation $presentation): int
    {
        $presentation->loadMissing('measurementUnit');

        if ($presentation->measurementUnit->dimension !== 'quantity') {
            return 1;
        }

        $quantity = (float) $presentation->quantity;

        if ($quantity < 1 || floor($quantity) !== $quantity) {
            throw new InvalidArgumentException('Una presentación por unidades debe contener un número entero positivo.');
        }

        return (int) $quantity;
    }

    public function convert(ProductPackage $package, int $packageQuantity): int
    {
        if ($packageQuantity < 0) {
            throw new InvalidArgumentException('La cantidad de empaques no puede ser negativa.');
        }

        return $packageQuantity * $package->units_per_package;
    }
}
