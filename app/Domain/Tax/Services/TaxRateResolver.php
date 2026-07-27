<?php

namespace App\Domain\Tax\Services;

use App\Domain\Tax\Models\TaxRate;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class TaxRateResolver
{
    public function ivaForDate(CarbonInterface|string|null $date = null): ?TaxRate
    {
        $effectiveDate = $date instanceof CarbonInterface
            ? $date
            : Carbon::parse($date ?? now());

        return TaxRate::query()
            ->where('tax_type', TaxRate::IVA_GENERAL)
            ->whereDate('effective_from', '<=', $effectiveDate->toDateString())
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();
    }
}
