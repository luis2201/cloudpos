@extends('layouts.app')

@section('title', 'Establecimientos')

@section('content')
    <div class="page-heading">
        <div><span class="eyebrow">Organización</span><h1>Establecimientos</h1><p>Administra los locales asociados a {{ $company->trade_name ?: $company->legal_name }}.</p></div>
        <button class="button button--primary" type="button" data-dialog-open="new-establishment"><x-icon name="plus" /> Nuevo establecimiento</button>
    </div>

    <section class="panel table-panel">
        <div class="panel__header panel__header--table"><div><span class="eyebrow">Locales registrados</span><h2>Estructura comercial</h2></div><span class="record-count">{{ $establishments->total() }} registros</span></div>
        <div class="table-wrap"><table class="data-table"><thead><tr><th>Establecimiento</th><th>Dirección</th><th>Bodegas</th><th>Tipo</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
            @forelse ($establishments as $establishment)
                <tr>
                    <td data-label="Establecimiento"><div class="table-primary"><span class="table-primary__icon"><x-icon name="store" /></span><span><strong>{{ $establishment->code }} · {{ $establishment->name }}</strong><small>{{ $establishment->trade_name ?: 'Sin nombre comercial' }}</small></span></div></td>
                    <td data-label="Dirección">{{ Str::limit($establishment->address, 52) }}</td>
                    <td data-label="Bodegas">{{ $establishment->warehouses_count }}</td>
                    <td data-label="Tipo"><span class="status-badge {{ $establishment->is_main ? 'status-badge--info' : 'status-badge--muted' }}">{{ $establishment->is_main ? 'Matriz' : 'Adicional' }}</span></td>
                    <td data-label="Estado"><span class="status-badge {{ $establishment->is_active ? 'status-badge--success' : 'status-badge--muted' }}">{{ $establishment->is_active ? 'Activo' : 'Inactivo' }}</span></td>
                    <td data-label="Acciones"><button class="icon-button" type="button" data-dialog-open="edit-establishment-{{ $establishment->id }}" aria-label="Editar"><x-icon name="edit" size="17" /></button></td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty-state"><span><x-icon name="store" /></span><strong>No hay establecimientos</strong><small>Registra el primer local.</small></div></td></tr>
            @endforelse
        </tbody></table></div>
        <x-pagination :paginator="$establishments" />
    </section>
@endsection

@push('dialogs')
    <dialog class="dialog" id="new-establishment">
        <form method="POST" action="{{ route('establishments.store') }}">@csrf
            <div class="dialog__header"><div><span class="dialog__icon"><x-icon name="store" /></span><span><span class="eyebrow">Nuevo registro</span><h2>Agregar establecimiento</h2></span></div><button class="icon-button" type="button" data-dialog-close><x-icon name="close" /></button></div>
            <div class="dialog__body">@include('organization.establishments.partials.form', ['establishment' => null])</div>
            <div class="dialog__footer"><button class="button button--ghost" type="button" data-dialog-close>Cancelar</button><button class="button button--primary" type="submit">Guardar establecimiento</button></div>
        </form>
    </dialog>
    @foreach ($establishments as $establishment)
        <dialog class="dialog" id="edit-establishment-{{ $establishment->id }}">
            <form method="POST" action="{{ route('establishments.update', $establishment) }}">@csrf @method('PUT')
                <div class="dialog__header"><div><span class="dialog__icon"><x-icon name="edit" /></span><span><span class="eyebrow">Edición</span><h2>{{ $establishment->code }} · {{ $establishment->name }}</h2></span></div><button class="icon-button" type="button" data-dialog-close><x-icon name="close" /></button></div>
                <div class="dialog__body">@include('organization.establishments.partials.form', ['establishment' => $establishment])</div>
                <div class="dialog__footer"><button class="button button--ghost" type="button" data-dialog-close>Cancelar</button><button class="button button--primary" type="submit">Guardar cambios</button></div>
            </form>
        </dialog>
    @endforeach
@endpush
