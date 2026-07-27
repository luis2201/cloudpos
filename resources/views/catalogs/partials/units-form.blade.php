<div class="form-grid">
    <label class="field"><span>Código <b>*</b></span><input name="code" value="{{ old('code', $record?->code) }}" maxlength="12" required></label>
    <label class="field"><span>Símbolo <b>*</b></span><input name="symbol" value="{{ old('symbol', $record?->symbol) }}" maxlength="12" required></label>
    <label class="field"><span>Nombre <b>*</b></span><input name="name" value="{{ old('name', $record?->name) }}" maxlength="80" required></label>
    <label class="field"><span>Dimensión <b>*</b></span><select name="dimension" required>@foreach ($measurementDimensions as $value => $label)<option value="{{ $value }}" @selected(old('dimension', $record?->dimension) === $value)>{{ $label }}</option>@endforeach</select></label>
</div>
<div class="check-stack check-stack--inline"><label class="check-field"><input name="allows_decimals" type="checkbox" value="1" @checked(old('allows_decimals', $record?->allows_decimals))><span>Permite cantidades decimales</span></label><label class="check-field"><input name="is_active" type="checkbox" value="1" @checked(old('is_active', $record?->is_active ?? true))><span>Unidad activa</span></label></div>
