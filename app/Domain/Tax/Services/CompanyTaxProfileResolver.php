<?php

namespace App\Domain\Tax\Services;

use App\Domain\Organization\Models\Company;
use App\Domain\Organization\Models\CompanyTaxProfile;
use DateTimeInterface;
use Illuminate\Support\Carbon;

class CompanyTaxProfileResolver
{
    public function forDate(Company $company, DateTimeInterface|string|null $date = null): ?CompanyTaxProfile
    {
        $businessDate = $date instanceof DateTimeInterface
            ? Carbon::instance($date)
            : Carbon::parse($date ?? now('America/Guayaquil'));

        return $company->taxProfiles()
            ->whereDate('effective_from', '<=', $businessDate->toDateString())
            ->latest('effective_from')
            ->first();
    }
}
