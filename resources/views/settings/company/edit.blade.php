@extends('layouts.app')

@section('title', 'Datos de la empresa')

@php
    $taxpayerLabels = [
        'natural_person' => 'Persona natural',
        'private_company' => 'Sociedad privada',
        'public_company' => 'Sociedad pública',
    ];
@endphp

@section('content')
    <div class="page-heading">
        <div><span class="eyebrow">Configuración principal</span><h1>Datos de la empresa</h1><p>Identidad comercial y línea de tiempo tributaria del negocio.</p></div>
        <span class="status-badge status-badge--success">Configurada</span>
    </div>

    <form method="POST" action="{{ route('settings.company.update') }}" class="panel settings-form">
        @csrf
        @method('PUT')
        <div class="panel__header"><div><span class="eyebrow">Identificación</span><h2>{{ $company->trade_name ?: $company->legal_name }}</h2></div><span class="company-ruc">RUC {{ $company->ruc }}</span></div>
        <div class="form-grid form-grid--three">
            <label class="field"><span>RUC <b>*</b></span><input name="ruc" value="{{ old('ruc', $company->ruc) }}" inputmode="numeric" minlength="13" maxlength="13" required></label>
            <div class="field"><span>Tipo de contribuyente</span><div class="input-readonly">{{ $taxpayerLabels[$company->taxpayer_type] ?? $company->taxpayer_type }}</div></div>
            <div class="field"><span>Perfil vigente</span><div class="input-readonly">{{ $currentTaxProfile ? $taxRegimes[$currentTaxProfile->tax_regime] : 'Sin perfil vigente' }}</div></div>
            <label class="field field--wide"><span>Razón social <b>*</b></span><input name="legal_name" value="{{ old('legal_name', $company->legal_name) }}" required></label>
            <label class="field field--wide"><span>Nombre comercial</span><input name="trade_name" value="{{ old('trade_name', $company->trade_name) }}"></label>
            <label class="field"><span>Correo</span><input name="email" type="email" value="{{ old('email', $company->email) }}"></label>
            <label class="field"><span>Teléfono</span><input name="phone" value="{{ old('phone', $company->phone) }}"></label>
            <label class="field field--wide"><span>Dirección matriz <b>*</b></span><textarea name="address" required>{{ old('address', $company->address) }}</textarea></label>
        </div>

        <div class="section-divider"><span>Designaciones tributarias</span></div>
        <div class="form-grid form-grid--three">
            <label class="check-card"><input name="withholding_agent" type="checkbox" value="1" @checked(old('withholding_agent', $company->withholding_agent))><span><strong>Agente de retención</strong><small>Designación vigente del SRI</small></span></label>
            <label class="check-card"><input name="special_taxpayer" type="checkbox" value="1" @checked(old('special_taxpayer', $company->special_taxpayer))><span><strong>Contribuyente especial</strong><small>Designación vigente del SRI</small></span></label>
            <label class="field"><span>Resolución de agente de retención</span><input name="withholding_agent_resolution" value="{{ old('withholding_agent_resolution', $company->withholding_agent_resolution) }}"></label>
            <label class="field"><span>Resolución de contribuyente especial</span><input name="special_taxpayer_resolution" value="{{ old('special_taxpayer_resolution', $company->special_taxpayer_resolution) }}"></label>
        </div>
        <div class="form-actions"><span>Moneda: USD · Zona horaria: America/Guayaquil</span><button class="button button--primary" type="submit"><x-icon name="check" /> Guardar datos</button></div>
    </form>

    <section class="panel table-panel tax-profile-panel">
        <div class="panel__header panel__header--table">
            <div><span class="eyebrow">Vigencias históricas</span><h2>Perfiles tributarios</h2></div>
            @can('taxes.manage')<button class="button button--primary" type="button" data-dialog-open="new-tax-profile"><x-icon name="calendar" /> Programar cambio</button>@endcan
        </div>

        @if ($currentTaxProfile)
            <div class="product-detail-grid tax-profile-summary">
                <article class="detail-card"><span class="detail-card__icon"><x-icon name="tax" /></span><div><small>Régimen vigente</small><strong>{{ $taxRegimes[$currentTaxProfile->tax_regime] }}</strong><p>Desde {{ $currentTaxProfile->effective_from->translatedFormat('d \d\e F \d\e Y') }}</p></div></article>
                <article class="detail-card"><span class="detail-card__icon"><x-icon name="receipt" /></span><div><small>Tratamiento de IVA</small><strong>{{ $currentTaxProfile->isRimpePopularBusiness() ? 'Sin desglose' : 'IVA desglosado' }}</strong><p>La tarifa del producto se resolverá por la fecha de venta.</p></div></article>
                <article class="detail-card"><span class="detail-card__icon"><x-icon name="package" /></span><div><small>ICE a fundas plásticas</small><strong>{{ $plasticBagIceApplies ? 'Aplica' : 'No aplica' }}</strong><p>{{ $currentTaxProfile->is_franchise ? 'Franquicia' : 'Negocio independiente' }} · {{ $currentTaxProfile->accounting_required ? 'Con' : 'Sin' }} obligación contable</p></div></article>
            </div>
        @endif

        <div class="info-box"><x-icon name="shield" /><span>Los perfiles anteriores no se editan. Una nueva condición se programa con su fecha de vigencia para conservar el tratamiento aplicado a cada venta histórica.</span></div>

        <div class="table-wrap"><table class="data-table"><thead><tr><th>Régimen</th><th>Vigente desde</th><th>IVA en venta</th><th>Condiciones</th><th>Estado</th><th>Referencia</th></tr></thead><tbody>
            @foreach ($taxProfiles as $profile)
                @php($isScheduled = $profile->effective_from->isFuture())
                <tr>
                    <td data-label="Régimen"><strong>{{ $taxRegimes[$profile->tax_regime] }}</strong><small class="price-note">{{ $taxpayerLabels[$profile->taxpayer_type] ?? $profile->taxpayer_type }}</small></td>
                    <td data-label="Vigente desde">{{ $profile->effective_from->format('d/m/Y') }}</td>
                    <td data-label="IVA en venta">{{ $profile->isRimpePopularBusiness() ? 'No se desglosa' : 'Se desglosa según producto' }}</td>
                    <td data-label="Condiciones">{{ $profile->accounting_required ? 'Obligado a contabilidad' : 'No obligado' }} · {{ $profile->is_franchise ? 'Franquicia' : 'Independiente' }}</td>
                    <td data-label="Estado"><span class="status-badge {{ $isScheduled ? 'status-badge--warning' : ($currentTaxProfile?->is($profile) ? 'status-badge--success' : 'status-badge--muted') }}">{{ $isScheduled ? 'Programado' : ($currentTaxProfile?->is($profile) ? 'Vigente' : 'Histórico') }}</span></td>
                    <td data-label="Referencia">{{ $profile->legal_reference ?: 'Sin referencia' }}@if ($profile->creator)<small class="price-note">Registrado por {{ $profile->creator->name }}</small>@endif</td>
                </tr>
            @endforeach
        </tbody></table></div>
    </section>
@endsection

@can('taxes.manage')
    @push('dialogs')
        <dialog class="dialog dialog--wide" id="new-tax-profile">
            <form method="POST" action="{{ route('settings.company-tax-profiles.store') }}">@csrf
                <div class="dialog__header"><div><span class="dialog__icon"><x-icon name="calendar" /></span><span><span class="eyebrow">Nueva vigencia</span><h2>Programar perfil tributario</h2></span></div><button class="icon-button" type="button" data-dialog-close><x-icon name="close" /></button></div>
                <div class="dialog__body">
                    <div class="form-grid">
                        <label class="field"><span>Nuevo régimen <b>*</b></span><select name="tax_regime" required>@foreach ($taxRegimes as $value => $label)<option value="{{ $value }}" @selected(old('tax_regime', 'rimpe_entrepreneur') === $value)>{{ $label }}</option>@endforeach</select></label>
                        <label class="field"><span>Vigente desde <b>*</b></span><input name="effective_from" type="date" min="{{ $minimumTaxProfileDate->toDateString() }}" value="{{ old('effective_from', $suggestedTaxProfileDate->toDateString()) }}" required></label>
                        <label class="check-card"><input name="accounting_required" type="checkbox" value="1" @checked(old('accounting_required'))><span><strong>Obligado a llevar contabilidad</strong><small>Según la condición vigente en el RUC desde esa fecha</small></span></label>
                        <label class="check-card"><input name="is_franchise" type="checkbox" value="1" @checked(old('is_franchise', $currentTaxProfile?->is_franchise))><span><strong>Opera como franquicia</strong><small>Incide en el ICE de fundas plásticas</small></span></label>
                        <label class="field field--wide"><span>Referencia legal o del RUC</span><input name="legal_reference" value="{{ old('legal_reference') }}" maxlength="255" placeholder="Resolución, actualización del RUC u otro respaldo"></label>
                        <label class="field field--wide"><span>Notas</span><textarea name="notes" maxlength="1000">{{ old('notes') }}</textarea></label>
                    </div>
                    <div class="info-box"><x-icon name="help" /><span>Para el paso a RIMPE Emprendedor, registra la fecha efectiva indicada por el SRI. Desde esa fecha las facturas desglosarán el IVA correspondiente; las ventas anteriores conservarán la regla de Negocio Popular.</span></div>
                </div>
                <div class="dialog__footer"><button class="button button--ghost" type="button" data-dialog-close>Cancelar</button><button class="button button--primary" type="submit">Programar vigencia</button></div>
            </form>
        </dialog>
    @endpush
@endcan
