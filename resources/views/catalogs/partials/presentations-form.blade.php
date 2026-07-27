@php($isUsed = (int) ($record?->product_packages_count ?? 0) > 0)
<div class="form-grid">
    <label class="field"><span>Código <b>*</b></span><input name="code" value="{{ old('code', $record?->code) }}" maxlength="30" required></label>
    <label class="field"><span>Nombre <b>*</b></span><input name="name" value="{{ old('name', $record?->name) }}" maxlength="120" required></label>
    <label class="field"><span>Cantidad contenida <b>*</b></span><input name="quantity" type="number" min="0.0001" step="0.0001" value="{{ old('quantity', $record?->quantity) }}" required @readonly($isUsed)></label>
    <label class="field"><span>Unidad del contenido <b>*</b></span><select name="measurement_unit_id" required @disabled($isUsed)>@foreach ($units as $unit)<option value="{{ $unit->id }}" @selected((string) old('measurement_unit_id', $record?->measurement_unit_id) === (string) $unit->id)>{{ $unit->name }} ({{ $unit->symbol }})</option>@endforeach</select>@if ($isUsed)<input type="hidden" name="measurement_unit_id" value="{{ $record->measurement_unit_id }}">@endif</label>
    <label class="field field--wide"><span>Descripción</span><textarea name="description" maxlength="255">{{ old('description', $record?->description) }}</textarea></label>
</div>
@if ($isUsed)<div class="info-box"><x-icon name="shield" /><span>El contenido está protegido porque {{ $record->product_packages_count }} empaque(s) de producto utilizan esta conversión. Puedes editar el nombre y la descripción.</span></div>@endif
<label class="check-field catalog-active"><input name="is_active" type="checkbox" value="1" @checked(old('is_active', $record?->is_active ?? true))><span>Presentación activa</span></label>
