<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Organization\Models\Company;
use App\Domain\Organization\Models\Establishment;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EstablishmentController extends Controller
{
    public function index(): View
    {
        $company = Company::query()->firstOrFail();

        return view('organization.establishments.index', [
            'company' => $company,
            'establishments' => $company->establishments()->withCount('warehouses')->orderByDesc('is_main')->orderBy('code')->paginate(10),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $company = Company::query()->firstOrFail();
        $validated = $this->validateEstablishment($request, $company);

        DB::transaction(function () use ($company, $request, $validated): void {
            if ($request->boolean('is_main')) {
                $company->establishments()->update(['is_main' => false]);
            }

            $company->establishments()->create([
                ...$validated,
                'is_main' => $request->boolean('is_main'),
                'is_active' => $request->boolean('is_active', true),
            ]);
        });

        return back()->with('success', 'Establecimiento registrado correctamente.');
    }

    public function update(Request $request, Establishment $establishment): RedirectResponse
    {
        $company = Company::query()->firstOrFail();
        abort_unless($establishment->company_id === $company->getKey(), 404);
        $validated = $this->validateEstablishment($request, $company, $establishment);

        DB::transaction(function () use ($company, $establishment, $request, $validated): void {
            if ($request->boolean('is_main')) {
                $company->establishments()->whereKeyNot($establishment->getKey())->update(['is_main' => false]);
            }

            $establishment->update([
                ...$validated,
                'is_main' => $request->boolean('is_main'),
                'is_active' => $request->boolean('is_active'),
            ]);
        });

        return back()->with('success', 'Establecimiento actualizado correctamente.');
    }

    /** @return array<string, mixed> */
    private function validateEstablishment(Request $request, Company $company, ?Establishment $establishment = null): array
    {
        return $request->validate([
            'code' => [
                'required',
                'digits:3',
                Rule::unique('establishments', 'code')
                    ->where(fn ($query) => $query->where('company_id', $company->getKey()))
                    ->ignore($establishment),
            ],
            'name' => ['required', 'string', 'max:150'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);
    }
}
