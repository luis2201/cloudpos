@php($prefix = $establishment ? 'edit_'.$establishment->id.'_' : '')
<div class="form-grid">
    <label class="field"><span>Código SRI <b>*</b></span><input name="code" value="{{ old($prefix.'code', $establishment?->code ?? '002') }}" inputmode="numeric" minlength="3" maxlength="3" required></label>
    <label class="field"><span>Nombre interno <b>*</b></span><input name="name" value="{{ old($prefix.'name', $establishment?->name) }}" maxlength="150" required></label>
    <label class="field field--wide"><span>Nombre comercial</span><input name="trade_name" value="{{ old($prefix.'trade_name', $establishment?->trade_name) }}"></label>
    <label class="field field--wide"><span>Dirección <b>*</b></span><textarea name="address" required>{{ old($prefix.'address', $establishment?->address) }}</textarea></label>
    <label class="field"><span>Teléfono</span><input name="phone" value="{{ old($prefix.'phone', $establishment?->phone) }}"></label>
    <label class="field"><span>Correo</span><input name="email" type="email" value="{{ old($prefix.'email', $establishment?->email) }}"></label>
</div>
<div class="check-stack check-stack--inline"><label class="check-field"><input name="is_main" type="checkbox" value="1" @checked($establishment?->is_main)><span>Establecimiento matriz</span></label><label class="check-field"><input name="is_active" type="checkbox" value="1" @checked($establishment?->is_active ?? true)><span>Activo</span></label></div>
