<div class="form-grid">
    <label class="field"><span>Código <b>*</b></span><input name="code" value="{{ old('code', $record?->code) }}" maxlength="30" required></label>
    <label class="field"><span>Nombre <b>*</b></span><input name="name" value="{{ old('name', $record?->name) }}" maxlength="120" required></label>
    <label class="field field--wide"><span>Categoría superior</span><select name="parent_id"><option value="">Categoría principal</option>@foreach ($parents as $parent) @if (! $record || ! $parent->is($record))<option value="{{ $parent->id }}" @selected((string) old('parent_id', $record?->parent_id) === (string) $parent->id)>{{ $parent->code }} · {{ $parent->name }}</option>@endif @endforeach</select></label>
    <label class="field field--wide"><span>Descripción</span><textarea name="description" maxlength="255">{{ old('description', $record?->description) }}</textarea></label>
</div>
<label class="check-field catalog-active"><input name="is_active" type="checkbox" value="1" @checked(old('is_active', $record?->is_active ?? true))><span>Categoría activa</span></label>
