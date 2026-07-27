<?php

namespace App\Http\Controllers\Setup;

use App\Domain\Organization\Models\Company;
use App\Domain\Setup\OnboardingManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CompanySetupController extends Controller
{
    public function create(OnboardingManager $onboarding): View|RedirectResponse
    {
        if (Company::query()->exists()) {
            return to_route($onboarding->nextRouteName());
        }

        return view('setup.company');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_if(Company::query()->exists(), 409, 'La empresa principal ya fue configurada.');

        DB::transaction(function () use ($request): void {
            $company = Company::query()->create($this->validatedData($request));

            $company->taxProfiles()->create([
                'tax_regime' => 'rimpe_popular_business',
                'taxpayer_type' => 'natural_person',
                'accounting_required' => false,
                'is_franchise' => $request->boolean('is_franchise'),
                'effective_from' => now('America/Guayaquil')->startOfYear()->toDateString(),
                'notes' => 'Perfil tributario inicial de la instalación.',
                'created_by' => $request->user()->getKey(),
            ]);
        });

        return to_route('setup.establishment.create')
            ->with('success', 'Empresa registrada. Configura ahora su establecimiento matriz.');
    }

    /** @return array<string, mixed> */
    private function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'ruc' => ['required', 'digits:13', 'unique:companies,ruc'],
            'legal_name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:1000'],
        ]);

        return [
            ...$validated,
            'taxpayer_type' => 'natural_person',
            'rimpe_category' => 'popular_business',
            'ice_taxpayer_role' => 'retailer',
            'is_franchise' => $request->boolean('is_franchise'),
            'accounting_required' => false,
            'withholding_agent' => false,
            'special_taxpayer' => false,
            'withholding_agent_resolution' => null,
            'special_taxpayer_resolution' => null,
            'currency' => 'USD',
            'timezone' => 'America/Guayaquil',
        ];
    }
}
