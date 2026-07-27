@extends('layouts.app')

@section('title', $product->name)

@section('content')
    <div class="page-heading">
        <div><a class="back-link" href="{{ route('products.index') }}"><x-icon name="arrow-left" size="15" /> Volver a productos</a><span class="eyebrow">{{ $product->code }}</span><h1>{{ $product->name }}</h1><p>{{ $product->brand?->name ?: 'Sin marca' }} · {{ $product->category->name }}</p></div>
        <div class="heading-actions"><button class="button button--secondary" type="button" data-dialog-open="edit-product"><x-icon name="edit" /> Editar producto</button><button class="button button--primary" type="button" data-dialog-open="new-package"><x-icon name="plus" /> Agregar presentación</button></div>
    </div>

    <section class="product-detail-grid">
        <article class="panel detail-card"><span class="detail-card__icon"><x-icon name="tax" /></span><div><small>Clasificación fiscal del producto</small><strong>{{ $vatTreatments[$product->vat_treatment] }}</strong><p>{{ $shouldBreakDownVat ? 'IVA aplicable en venta' : 'Sin desglose de IVA por RIMPE Negocio Popular' }} · {{ $shouldCalculateProductIce ? 'ICE por liquidar' : 'ICE de origen, no se suma en la reventa' }}</p></div></article>
        <article class="panel detail-card"><span class="detail-card__icon"><x-icon name="bottle" /></span><div><small>Tipo de producto</small><strong>{{ $productKinds[$product->product_kind] }}</strong><p>{{ $product->alcohol_by_volume ? $product->alcohol_by_volume.'% de alcohol' : 'Sin grado alcohólico' }}</p></div></article>
        <article class="panel detail-card"><span class="detail-card__icon"><x-icon name="box" /></span><div><small>Regla de inventario</small><strong>Control por unidad</strong><p>Cada empaque multiplica su factor de conversión.</p></div></article>
    </section>

    <section class="panel table-panel">
        <div class="panel__header panel__header--table"><div><span class="eyebrow">Precios y conversión</span><h2>Presentaciones del producto</h2></div><span class="record-count">{{ $product->packages->count() }} presentaciones</span></div>
        <div class="table-wrap"><table class="data-table"><thead><tr><th>Presentación</th><th>Identificación</th><th>Precio neto</th><th>Conversión</th><th>Ejemplo</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
            @foreach ($product->packages->sortByDesc('is_base') as $package)
                <tr>
                    <td data-label="Presentación"><div class="table-primary"><span class="table-primary__icon"><x-icon name="package" /></span><span><strong>{{ $package->presentation->name }}</strong><small>{{ $package->is_base ? 'Presentación principal' : 'Presentación adicional' }}</small></span></div></td>
                    <td data-label="Identificación"><div class="barcode-stack"><small>Fabricante: {{ $package->barcode ?: 'pendiente' }}</small>@if ($package->internal_barcode)<img class="barcode-preview" src="{{ route('products.packages.internal-barcode.svg', [$product, $package]) }}" alt="Código interno {{ $package->internal_barcode }}"><span class="barcode-value">{{ $package->internal_barcode }}</span>@else<form method="POST" action="{{ route('products.packages.internal-barcode', [$product, $package]) }}">@csrf<button class="button button--secondary button--small" type="submit">Generar interno</button></form>@endif</div></td>
                    <td data-label="Precio base">@if ((float) $package->price_before_tax > 0)<strong class="price-value">$ {{ number_format((float) $package->price_before_tax, 2, ',', '.') }}</strong><small class="price-note">neto · ICE no se suma</small>@else<span class="status-badge status-badge--warning">Por definir</span>@endif</td>
                    <td data-label="Conversión"><strong>1 × {{ $package->units_per_package }}</strong> unidad(es)</td>
                    <td data-label="Ejemplo">5 empaques = <strong>{{ 5 * $package->units_per_package }}</strong> unidades</td>
                    <td data-label="Estado"><span class="status-badge {{ $package->is_active ? 'status-badge--success' : 'status-badge--muted' }}">{{ $package->is_active ? 'Activa' : 'Inactiva' }}</span></td>
                    <td data-label="Acciones"><div class="table-actions"><button class="icon-button" type="button" data-dialog-open="edit-package-{{ $package->id }}" aria-label="Editar presentación"><x-icon name="edit" size="17" /></button>@if ($package->internal_barcode)<a class="button button--secondary button--small" href="{{ route('products.packages.internal-barcode.svg', [$product, $package]) }}" target="_blank" rel="noopener">Abrir SVG</a>@endif</div></td>
                </tr>
            @endforeach
        </tbody></table></div>
    </section>

    <div class="info-box"><x-icon name="shield" /><span>CloudPOS conserva {{ $iceTreatments[$product->ice_treatment] }} como trazabilidad de compra. Al ser un negocio minorista que no fabrica ni importa, este valor no se calcula ni se agrega nuevamente al precio de venta.</span></div>
    @if ($product->product_kind === 'alcoholic_beverage')<div class="info-box no-expiration-banner"><x-icon name="check" /><span>Este producto está clasificado como bebida alcohólica y no maneja fecha de caducidad en CloudPOS.</span></div>@endif
@endsection

@push('dialogs')
    <dialog class="dialog dialog--wide" id="edit-product"><form method="POST" action="{{ route('products.update', $product) }}">@csrf @method('PUT')<div class="dialog__header"><div><span class="dialog__icon"><x-icon name="edit" /></span><span><span class="eyebrow">Producto</span><h2>Editar {{ $product->name }}</h2></span></div><button class="icon-button" type="button" data-dialog-close><x-icon name="close" /></button></div><div class="dialog__body">@include('products.partials.product-form', ['includePackage' => false])</div><div class="dialog__footer"><button class="button button--ghost" type="button" data-dialog-close>Cancelar</button><button class="button button--primary" type="submit">Guardar cambios</button></div></form></dialog>

    <dialog class="dialog" id="new-package"><form method="POST" action="{{ route('products.packages.store', $product) }}">@csrf<div class="dialog__header"><div><span class="dialog__icon"><x-icon name="package" /></span><span><span class="eyebrow">Empaque de venta</span><h2>Agregar presentación</h2></span></div><button class="icon-button" type="button" data-dialog-close><x-icon name="close" /></button></div><div class="dialog__body">
        <div class="form-grid">
            <label class="field field--wide"><span>Presentación <b>*</b></span><select name="presentation_id" required><option value="">Selecciona una presentación</option>@foreach ($availablePresentations->whereNotIn('id', $product->packages->pluck('presentation_id')) as $presentation) @php($factor = $presentation->measurementUnit->dimension === 'quantity' ? (int) $presentation->quantity : 1)<option value="{{ $presentation->id }}">{{ $presentation->name }} · convierte a {{ $factor }} unidad(es)</option>@endforeach</select></label>
            <label class="field"><span>GTIN/EAN/UPC del fabricante</span><input name="barcode" maxlength="80" inputmode="numeric" placeholder="Escanea o digita el código de la etiqueta"></label>
            <label class="field"><span>Precio base registrado <b>*</b></span><div class="input-suffix"><span>$</span><input name="price_before_tax" type="number" min="0" step="0.0001" required></div></label>
        </div>
        <label class="check-field catalog-active"><input name="is_active" type="checkbox" value="1" checked><span>Presentación activa</span></label>
        <div class="info-box"><x-icon name="box" /><span>La conversión se toma de la presentación. CloudPOS generará además un Code 128 interno sin reemplazar el código original del fabricante.</span></div>
    </div><div class="dialog__footer"><button class="button button--ghost" type="button" data-dialog-close>Cancelar</button><button class="button button--primary" type="submit">Agregar presentación</button></div></form></dialog>

    @foreach ($product->packages as $package)
        <dialog class="dialog" id="edit-package-{{ $package->id }}"><form method="POST" action="{{ route('products.packages.update', [$product, $package]) }}">@csrf @method('PUT')<div class="dialog__header"><div><span class="dialog__icon"><x-icon name="edit" /></span><span><span class="eyebrow">Precio neto</span><h2>{{ $package->presentation->name }}</h2></span></div><button class="icon-button" type="button" data-dialog-close><x-icon name="close" /></button></div><div class="dialog__body">
            <div class="conversion-summary"><span>Factor de inventario</span><strong>1 empaque = {{ $package->units_per_package }} unidad(es)</strong></div>
            <div class="form-grid"><label class="field"><span>GTIN/EAN/UPC del fabricante</span><input name="barcode" value="{{ $package->barcode }}" maxlength="80" inputmode="numeric" placeholder="Código impreso en la etiqueta"></label><label class="field"><span>Precio neto sin impuestos <b>*</b></span><div class="input-suffix"><span>$</span><input name="price_before_tax" type="number" min="0" step="0.0001" value="{{ $package->price_before_tax }}" required></div></label></div>
            <label class="check-field catalog-active"><input name="is_active" type="checkbox" value="1" @checked($package->is_active) @disabled($package->is_base)><span>Presentación activa</span></label>@if ($package->is_base)<input type="hidden" name="is_active" value="1"><div class="info-box"><x-icon name="shield" /><span>La presentación principal debe permanecer activa.</span></div>@endif
        </div><div class="dialog__footer"><button class="button button--ghost" type="button" data-dialog-close>Cancelar</button><button class="button button--primary" type="submit">Guardar cambios</button></div></form></dialog>
    @endforeach
@endpush
