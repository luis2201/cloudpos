<div class="form-grid">
    <label class="field"><span>Establecimiento <b>*</b></span><select name="establishment_id" required>@foreach ($establishments as $option)<option value="{{ $option->id }}" @selected((string) old('establishment_id', $warehouse?->establishment_id) === (string) $option->id)>{{ $option->code }} · {{ $option->name }}</option>@endforeach</select></label>
    <label class="field"><span>Código <b>*</b></span><input name="code" value="{{ old('code', $warehouse?->code) }}" maxlength="20" required></label>
    <label class="field field--wide"><span>Nombre <b>*</b></span><input name="name" value="{{ old('name', $warehouse?->name) }}" maxlength="150" required></label>
    <label class="field field--wide"><span>Dirección o ubicación</span><textarea name="address">{{ old('address', $warehouse?->address) }}</textarea></label>
    <label class="field field--wide"><span>Descripción</span><textarea name="description">{{ old('description', $warehouse?->description) }}</textarea></label>
</div>
<div class="check-stack check-stack--inline"><label class="check-field"><input name="is_main" type="checkbox" value="1" @checked($warehouse?->is_main)><span>Bodega principal</span></label><label class="check-field"><input name="is_active" type="checkbox" value="1" @checked($warehouse?->is_active ?? true)><span>Activa</span></label><label class="check-field"><input name="allow_negative_stock" type="checkbox" value="1" @checked($warehouse?->allow_negative_stock)><span>Permitir stock negativo</span></label></div>
