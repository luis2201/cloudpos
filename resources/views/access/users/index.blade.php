@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')
    <div class="page-heading">
        <div><span class="eyebrow">Acceso y seguridad</span><h1>Usuarios</h1><p>Crea cuentas operativas y asigna sus responsabilidades mediante roles.</p></div>
        <button class="button button--primary" type="button" data-dialog-open="new-user"><x-icon name="plus" /> Nuevo usuario</button>
    </div>
    <section class="panel table-panel">
        <div class="panel__header panel__header--table"><div><span class="eyebrow">Equipo</span><h2>Cuentas registradas</h2></div><span class="record-count">{{ $users->total() }} usuarios</span></div>
        <form class="filters" method="GET" action="{{ route('users.index') }}"><label class="search-field"><x-icon name="search" size="17" /><input name="search" value="{{ $search }}" placeholder="Buscar por nombre o correo"><span class="sr-only">Buscar usuario</span></label><button class="button button--secondary" type="submit">Buscar</button>@if ($search !== '')<a class="button button--ghost" href="{{ route('users.index') }}">Limpiar</a>@endif</form>
        <div class="table-wrap"><table class="data-table"><thead><tr><th>Usuario</th><th>Contacto</th><th>Roles</th><th>Último ingreso</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
            @forelse ($users as $user)
                <tr>
                    <td data-label="Usuario"><div class="table-primary"><span class="user-menu__avatar">{{ collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join('') }}</span><span><strong>{{ $user->name }}</strong><small>{{ $user->is(auth()->user()) ? 'Tu cuenta' : 'Cuenta del equipo' }}</small></span></div></td>
                    <td data-label="Contacto"><strong class="table-email">{{ $user->email }}</strong><br><small>{{ $user->phone ?: 'Sin teléfono' }}</small></td>
                    <td data-label="Roles"><div class="tag-list">@foreach ($user->roles as $role)<span>{{ $role->name }}</span>@endforeach</div></td>
                    <td data-label="Último ingreso">{{ $user->last_login_at?->translatedFormat('d M Y, H:i') ?: 'Aún no ingresa' }}</td>
                    <td data-label="Estado"><span class="status-badge {{ $user->is_active ? 'status-badge--success' : 'status-badge--muted' }}">{{ $user->is_active ? 'Activo' : 'Inactivo' }}</span></td>
                    <td data-label="Acciones">
                        <div class="table-actions"><button class="icon-button" type="button" data-dialog-open="edit-user-{{ $user->id }}" aria-label="Editar"><x-icon name="edit" size="17" /></button><form method="POST" action="{{ route('users.toggle-status', $user) }}">@csrf @method('PATCH')<button class="button button--secondary button--small" type="submit" @disabled($user->is(auth()->user()))>{{ $user->is_active ? 'Desactivar' : 'Activar' }}</button></form></div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty-state"><span><x-icon name="users" /></span><strong>No se encontraron usuarios</strong><small>Prueba con otro término de búsqueda.</small></div></td></tr>
            @endforelse
        </tbody></table></div>
        <x-pagination :paginator="$users" />
    </section>
@endsection

@push('dialogs')
    <dialog class="dialog" id="new-user">
        <form method="POST" action="{{ route('users.store') }}">@csrf
            <div class="dialog__header"><div><span class="dialog__icon"><x-icon name="users" /></span><span><span class="eyebrow">Nueva cuenta</span><h2>Crear usuario</h2></span></div><button class="icon-button" type="button" data-dialog-close><x-icon name="close" /></button></div>
            <div class="dialog__body">
                <div class="form-grid">
                    <label class="field field--wide"><span>Nombre completo <b>*</b></span><input name="name" value="{{ old('name') }}" maxlength="120" required></label>
                    <label class="field"><span>Correo <b>*</b></span><input name="email" type="email" value="{{ old('email') }}" required></label>
                    <label class="field"><span>Teléfono</span><input name="phone" value="{{ old('phone') }}"></label>
                    <label class="field"><span>Contraseña <b>*</b></span><input name="password" type="password" minlength="10" required></label>
                    <label class="field"><span>Confirmar contraseña <b>*</b></span><input name="password_confirmation" type="password" minlength="10" required></label>
                    <div class="field field--wide"><span>Roles asignados <b>*</b></span><div class="permission-grid">@foreach ($roles as $role)<label class="permission-option"><input name="role_ids[]" type="checkbox" value="{{ $role->id }}" @checked(in_array($role->id, old('role_ids', [])))><span><strong>{{ $role->name }}</strong><small>{{ $role->description }}</small></span></label>@endforeach</div></div>
                </div>
                <div class="info-box"><x-icon name="shield" /><span>La contraseña requiere 10 caracteres como mínimo, con letras y números.</span></div>
            </div>
            <div class="dialog__footer"><button class="button button--ghost" type="button" data-dialog-close>Cancelar</button><button class="button button--primary" type="submit">Crear usuario</button></div>
        </form>
    </dialog>
    @foreach ($users as $user)
        <dialog class="dialog" id="edit-user-{{ $user->id }}">
            <form method="POST" action="{{ route('users.update', $user) }}">@csrf @method('PUT')
                <div class="dialog__header"><div><span class="dialog__icon"><x-icon name="edit" /></span><span><span class="eyebrow">Cuenta de usuario</span><h2>Editar {{ $user->name }}</h2></span></div><button class="icon-button" type="button" data-dialog-close><x-icon name="close" /></button></div>
                <div class="dialog__body">
                    <div class="form-grid">
                        <label class="field field--wide"><span>Nombre completo <b>*</b></span><input name="name" value="{{ $user->name }}" maxlength="120" required></label>
                        <label class="field"><span>Correo <b>*</b></span><input name="email" type="email" value="{{ $user->email }}" required></label>
                        <label class="field"><span>Teléfono</span><input name="phone" value="{{ $user->phone }}"></label>
                        <label class="field"><span>Nueva contraseña</span><input name="password" type="password" minlength="10" placeholder="Dejar vacío para conservar"></label>
                        <label class="field"><span>Confirmar nueva contraseña</span><input name="password_confirmation" type="password" minlength="10"></label>
                        <div class="field field--wide"><span>Roles asignados <b>*</b></span><div class="permission-grid">@foreach ($roles as $role)<label class="permission-option"><input name="role_ids[]" type="checkbox" value="{{ $role->id }}" @checked($user->roles->contains($role))><span><strong>{{ $role->name }}</strong><small>{{ $role->description }}</small></span></label>@endforeach</div></div>
                    </div>
                </div>
                <div class="dialog__footer"><button class="button button--ghost" type="button" data-dialog-close>Cancelar</button><button class="button button--primary" type="submit">Guardar cambios</button></div>
            </form>
        </dialog>
    @endforeach
@endpush
