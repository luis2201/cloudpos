<?php

namespace App\Http\Controllers\Setup;

use App\Domain\Organization\Models\Company;
use App\Domain\Organization\Models\Establishment;
use App\Domain\Setup\OnboardingManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EstablishmentSetupController extends Controller
{
    public function create(OnboardingManager $onboarding): View|RedirectResponse
    {
        if (! Company::query()->exists() || Establishment::query()->exists()) {
            return to_route($onboarding->nextRouteName());
        }

        return view('setup.establishment', ['company' => Company::query()->firstOrFail()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $company = Company::query()->firstOrFail();
        abort_if(Establishment::query()->exists(), 409, 'El establecimiento matriz ya fue configurado.');

        $validated = $request->validate([
            'code' => ['required', 'digits:3'],
            'name' => ['required', 'string', 'max:150'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $company->establishments()->create([...$validated, 'is_main' => true, 'is_active' => true]);

        return to_route('setup.warehouse.create')
            ->with('success', 'Establecimiento matriz creado. Falta registrar su bodega principal.');
    }
}
