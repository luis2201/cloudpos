<div class="form-grid">
    <label class="field"><span>Código <b>*</b></span><input name="code" value="{{ old('code', $record?->code) }}" maxlength="30" required></label>
    <label class="field"><span>Nombre <b>*</b></span><input name="name" value="{{ old('name', $record?->name) }}" maxlength="120" required></label>
    <label class="field field--wide"><span>Descripción</span><textarea name="description" maxlength="255">{{ old('description', $record?->description) }}</textarea></label>
</div>
<label class="check-field catalog-active"><input name="is_active" type="checkbox" value="1" @checked(old('is_active', $record?->is_active ?? true))><span>Marca activa</span></label>
