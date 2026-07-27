<?php

namespace App\Domain\Tax\Services;

use App\Domain\Organization\Models\Company;

class CompanyTaxPolicy
{
    public function __construct(private readonly CompanyTaxProfileResolver $profiles) {}

    public function shouldBreakDownVat(Company $company, \DateTimeInterface|string|null $date = null): bool
    {
        $profile = $this->profiles->forDate($company, $date);

        return $profile
            ? ! $profile->isRimpePopularBusiness()
            : ! $company->isRimpePopularBusiness();
    }

    public function shouldCalculateProductIce(Company $company): bool
    {
        return $company->isDirectIceTaxpayer();
    }

    public function appliesPlasticBagIce(Company $company, \DateTimeInterface|string|null $date = null): bool
    {
        $profile = $this->profiles->forDate($company, $date);
        $isFranchise = $profile?->is_franchise ?? $company->is_franchise;
        $taxpayerType = $profile?->taxpayer_type ?? $company->taxpayer_type;
        $accountingRequired = $profile?->accounting_required ?? $company->accounting_required;

        if ($isFranchise) {
            return true;
        }

        $isEstablishmentOfCommerce = $taxpayerType !== 'natural_person'
            || $accountingRequired;

        if (! $isEstablishmentOfCommerce) {
            return false;
        }

        return $company->establishments()
            ->where('is_active', true)
            ->count() >= 3;
    }
}
