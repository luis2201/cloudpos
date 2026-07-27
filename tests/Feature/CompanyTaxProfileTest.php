<?php

namespace Tests\Feature;

use App\Domain\Organization\Models\Company;
use App\Domain\Tax\Services\CompanyTaxPolicy;
use App\Domain\Tax\Services\CompanyTaxProfileResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CompanyTaxProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_entrepreneur_transition_is_scheduled_without_changing_the_previous_period(): void
    {
        Carbon::setTestNow('2026-07-27 10:00:00');
        $this->signInConfiguredAdministrator();
        $company = Company::query()->firstOrFail();

        $this->post(route('settings.company-tax-profiles.store'), [
            'tax_regime' => 'rimpe_entrepreneur',
            'effective_from' => '2027-01-01',
            'legal_reference' => 'Actualización del RUC',
        ])->assertSessionHas('success');

        $resolver = app(CompanyTaxProfileResolver::class);
        $taxPolicy = app(CompanyTaxPolicy::class);

        $this->assertSame('rimpe_popular_business', $resolver->forDate($company, '2026-12-31')->tax_regime);
        $this->assertSame('rimpe_entrepreneur', $resolver->forDate($company, '2027-01-01')->tax_regime);
        $this->assertFalse($taxPolicy->shouldBreakDownVat($company, '2026-12-31'));
        $this->assertTrue($taxPolicy->shouldBreakDownVat($company, '2027-01-01'));
        $this->assertSame('popular_business', $company->fresh()->rimpe_category);

        $this->get(route('settings.company.edit'))
            ->assertOk()
            ->assertSee('RIMPE Emprendedor')
            ->assertSee('Programado')
            ->assertSee('Actualización del RUC');

        Carbon::setTestNow();
    }

    public function test_a_new_profile_must_start_after_the_latest_registered_vigency(): void
    {
        Carbon::setTestNow('2026-07-27 10:00:00');
        $this->signInConfiguredAdministrator();

        $this->post(route('settings.company-tax-profiles.store'), [
            'tax_regime' => 'rimpe_entrepreneur',
            'effective_from' => '2027-01-01',
        ])->assertSessionHas('success');

        $this->post(route('settings.company-tax-profiles.store'), [
            'tax_regime' => 'general',
            'effective_from' => '2026-12-01',
        ])->assertSessionHasErrors('effective_from');

        $this->assertSame(2, Company::query()->firstOrFail()->taxProfiles()->count());

        Carbon::setTestNow();
    }
}
