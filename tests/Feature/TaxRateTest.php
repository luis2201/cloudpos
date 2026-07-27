<?php

namespace Tests\Feature;

use App\Domain\Tax\Models\TaxRate;
use App\Domain\Tax\Services\TaxRateResolver;
use Database\Seeders\TaxRateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TaxRateTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_the_rate_effective_on_a_given_date(): void
    {
        TaxRate::query()->create([
            'tax_type' => TaxRate::IVA_GENERAL,
            'name' => 'IVA general',
            'rate' => 12,
            'effective_from' => '2020-01-01',
        ]);
        TaxRate::query()->create([
            'tax_type' => TaxRate::IVA_GENERAL,
            'name' => 'IVA general',
            'rate' => 15,
            'effective_from' => '2024-04-01',
        ]);

        $resolver = app(TaxRateResolver::class);

        $this->assertSame('12.00', $resolver->ivaForDate('2024-03-31')->rate);
        $this->assertSame('15.00', $resolver->ivaForDate('2024-04-01')->rate);
    }

    public function test_an_administrator_can_schedule_a_new_tax_rate(): void
    {
        $this->signInConfiguredAdministrator();

        $response = $this->post(route('settings.tax-rates.store'), [
            'name' => 'IVA general',
            'rate' => '14.50',
            'effective_from' => '2027-01-01',
            'legal_reference' => 'Decreto de prueba',
            'notes' => 'Vigencia futura',
        ]);

        $response->assertRedirect(route('settings.tax-rates.index'))
            ->assertSessionHas('success');

        $createdRate = TaxRate::query()
            ->where('tax_type', TaxRate::IVA_GENERAL)
            ->whereDate('effective_from', '2027-01-01')
            ->firstOrFail();

        $this->assertSame('14.50', $createdRate->rate);
        $this->assertSame('2027-01-01', $createdRate->effective_from->toDateString());
    }

    public function test_two_rates_cannot_start_on_the_same_date(): void
    {
        $this->signInConfiguredAdministrator();
        $this->seed(TaxRateSeeder::class);

        $this->post(route('settings.tax-rates.store'), [
            'name' => 'IVA general',
            'rate' => '13.00',
            'effective_from' => '2024-04-01',
        ])->assertSessionHasErrors('effective_from');
    }

    public function test_future_rates_are_shown_as_scheduled(): void
    {
        Carbon::setTestNow('2026-07-27 10:00:00');
        $this->signInConfiguredAdministrator();
        $this->seed(TaxRateSeeder::class);
        TaxRate::query()->create([
            'tax_type' => TaxRate::IVA_GENERAL,
            'name' => 'IVA futura',
            'rate' => 13,
            'effective_from' => '2027-01-01',
        ]);

        $this->get(route('settings.tax-rates.index', ['status' => 'programado']))
            ->assertOk()
            ->assertSee('IVA futura')
            ->assertSee('Programado');

        Carbon::setTestNow();
    }
}
