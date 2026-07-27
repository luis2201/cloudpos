<?php

namespace App\Http\Controllers\Settings;

use App\Domain\Organization\Models\Company;
use App\Domain\Organization\Models\CompanyTaxProfile;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CompanyTaxProfileController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $company = Company::query()->firstOrFail();
        $validated = $request->validate([
            'tax_regime' => ['required', Rule::in(array_keys(CompanyTaxProfile::REGIMES))],
            'effective_from' => ['required', 'date', 'after_or_equal:today'],
            'legal_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $effectiveFrom = Carbon::parse($validated['effective_from'])->startOfDay();
        $latestEffectiveFrom = $company->taxProfiles()->max('effective_from');

        if ($latestEffectiveFrom && $effectiveFrom->lte(Carbon::parse($latestEffectiveFrom))) {
            throw ValidationException::withMessages([
                'effective_from' => 'La nueva vigencia debe ser posterior al último perfil tributario registrado.',
            ]);
        }

        $accountingRequired = $request->boolean('accounting_required');

        if ($validated['tax_regime'] === 'rimpe_popular_business' && $company->taxpayer_type !== 'natural_person') {
            throw ValidationException::withMessages([
                'tax_regime' => 'RIMPE Negocio Popular corresponde únicamente a una persona natural.',
            ]);
        }

        if ($validated['tax_regime'] === 'rimpe_popular_business' && $accountingRequired) {
            throw ValidationException::withMessages([
                'accounting_required' => 'RIMPE Negocio Popular no es compatible con obligación de llevar contabilidad.',
            ]);
        }

        $profile = $company->taxProfiles()->create([
            ...$validated,
            'taxpayer_type' => $company->taxpayer_type,
            'accounting_required' => $accountingRequired,
            'is_franchise' => $request->boolean('is_franchise'),
            'created_by' => $request->user()->getKey(),
        ]);

        if ($profile->effective_from->isToday()) {
            $company->update([
                'rimpe_category' => match ($profile->tax_regime) {
                    'rimpe_popular_business' => 'popular_business',
                    'rimpe_entrepreneur' => 'entrepreneur',
                    default => null,
                },
                'accounting_required' => $profile->accounting_required,
                'is_franchise' => $profile->is_franchise,
            ]);
        }

        return back()->with('success', 'La nueva vigencia tributaria fue programada correctamente.');
    }
}
