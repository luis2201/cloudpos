<?php

namespace App\Http\Controllers\Settings;

use App\Domain\Tax\Models\TaxRate;
use App\Domain\Tax\Services\TaxRateResolver;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TaxRateController extends Controller
{
    public function index(Request $request, TaxRateResolver $resolver): View
    {
        $currentRate = $resolver->ivaForDate();
        $status = $request->string('status')->toString();
        $search = trim($request->string('search')->toString());
        $sort = in_array($request->string('sort')->toString(), ['effective_from', 'rate', 'created_at'], true)
            ? $request->string('sort')->toString()
            : 'effective_from';
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        $query = TaxRate::query()->where('tax_type', TaxRate::IVA_GENERAL);

        $query->when($search !== '', function ($query) use ($search): void {
            $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('legal_reference', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        });

        match ($status) {
            'vigente' => $currentRate ? $query->whereKey($currentRate->getKey()) : $query->whereRaw('1 = 0'),
            'historico' => $query
                ->whereDate('effective_from', '<=', today())
                ->when($currentRate, fn ($query) => $query->whereKeyNot($currentRate->getKey())),
            'programado' => $query->whereDate('effective_from', '>', today()),
            default => null,
        };

        return view('settings.tax-rates.index', [
            'currentRate' => $currentRate,
            'rates' => $query->orderBy($sort, $direction)->paginate(10)->withQueryString(),
            'filters' => compact('search', 'status', 'sort', 'direction'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
            'effective_from' => [
                'required',
                'date_format:Y-m-d',
            ],
            'legal_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'effective_from.unique' => 'Ya existe una tarifa de IVA con esa fecha de vigencia.',
            'rate.decimal' => 'La tarifa admite hasta dos decimales.',
        ]);

        $dateAlreadyExists = TaxRate::query()
            ->where('tax_type', TaxRate::IVA_GENERAL)
            ->whereDate('effective_from', $validated['effective_from'])
            ->exists();

        if ($dateAlreadyExists) {
            throw ValidationException::withMessages([
                'effective_from' => 'Ya existe una tarifa de IVA con esa fecha de vigencia.',
            ]);
        }

        TaxRate::query()->create([
            ...$validated,
            'tax_type' => TaxRate::IVA_GENERAL,
        ]);

        return to_route('settings.tax-rates.index')
            ->with('success', 'La nueva tarifa de IVA fue programada correctamente.');
    }
}
