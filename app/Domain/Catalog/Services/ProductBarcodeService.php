<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\ProductPackage;
use Illuminate\Validation\ValidationException;
use Picqer\Barcode\BarcodeGeneratorSVG;

class ProductBarcodeService
{
    private const INTERNAL_PREFIX = 'CP';

    public function ensureInternal(ProductPackage $package): string
    {
        if (filled($package->internal_barcode)) {
            return $package->internal_barcode;
        }

        $package->forceFill([
            'internal_barcode' => self::INTERNAL_PREFIX.str_pad((string) $package->getKey(), 10, '0', STR_PAD_LEFT),
        ])->save();

        return $package->internal_barcode;
    }

    public function normalizeManufacturer(?string $barcode): ?string
    {
        $barcode = trim((string) $barcode);

        if ($barcode === '') {
            return null;
        }

        $withoutSpaces = preg_replace('/\s+/', '', $barcode);

        return ctype_digit($withoutSpaces) ? $withoutSpaces : mb_strtoupper($barcode);
    }

    public function validateManufacturer(?string $barcode): void
    {
        if ($barcode === null || ! ctype_digit($barcode) || ! in_array(strlen($barcode), [8, 12, 13, 14], true)) {
            return;
        }

        if (! $this->hasValidGtinCheckDigit($barcode)) {
            throw ValidationException::withMessages([
                'barcode' => 'El GTIN/EAN/UPC no supera la validación de su dígito verificador.',
            ]);
        }
    }

    public function svg(string $barcode): string
    {
        $generator = new BarcodeGeneratorSVG;

        return $generator->getBarcode($barcode, $generator::TYPE_CODE_128, 2, 64);
    }

    public function hasValidGtinCheckDigit(string $barcode): bool
    {
        if (! ctype_digit($barcode) || ! in_array(strlen($barcode), [8, 12, 13, 14], true)) {
            return false;
        }

        $digits = array_map('intval', str_split(substr($barcode, 0, -1)));
        $sum = 0;
        $weight = 3;

        for ($index = count($digits) - 1; $index >= 0; $index--) {
            $sum += $digits[$index] * $weight;
            $weight = $weight === 3 ? 1 : 3;
        }

        $expected = (10 - ($sum % 10)) % 10;

        return $expected === (int) substr($barcode, -1);
    }
}
