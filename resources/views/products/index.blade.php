@extends('layouts.app')

@section('title', 'Productos')

@php($nextDirection = $filters['direction'] === 'asc' ? 'desc' : 'asc')

@section('content')
    <div class="page-heading">
        <div><span class="eyebrow">Catálogo comercial</span><h1>Productos</h1><p>Precios netos editables, identificación para escáner y presentaciones convertibles a unidades.</p></div>
        <button class="button button--primary" type="button" data-dialog-open="new-product"><x-icon name="plus" /> Nuevo producto</button>
    </div>

    <section class="product-rule-grid">
        <article class="panel product-rule"><span><x-icon name="cash" /></span><div><strong>Precio neto editable</strong><small>Valor antes de tributos; confirma la lista inicial con el negocio.</small></div></article>
        <article class="panel product-rule"><span><x-icon name="tax" /></span><div><strong>RIMPE Negocio Popular</strong><small>La venta no desglosa IVA; la clasificación del producto se conserva.</small></div></article>
        <article class="panel product-rule"><span><x-icon name="shield" /></span><div><strong>{{ $shouldCalculateProductIce ? 'ICE por liquidar' : 'ICE ya liquidado en origen' }}</strong><small>{{ $shouldCalculateProductIce ? 'El perfil tributario requiere cálculo directo.' : 'CloudPOS no vuelve a sumar ICE en la reventa.' }}</small></div></article>
    </section>

    <section class="panel table-panel">
        <div class="panel__header panel__header--table"><div><span class="eyebrow">Maestro de productos</span><h2>Productos registrados</h2></div><span class="record-count">{{ $products->total() }} registros</span></div>
        <form class="filters filters--wrap" method="GET" action="{{ route('products.index') }}">
            <label class="search-field"><x-icon name="search" size="17" /><input name="search" value="{{ $filters['search'] }}" placeholder="Código, nombre o código de barras"><span class="sr-only">Buscar productos</span></label>
            <label class="select-field"><select name="category_id"><option value="">Todas las categorías</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected((string) $filters['categoryId'] === (string) $category->id)>{{ $category->parent ? $category->parent->name.' · ' : '' }}{{ $category->name }}</option>@endforeach</select></label>
            <label class="select-field"><select name="tax"><option value="">Todos los perfiles</option><option value="vat" @selected($filters['tax'] === 'vat')>Grava IVA general</option><option value="ice" @selected($filters['tax'] === 'ice')>Con referencia ICE en origen</option><option value="none" @selected($filters['tax'] === 'none')>Sin IVA general ni referencia ICE</option></select></label>
            <label class="select-field"><select name="status"><option value="">Todos los estados</option><option value="active" @selected($filters['status'] === 'active')>Activos</option><option value="inactive" @selected($filters['status'] === 'inactive')>Inactivos</option></select></label>
            <button class="button button--secondary" type="submit">Aplicar</button>
            @if (array_filter([$filters['search'], $filters['categoryId'], $filters['tax'], $filters['status']]))<a class="button button--ghost" href="{{ route('products.index') }}">Limpiar</a>@endif
        </form>
        <div class="table-wrap"><table class="data-table"><thead><tr>
            <th><a href="{{ route('products.index', array_merge(request()->query(), ['sort' => 'code', 'direction' => $filters['sort'] === 'code' ? $nextDirection : 'asc'])) }}">Código <x-icon name="sort" size="13" /></a></th>
            <th><a href="{{ route('products.index', array_merge(request()->query(), ['sort' => 'name', 'direction' => $filters['sort'] === 'name' ? $nextDirection : 'asc'])) }}">Producto <x-icon name="sort" size="13" /></a></th>
            <th>Categoría</th><th>Clasificación fiscal</th><th>Presentación base</th><th>Precio base</th><th>Estado</th><th>Acciones</th>
        </tr></thead><tbody>
            @forelse ($products as $product)
                <tr>
                    <td data-label="Código"><strong class="code-chip">{{ $product->code }}</strong></td>
                    <td data-label="Producto"><div class="table-primary"><span class="table-primary__icon"><x-icon name="bottle" /></span><span><strong>{{ $product->name }}</strong><small>{{ $product->brand?->name ?: 'Sin marca' }}{{ $product->alcohol_by_volume ? ' · '.$product->alcohol_by_volume.'% Alc.' : '' }}</small></span></div></td>
                    <td data-label="Categoría">{{ $product->category->name }}</td>
                    <td data-label="Impuestos"><div class="tag-list"><span>{{ $vatTreatments[$product->vat_treatment] }}</span>@if ($product->ice_treatment !== 'none')<span class="tag--orange">{{ $iceTreatments[$product->ice_treatment] }}</span>@endif</div></td>
                    <td data-label="Presentación base">{{ $product->basePackage?->presentation?->name ?: 'Sin presentación' }}</td>
                    <td data-label="Precio base">@if ((float) ($product->basePackage?->price_before_tax ?? 0) > 0)<strong class="price-value">$ {{ number_format((float) $product->basePackage->price_before_tax, 2, ',', '.') }}</strong><small class="price-note">neto · ICE no se suma</small>@else<span class="status-badge status-badge--warning">Por definir</span>@endif</td>
                    <td data-label="Estado"><span class="status-badge {{ $product->is_active ? 'status-badge--success' : 'status-badge--muted' }}">{{ $product->is_active ? 'Activo' : 'Inactivo' }}</span></td>
                    <td data-label="Acciones"><a class="button button--secondary button--small" href="{{ route('products.show', $product) }}">Administrar <x-icon name="arrow-right" size="14" /></a></td>
                </tr>
            @empty
                <tr><td colspan="8"><div class="empty-state"><span><x-icon name="bottle" /></span><strong>No hay productos</strong><small>Registra el primer producto o cambia los filtros.</small></div></td></tr>
            @endforelse
        </tbody></table></div>
        <x-pagination :paginator="$products" />
    </section>
@endsection

@push('dialogs')
    <dialog class="dialog dialog--wide" id="new-product">
        <form method="POST" action="{{ route('products.store') }}">@csrf
            <div class="dialog__header"><div><span class="dialog__icon"><x-icon name="bottle" /></span><span><span class="eyebrow">Nuevo registro</span><h2>Crear producto</h2></span></div><button class="icon-button" type="button" data-dialog-close><x-icon name="close" /></button></div>
            <div class="dialog__body">@include('products.partials.product-form', ['product' => null, 'includePackage' => true])</div>
            <div class="dialog__footer"><button class="button button--ghost" type="button" data-dialog-close>Cancelar</button><button class="button button--primary" type="submit">Crear producto</button></div>
        </form>
    </dialog>
@endpush
