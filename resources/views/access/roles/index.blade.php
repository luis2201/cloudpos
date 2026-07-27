@extends('layouts.app')

@section('title', 'Roles y permisos')

@section('content')
    <div class="page-heading">
        <div><span class="eyebrow">Acceso y seguridad</span><h1>Roles y permisos</h1><p>Agrupa las capacidades del sistema según la función de cada usuario.</p></div>
        <button class="button button--primary" type="button" data-dialog-open="new-role"><x-icon name="plus" /> Nuevo rol</button>
    </div>
    <div class="role-grid">
        @foreach ($roles as $role)
            <article class="panel role-card">
                <div class="role-card__header"><span class="role-card__icon"><x-icon name="shield" /></span><div><h2>{{ $role->name }}</h2><p>{{ $role->description ?: 'Sin descripción' }}</p></div><span class="status-badge {{ $role->is_system ? 'status-badge--info' : 'status-badge--muted' }}">{{ $role->is_system ? 'Sistema' : 'Personalizado' }}</span></div>
                <div class="role-card__stats"><span><strong>{{ $role->users_count }}</strong> usuarios</span><span><strong>{{ in_array('*', $role->permissions ?? [], true) ? count($availablePermissions) : count($role->permissions ?? []) }}</strong> permisos</span></div>
                <div class="tag-list tag-list--permissions">@if (in_array('*', $role->permissions ?? [], true))<span>Acceso total</span>@else @foreach ($role->permissions ?? [] as $permission)<span>{{ $availablePermissions[$permission] ?? $permission }}</span>@endforeach @endif</div>
                <footer>@if ($role->is_system)<small>Rol protegido; garantiza que siempre exista control administrativo.</small>@else<button class="button button--secondary button--block" type="button" data-dialog-open="edit-role-{{ $role->id }}"><x-icon name="edit" /> Editar permisos</button>@endif</footer>
            </article>
        @endforeach
    </div>
    <div class="panel role-pagination"><x-pagination :paginator="$roles" /></div>
@endsection

@push('dialogs')
    <dialog class="dialog dialog--wide" id="new-role"><form method="POST" action="{{ route('roles.store') }}">@csrf<div class="dialog__header"><div><span class="dialog__icon"><x-icon name="shield" /></span><span><span class="eyebrow">Nuevo perfil</span><h2>Crear rol</h2></span></div><button class="icon-button" type="button" data-dialog-close><x-icon name="close" /></button></div><div class="dialog__body">@include('access.roles.partials.form', ['role' => null])</div><div class="dialog__footer"><button class="button button--ghost" type="button" data-dialog-close>Cancelar</button><button class="button button--primary" type="submit">Crear rol</button></div></form></dialog>
    @foreach ($roles->where('is_system', false) as $role)
        <dialog class="dialog dialog--wide" id="edit-role-{{ $role->id }}"><form method="POST" action="{{ route('roles.update', $role) }}">@csrf @method('PUT')<div class="dialog__header"><div><span class="dialog__icon"><x-icon name="edit" /></span><span><span class="eyebrow">Permisos</span><h2>Editar {{ $role->name }}</h2></span></div><button class="icon-button" type="button" data-dialog-close><x-icon name="close" /></button></div><div class="dialog__body">@include('access.roles.partials.form', ['role' => $role])</div><div class="dialog__footer"><button class="button button--ghost" type="button" data-dialog-close>Cancelar</button><button class="button button--primary" type="submit">Guardar permisos</button></div></form></dialog>
    @endforeach
@endpush
