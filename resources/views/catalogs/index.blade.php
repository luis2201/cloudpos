@extends('layouts.app')

@section('title', 'Catálogo base')

@php
    $current = $sections[$section];
    $nextDirection = $filters['direction'] === 'asc' ? 'desc' : 'asc';
    $sortUrl = fn (string $column) => route('catalogs.index', array_filter([
        'section' => $section,
        'search' => $filters['search'],
        'status' => $filters['status'],
        'sort' => $column,
        'direction' => $filters['sort'] === $column ? $nextDirection : 'asc',
    ]));
@endphp

@section('content')
    <div class="page-heading">
        <div><span class="eyebrow">Configuración comercial</span><h1>Catálogo base</h1><p>Estructuras reutilizables que darán forma a productos, ventas e inventario.</p></div>
        <button class="button button--primary" type="button" data-dialog-open="new-record"><x-icon name="plus" /> Agregar {{ $current['singular'] }}</button>
    </div>

    <nav class="catalog-tabs" aria-label="Secciones del catálogo">
        @foreach ($sections as $key => $definition)
            <a href="{{ route('catalogs.index', ['section' => $key]) }}" @class(['catalog-tab', 'is-active' => $section === $key])>
                <span><x-icon :name="$definition['icon']" size="19" /></span>
                <div><strong>{{ $definition['label'] }}</strong><small>{{ $counts[$key] }} registros</small></div>
            </a>
        @endforeach
    </nav>

    <section class="panel table-panel">
        <div class="panel__header panel__header--table"><div><span class="eyebrow">{{ $current['label'] }}</span><h2>Registros configurados</h2></div><span class="record-count">{{ $records->total() }} resultados</span></div>
        <form class="filters" method="GET" action="{{ route('catalogs.index') }}">
            <input type="hidden" name="section" value="{{ $section }}">
            <label class="search-field"><x-icon name="search" size="17" /><input name="search" value="{{ $filters['search'] }}" placeholder="Buscar por código, nombre o descripción"><span class="sr-only">Buscar</span></label>
            <label class="select-field"><x-icon name="filter" size="17" /><select name="status"><option value="">Todos los estados</option><option value="active" @selected($filters['status'] === 'active')>Activos</option><option value="inactive" @selected($filters['status'] === 'inactive')>Inactivos</option></select></label>
            <button class="button button--secondary" type="submit">Aplicar</button>
            @if ($filters['search'] !== '' || $filters['status'] !== '')<a class="button button--ghost" href="{{ route('catalogs.index', ['section' => $section]) }}">Limpiar</a>@endif
        </form>

        <div class="table-wrap">
            @if ($section === 'categories')
                <table class="data-table"><thead><tr><th><a href="{{ $sortUrl('code') }}">Código <x-icon name="sort" size="13" /></a></th><th><a href="{{ $sortUrl('name') }}">Categoría <x-icon name="sort" size="13" /></a></th><th>Jerarquía</th><th>Subcategorías</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
                    @forelse ($records as $record)<tr><td data-label="Código"><strong class="code-chip">{{ $record->code }}</strong></td><td data-label="Categoría"><div class="table-primary"><span class="table-primary__icon"><x-icon name="category" /></span><span><strong>{{ $record->name }}</strong><small>{{ $record->description ?: 'Sin descripción' }}</small></span></div></td><td data-label="Jerarquía">{{ $record->parent?->name ?: 'Categoría principal' }}</td><td data-label="Subcategorías">{{ $record->children_count }}</td><td data-label="Estado">@include('catalogs.partials.status', ['active' => $record->is_active])</td><td data-label="Acciones">@include('catalogs.partials.edit-button', ['id' => $record->id])</td></tr>@empty @include('catalogs.partials.empty-row', ['columns' => 6, 'icon' => 'category', 'label' => 'categorías']) @endforelse
                </tbody></table>
            @elseif ($section === 'brands')
                <table class="data-table"><thead><tr><th><a href="{{ $sortUrl('code') }}">Código <x-icon name="sort" size="13" /></a></th><th><a href="{{ $sortUrl('name') }}">Marca <x-icon name="sort" size="13" /></a></th><th>Descripción</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
                    @forelse ($records as $record)<tr><td data-label="Código"><strong class="code-chip">{{ $record->code }}</strong></td><td data-label="Marca"><div class="table-primary"><span class="table-primary__icon"><x-icon name="tag" /></span><span><strong>{{ $record->name }}</strong><small>Marca comercial</small></span></div></td><td data-label="Descripción">{{ $record->description ?: 'Sin descripción' }}</td><td data-label="Estado">@include('catalogs.partials.status', ['active' => $record->is_active])</td><td data-label="Acciones">@include('catalogs.partials.edit-button', ['id' => $record->id])</td></tr>@empty @include('catalogs.partials.empty-row', ['columns' => 5, 'icon' => 'tag', 'label' => 'marcas']) @endforelse
                </tbody></table>
            @elseif ($section === 'units')
                <table class="data-table"><thead><tr><th><a href="{{ $sortUrl('code') }}">Código <x-icon name="sort" size="13" /></a></th><th><a href="{{ $sortUrl('name') }}">Unidad <x-icon name="sort" size="13" /></a></th><th>Dimensión</th><th>Decimales</th><th>Presentaciones</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
                    @forelse ($records as $record)<tr><td data-label="Código"><strong class="code-chip">{{ $record->code }}</strong></td><td data-label="Unidad"><div class="table-primary"><span class="table-primary__icon"><x-icon name="ruler" /></span><span><strong>{{ $record->name }}</strong><small>Símbolo: {{ $record->symbol }}</small></span></div></td><td data-label="Dimensión">{{ $measurementDimensions[$record->dimension] }}</td><td data-label="Decimales">{{ $record->allows_decimals ? 'Permitidos' : 'Solo enteros' }}</td><td data-label="Presentaciones">{{ $record->presentations_count }}</td><td data-label="Estado">@include('catalogs.partials.status', ['active' => $record->is_active])</td><td data-label="Acciones">@include('catalogs.partials.edit-button', ['id' => $record->id])</td></tr>@empty @include('catalogs.partials.empty-row', ['columns' => 7, 'icon' => 'ruler', 'label' => 'unidades']) @endforelse
                </tbody></table>
            @elseif ($section === 'presentations')
                <table class="data-table"><thead><tr><th><a href="{{ $sortUrl('code') }}">Código <x-icon name="sort" size="13" /></a></th><th><a href="{{ $sortUrl('name') }}">Presentación <x-icon name="sort" size="13" /></a></th><th>Contenido</th><th>Conversión</th><th>Productos</th><th>Descripción</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
                    @forelse ($records as $record)<tr><td data-label="Código"><strong class="code-chip">{{ $record->code }}</strong></td><td data-label="Presentación"><div class="table-primary"><span class="table-primary__icon"><x-icon name="package" /></span><span><strong>{{ $record->name }}</strong><small>Formato comercial</small></span></div></td><td data-label="Contenido"><strong>{{ rtrim(rtrim(number_format((float) $record->quantity, 4, ',', ''), '0'), ',') }}</strong> {{ $record->measurementUnit->symbol }}</td><td data-label="Conversión">{{ $record->measurementUnit->dimension === 'quantity' ? 'Multiplica inventario × '.(int) $record->quantity : 'Equivale a 1 unidad' }}</td><td data-label="Productos"><span class="status-badge {{ $record->product_packages_count > 0 ? 'status-badge--info' : 'status-badge--muted' }}">{{ $record->product_packages_count }} empaques</span></td><td data-label="Descripción">{{ $record->description ?: 'Sin descripción' }}</td><td data-label="Estado">@include('catalogs.partials.status', ['active' => $record->is_active])</td><td data-label="Acciones">@include('catalogs.partials.edit-button', ['id' => $record->id])</td></tr>@empty @include('catalogs.partials.empty-row', ['columns' => 8, 'icon' => 'package', 'label' => 'presentaciones']) @endforelse
                </tbody></table>
            @else
                <table class="data-table"><thead><tr><th><a href="{{ $sortUrl('code') }}">Código interno <x-icon name="sort" size="13" /></a></th><th><a href="{{ $sortUrl('name') }}">Forma de pago <x-icon name="sort" size="13" /></a></th><th><a href="{{ $sortUrl('sri_code') }}">Código SRI <x-icon name="sort" size="13" /></a></th><th>Comportamiento</th><th>Origen</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
                    @forelse ($records as $record)<tr><td data-label="Código"><strong class="code-chip">{{ $record->code }}</strong></td><td data-label="Forma de pago"><div class="table-primary"><span class="table-primary__icon"><x-icon name="card" /></span><span><strong>{{ $record->name }}</strong><small>{{ $record->requires_reference ? 'Requiere referencia' : 'Sin referencia obligatoria' }}</small></span></div></td><td data-label="Código SRI"><strong class="sri-chip">{{ $record->sri_code }}</strong></td><td data-label="Comportamiento">{{ $record->affects_cash ? 'Afecta efectivo en caja' : 'Movimiento no efectivo' }}</td><td data-label="Origen"><span class="status-badge {{ $record->is_system ? 'status-badge--info' : 'status-badge--muted' }}">{{ $record->is_system ? 'Oficial SRI' : 'Personalizada' }}</span></td><td data-label="Estado">@include('catalogs.partials.status', ['active' => $record->is_active])</td><td data-label="Acciones">@include('catalogs.partials.edit-button', ['id' => $record->id])</td></tr>@empty @include('catalogs.partials.empty-row', ['columns' => 7, 'icon' => 'card', 'label' => 'formas de pago']) @endforelse
                </tbody></table>
            @endif
        </div>
        <x-pagination :paginator="$records" />
    </section>

    @if ($section === 'payment-methods')
        <div class="info-box catalog-reference"><x-icon name="help" /><span>Los códigos SRI precargados corresponden al catálogo oficial de formas de pago. Una forma personalizada debe mapearse a uno de esos códigos para su futura emisión electrónica.</span></div>
    @endif
    @if ($section === 'presentations')
        <div class="info-box catalog-reference"><x-icon name="help" /><span>Las presentaciones no se eliminan: se desactivan para conservar la trazabilidad. Cuando una presentación ya está asociada a productos, su cantidad y unidad quedan protegidas para no alterar conversiones existentes.</span></div>
    @endif
@endsection

@push('dialogs')
    <dialog class="dialog" id="new-record">
        <form method="POST" action="{{ route('catalogs.'.$section.'.store') }}">@csrf
            <div class="dialog__header"><div><span class="dialog__icon"><x-icon :name="$current['icon']" /></span><span><span class="eyebrow">Nuevo registro</span><h2>Agregar {{ $current['singular'] }}</h2></span></div><button class="icon-button" type="button" data-dialog-close><x-icon name="close" /></button></div>
            <div class="dialog__body">@include('catalogs.partials.'.$section.'-form', ['record' => null])</div>
            <div class="dialog__footer"><button class="button button--ghost" type="button" data-dialog-close>Cancelar</button><button class="button button--primary" type="submit">Guardar registro</button></div>
        </form>
    </dialog>

    @foreach ($records as $record)
        <dialog class="dialog" id="edit-record-{{ $record->id }}">
            <form method="POST" action="{{ route('catalogs.'.$section.'.update', $record) }}">@csrf @method('PUT')
                <div class="dialog__header"><div><span class="dialog__icon"><x-icon name="edit" /></span><span><span class="eyebrow">Edición</span><h2>{{ $record->code }} · {{ $record->name }}</h2></span></div><button class="icon-button" type="button" data-dialog-close><x-icon name="close" /></button></div>
                <div class="dialog__body">@include('catalogs.partials.'.$section.'-form', ['record' => $record])</div>
                <div class="dialog__footer"><button class="button button--ghost" type="button" data-dialog-close>Cancelar</button><button class="button button--primary" type="submit">Guardar cambios</button></div>
            </form>
        </dialog>
    @endforeach
@endpush
