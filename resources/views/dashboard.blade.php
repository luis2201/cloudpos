@extends('layouts.app')

@section('title', 'Resumen')

@section('content')
    <div class="page-heading">
        <div>
            <span class="eyebrow">Resumen operativo</span>
            <h1>Buenos días, {{ Str::before(auth()->user()->name, ' ') }}</h1>
            <p>{{ $company->trade_name ?: $company->legal_name }} · {{ now()->translatedFormat('d \d\e F \d\e Y') }}.</p>
        </div>
        @can('sales.manage')<a class="button button--primary" href="{{ route('modules.show', 'ventas') }}"><x-icon name="plus" /> Nueva venta</a>@endcan
    </div>

    <section class="metrics-grid" aria-label="Indicadores de hoy">
        @foreach ($metrics as $metric)
            <x-stat-card
                :label="$metric['label']"
                :value="$metric['value']"
                :detail="$metric['detail']"
                :icon="$metric['icon']"
                :tone="$metric['tone']"
            />
        @endforeach
    </section>

    <div class="dashboard-grid">
        <section class="panel panel--accent">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Accesos rápidos</span>
                    <h2>Empieza la operación</h2>
                </div>
            </div>
            <div class="quick-actions">
                @can('cash.manage')<a href="{{ route('modules.show', 'caja') }}" class="quick-action">
                    <span class="quick-action__icon"><x-icon name="cash" size="24" /></span>
                    <span><strong>Abrir caja</strong><small>Inicia el turno de trabajo</small></span>
                    <x-icon name="arrow-right" />
                </a>@endcan
                @can('sales.manage')<a href="{{ route('modules.show', 'ventas') }}" class="quick-action">
                    <span class="quick-action__icon"><x-icon name="cart" size="24" /></span>
                    <span><strong>Registrar venta</strong><small>Agrega productos y cobra</small></span>
                    <x-icon name="arrow-right" />
                </a>@endcan
                @can('inventory.manage')<a href="{{ route('inventory.index') }}" class="quick-action">
                    <span class="quick-action__icon"><x-icon name="box" size="24" /></span>
                    <span><strong>Revisar inventario</strong><small>Consulta existencias</small></span>
                    <x-icon name="arrow-right" />
                </a>@endcan
            </div>
        </section>

        <section class="panel tax-summary">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Configuración tributaria</span>
                    <h2>{{ $shouldBreakDownVat ? 'IVA general' : 'RIMPE Negocio Popular' }}</h2>
                </div>
                <span class="status-badge status-badge--success">{{ $shouldBreakDownVat ? 'Vigente' : 'Sin desglose' }}</span>
            </div>
            @if ($currentTaxRate)
                <div class="tax-summary__rate">{{ $shouldBreakDownVat ? number_format((float) $currentTaxRate->rate, 2, ',', '.') : 'RIMPE' }}@if ($shouldBreakDownVat)<span>%</span>@endif</div>
                <p>{{ $shouldBreakDownVat ? 'Aplicable' : 'La tarifa general referencial es '.number_format((float) $currentTaxRate->rate, 2, ',', '.').'% y está vigente' }} desde el {{ $currentTaxRate->effective_from->translatedFormat('d \d\e F \d\e Y') }}.</p>
                <div class="tax-summary__reference"><x-icon name="receipt" /> {{ $currentTaxRate->legal_reference ?: 'Sin referencia legal registrada' }}</div>
            @else
                <div class="empty-state empty-state--compact">
                    <strong>Sin tarifa configurada</strong>
                    <span>Registra una vigencia antes de operar.</span>
                </div>
            @endif
            @can('taxes.manage')<a class="text-link" href="{{ route('settings.tax-rates.index') }}">Administrar vigencias <x-icon name="arrow-right" /></a>@endcan
        </section>
    </div>

    <section class="panel setup-panel">
        <div class="panel__header">
            <div>
                <span class="eyebrow">Puesta en marcha</span>
                <h2>Configuración principal completada</h2>
            </div>
            <span class="progress-label">4 de 4</span>
        </div>
        <div class="progress"><span style="width: 100%"></span></div>
        <div class="setup-steps">
            <div class="setup-step is-complete"><span><x-icon name="check" /></span><div><strong>Administrador</strong><small>{{ $userCount }} usuario(s) registrado(s)</small></div></div>
            <div class="setup-step is-complete"><span><x-icon name="check" /></span><div><strong>Empresa</strong><small>RUC {{ $company->ruc }}</small></div></div>
            <div class="setup-step is-complete"><span><x-icon name="check" /></span><div><strong>Establecimientos</strong><small>{{ $company->establishments_count }} local(es) configurado(s)</small></div></div>
            <div class="setup-step is-complete"><span><x-icon name="check" /></span><div><strong>Bodegas</strong><small>{{ $company->warehouses_count }} ubicación(es) de inventario</small></div></div>
        </div>
    </section>
@endsection
