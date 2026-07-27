<?php

namespace App\Http\Controllers\Settings;

use App\Domain\Organization\Models\Company;
use App\Domain\Organization\Models\CompanyTaxProfile;
use App\Domain\Tax\Services\CompanyTaxPolicy;
use App\Domain\Tax\Services\CompanyTaxProfileResolver;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function edit(CompanyTaxPolicy $taxPolicy, CompanyTaxProfileResolver $taxProfiles): View
    {
        $company = Company::query()->firstOrFail();
        $currentTaxProfile = $taxProfiles->forDate($company);
        $profiles = $company->taxProfiles()->with('creator')->latest('effective_from')->get();
        $minimumTaxProfileDate = now('America/Guayaquil')->startOfDay();
        $latestProfile = $profiles->first();

        if ($latestProfile && $latestProfile->effective_from->gte($minimumTaxProfileDate)) {
            $minimumTaxProfileDate = $latestProfile->effective_from->copy()->addDay();
        }

        $suggestedTaxProfileDate = now('America/Guayaquil')->addYear()->startOfYear();

        if ($suggestedTaxProfileDate->lt($minimumTaxProfileDate)) {
            $suggestedTaxProfileDate = $minimumTaxProfileDate->copy();
        }

        return view('settings.company.edit', [
            'company' => $company,
            'currentTaxProfile' => $currentTaxProfile,
            'plasticBagIceApplies' => $taxPolicy->appliesPlasticBagIce($company),
            'minimumTaxProfileDate' => $minimumTaxProfileDate,
            'suggestedTaxProfileDate' => $suggestedTaxProfileDate,
            'taxProfiles' => $profiles,
            'taxRegimes' => CompanyTaxProfile::REGIMES,
        ]);
    }

    public function update(Request $request, CompanyTaxProfileResolver $taxProfiles): RedirectResponse
    {
        $company = Company::query()->firstOrFail();
        $currentTaxProfile = $taxProfiles->forDate($company);
        $validated = $request->validate([
            'ruc' => ['required', 'digits:13', Rule::unique('companies', 'ruc')->ignore($company)],
            'legal_name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:1000'],
            'withholding_agent_resolution' => ['nullable', 'string', 'max:255'],
            'special_taxpayer_resolution' => ['nullable', 'string', 'max:255'],
        ]);

        if ($currentTaxProfile?->isRimpePopularBusiness()) {
            if ($request->boolean('withholding_agent')) {
                throw ValidationException::withMessages([
                    'withholding_agent' => 'Un RIMPE Negocio Popular no actúa como agente de retención.',
                ]);
            }

            if ($request->boolean('special_taxpayer')) {
                throw ValidationException::withMessages([
                    'special_taxpayer' => 'La condición de RIMPE Negocio Popular no es compatible con contribuyente especial.',
                ]);
            }
        }

        $company->update([
            ...$validated,
            'withholding_agent' => $request->boolean('withholding_agent'),
            'special_taxpayer' => $request->boolean('special_taxpayer'),
            'withholding_agent_resolution' => $request->boolean('withholding_agent') ? ($validated['withholding_agent_resolution'] ?? null) : null,
            'special_taxpayer_resolution' => $request->boolean('special_taxpayer') ? ($validated['special_taxpayer_resolution'] ?? null) : null,
        ]);

        return back()->with('success', 'Los datos de la empresa fueron actualizados.');
    }
}
