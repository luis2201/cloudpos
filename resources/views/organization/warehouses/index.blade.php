@extends('layouts.app')

@section('title', 'Bodegas')

@section('content')
    <div class="page-heading">
        <div><span class="eyebrow">Inventario</span><h1>Bodegas</h1><p>Define dónde se almacenan y controlan las existencias.</p></div>
        <button class="button button--primary" type="button" data-dialog-open="new-warehouse"><x-icon name="plus" /> Nueva bodega</button>
    </div>
    <section class="panel table-panel">
        <div class="panel__header panel__header--table"><div><span class="eyebrow">Ubicaciones</span><h2>Bodegas registradas</h2></div><span class="record-count">{{ $warehouses->total() }} registros</span></div>
        <div class="table-wrap"><table class="data-table"><thead><tr><th>Bodega</th><th>Establecimiento</th><th>Ubicación</th><th>Stock negativo</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
            @forelse ($warehouses as $warehouse)
                <tr>
                    <td data-label="Bodega"><div class="table-primary"><span class="table-primary__icon"><x-icon name="box" /></span><span><strong>{{ $warehouse->code }} · {{ $warehouse->name }}</strong><small>{{ $warehouse->is_main ? 'Bodega principal' : 'Bodega adicional' }}</small></span></div></td>
                    <td data-label="Establecimiento">{{ $warehouse->establishment->code }} · {{ $warehouse->establishment->name }}</td>
                    <td data-label="Ubicación">{{ Str::limit($warehouse->address ?: 'Sin dirección específica', 45) }}</td>
                    <td data-label="Stock negativo"><span class="status-badge {{ $warehouse->allow_negative_stock ? 'status-badge--info' : 'status-badge--muted' }}">{{ $warehouse->allow_negative_stock ? 'Permitido' : 'Bloqueado' }}</span></td>
                    <td data-label="Estado"><span class="status-badge {{ $warehouse->is_active ? 'status-badge--success' : 'status-badge--muted' }}">{{ $warehouse->is_active ? 'Activa' : 'Inactiva' }}</span></td>
                    <td data-label="Acciones"><button class="icon-button" type="button" data-dialog-open="edit-warehouse-{{ $warehouse->id }}" aria-label="Editar"><x-icon name="edit" size="17" /></button></td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty-state"><span><x-icon name="box" /></span><strong>No hay bodegas</strong><small>Crea una ubicación para el inventario.</small></div></td></tr>
            @endforelse
        </tbody></table></div>
        <x-pagination :paginator="$warehouses" />
    </section>
@endsection

@push('dialogs')
    <dialog class="dialog" id="new-warehouse"><form method="POST" action="{{ route('warehouses.store') }}">@csrf<div class="dialog__header"><div><span class="dialog__icon"><x-icon name="box" /></span><span><span class="eyebrow">Nuevo registro</span><h2>Agregar bodega</h2></span></div><button class="icon-button" type="button" data-dialog-close><x-icon name="close" /></button></div><div class="dialog__body">@include('organization.warehouses.partials.form', ['warehouse' => null])</div><div class="dialog__footer"><button class="button button--ghost" type="button" data-dialog-close>Cancelar</button><button class="button button--primary" type="submit">Guardar bodega</button></div></form></dialog>
    @foreach ($warehouses as $warehouse)
        <dialog class="dialog" id="edit-warehouse-{{ $warehouse->id }}"><form method="POST" action="{{ route('warehouses.update', $warehouse) }}">@csrf @method('PUT')<div class="dialog__header"><div><span class="dialog__icon"><x-icon name="edit" /></span><span><span class="eyebrow">Edición</span><h2>{{ $warehouse->code }} · {{ $warehouse->name }}</h2></span></div><button class="icon-button" type="button" data-dialog-close><x-icon name="close" /></button></div><div class="dialog__body">@include('organization.warehouses.partials.form', ['warehouse' => $warehouse])</div><div class="dialog__footer"><button class="button button--ghost" type="button" data-dialog-close>Cancelar</button><button class="button button--primary" type="submit">Guardar cambios</button></div></form></dialog>
    @endforeach
@endpush
