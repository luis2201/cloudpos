<div class="form-grid">
    <label class="field"><span>Nombre del rol <b>*</b></span><input name="name" value="{{ old('name', $role?->name) }}" maxlength="80" required></label>
    <label class="field field--wide"><span>Descripción</span><textarea name="description">{{ old('description', $role?->description) }}</textarea></label>
    <div class="field field--wide"><span>Permisos <b>*</b></span><div class="permission-grid">@foreach ($availablePermissions as $permission => $label)<label class="permission-option"><input name="permissions[]" type="checkbox" value="{{ $permission }}" @checked(in_array($permission, old('permissions', $role?->permissions ?? []), true))><span><strong>{{ $label }}</strong><small>{{ $permission }}</small></span></label>@endforeach</div></div>
</div>
