@extends('layouts.app')

@section('title', 'Impuestos')

@php
    $sortUrl = function (string $column) use ($filters): string {
        $nextDirection = $filters['sort'] === $column && $filters['direction'] === 'asc' ? 'desc' : 'asc';

        return route('settings.tax-rates.index', array_filter([
            'search' => $filters['search'],
            'status' => $filters['status'],
            'sort' => $column,
            'direction' => $nextDirection,
        ]));
    };
@endphp

@section('content')
    <div class="page-heading">
        <div>
            <span class="eyebrow">Administración</span>
            <h1>Impuestos y vigencias</h1>
            <p>Consulta el histórico y programa cambios sin alterar operaciones anteriores.</p>
        </div>
        <button class="button button--primary" type="button" data-dialog-open="tax-rate-dialog">
            <x-icon name="plus" /> Nueva tarifa
        </button>
    </div>

    <section class="tax-current-card">
        <div class="tax-current-card__icon"><x-icon name="tax" size="28" /></div>
        <div class="tax-current-card__content">
            <span>Tarifa general de IVA aplicada hoy</span>
            @if ($currentRate)
                <strong>{{ number_format((float) $currentRate->rate, 2, ',', '.') }}%</strong>
                <small>Vigente desde {{ $currentRate->effective_from->translatedFormat('d/m/Y') }} · {{ $currentRate->legal_reference ?: 'Referencia pendiente' }}</small>
            @else
                <strong>Sin configurar</strong>
                <small>Registra una tarifa antes de iniciar ventas.</small>
            @endif
        </div>
        <div class="tax-current-card__notice">
            <x-icon name="clock" />
            <span>Las ventas futuras resolverán la tarifa correspondiente a su fecha.</span>
        </div>
    </section>

    <section class="panel table-panel">
        <div class="panel__header panel__header--table">
            <div>
                <span class="eyebrow">Historial fiscal</span>
                <h2>Tarifas registradas</h2>
            </div>
            <span class="record-count">{{ $rates->total() }} {{ $rates->total() === 1 ? 'registro' : 'registros' }}</span>
        </div>

        <form class="filters" method="GET" action="{{ route('settings.tax-rates.index') }}">
            <label class="search-field">
                <span class="sr-only">Buscar tarifa</span>
                <x-icon name="search" />
                <input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Buscar por nombre o referencia…">
            </label>
            <label class="select-field">
                <x-icon name="filter" />
                <span class="sr-only">Filtrar por estado</span>
                <select name="status">
                    <option value="">Todos los estados</option>
                    <option value="vigente" @selected($filters['status'] === 'vigente')>Vigente</option>
                    <option value="programado" @selected($filters['status'] === 'programado')>Programado</option>
                    <option value="historico" @selected($filters['status'] === 'historico')>Histórico</option>
                </select>
            </label>
            <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
            <input type="hidden" name="direction" value="{{ $filters['direction'] }}">
            <button class="button button--secondary" type="submit">Aplicar filtros</button>
            @if ($filters['search'] !== '' || $filters['status'] !== '')
                <a class="button button--ghost" href="{{ route('settings.tax-rates.index') }}">Limpiar</a>
            @endif
        </form>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Impuesto</th>
                        <th>
                            <a href="{{ $sortUrl('rate') }}">Tarifa <x-icon name="sort" size="15" /></a>
                        </th>
                        <th>
                            <a href="{{ $sortUrl('effective_from') }}">Vigencia desde <x-icon name="sort" size="15" /></a>
                        </th>
                        <th>Referencia legal</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rates as $rate)
                        @php
                            $isCurrent = $currentRate?->is($rate) ?? false;
                            $isFuture = $rate->effective_from->isFuture();
                        @endphp
                        <tr>
                            <td data-label="Impuesto">
                                <div class="table-primary"><span class="table-primary__icon"><x-icon name="tax" /></span><span><strong>{{ $rate->name }}</strong><small>{{ $rate->tax_type }}</small></span></div>
                            </td>
                            <td data-label="Tarifa"><strong class="rate-value">{{ number_format((float) $rate->rate, 2, ',', '.') }}%</strong></td>
                            <td data-label="Vigencia desde"><span class="date-value"><x-icon name="calendar" size="17" />{{ $rate->effective_from->format('d/m/Y') }}</span></td>
                            <td data-label="Referencia legal">{{ $rate->legal_reference ?: '—' }}</td>
                            <td data-label="Estado">
                                @if ($isCurrent)
                                    <span class="status-badge status-badge--success">Vigente</span>
                                @elseif ($isFuture)
                                    <span class="status-badge status-badge--info">Programado</span>
                                @else
                                    <span class="status-badge status-badge--muted">Histórico</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <span><x-icon name="search" size="26" /></span>
                                    <strong>No encontramos tarifas</strong>
                                    <small>Ajusta los filtros o registra una nueva vigencia.</small>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $rates->onEachSide(1)->links('components.pagination') }}
    </section>
@endsection

@push('dialogs')
    <dialog id="tax-rate-dialog" class="dialog dialog--form" @if ($errors->any()) data-auto-open @endif>
        <form method="POST" action="{{ route('settings.tax-rates.store') }}">
            @csrf
            <div class="dialog__header">
                <div>
                    <span class="dialog__icon"><x-icon name="tax" size="24" /></span>
                    <div><span class="eyebrow">Nueva vigencia</span><h2>Programar tarifa de IVA</h2></div>
                </div>
                <button class="icon-button" type="button" data-dialog-close aria-label="Cerrar"><x-icon name="close" /></button>
            </div>

            <div class="dialog__body">
                @if ($errors->any())
                    <div class="form-alert" role="alert">
                        <strong>Revisa los datos ingresados</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="form-grid">
                    <label class="field field--wide">
                        <span>Nombre de la tarifa <b>*</b></span>
                        <input type="text" name="name" value="{{ old('name', 'IVA general') }}" maxlength="100" required>
                        @error('name')<small class="field__error">{{ $message }}</small>@enderror
                    </label>
                    <label class="field">
                        <span>Porcentaje <b>*</b></span>
                        <div class="input-suffix"><input type="number" name="rate" value="{{ old('rate') }}" min="0" max="100" step="0.01" placeholder="15.00" required><span>%</span></div>
                        @error('rate')<small class="field__error">{{ $message }}</small>@enderror
                    </label>
                    <label class="field">
                        <span>Vigente desde <b>*</b></span>
                        <input type="date" name="effective_from" value="{{ old('effective_from') }}" required>
                        @error('effective_from')<small class="field__error">{{ $message }}</small>@enderror
                    </label>
                    <label class="field field--wide">
                        <span>Referencia legal</span>
                        <input type="text" name="legal_reference" value="{{ old('legal_reference') }}" maxlength="255" placeholder="Ej. Decreto Ejecutivo No. 000">
                        @error('legal_reference')<small class="field__error">{{ $message }}</small>@enderror
                    </label>
                    <label class="field field--wide">
                        <span>Notas</span>
                        <textarea name="notes" rows="3" maxlength="1000" placeholder="Detalle adicional para auditoría…">{{ old('notes') }}</textarea>
                        @error('notes')<small class="field__error">{{ $message }}</small>@enderror
                    </label>
                </div>
                <div class="info-box"><x-icon name="clock" /><span>La nueva tarifa se aplicará automáticamente desde esta fecha. Los registros históricos conservarán su cálculo original.</span></div>
            </div>

            <div class="dialog__footer">
                <button class="button button--ghost" type="button" data-dialog-close>Cancelar</button>
                <button class="button button--primary" type="submit">Guardar vigencia</button>
            </div>
        </form>
    </dialog>
@endpush
