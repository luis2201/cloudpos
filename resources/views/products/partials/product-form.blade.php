<div class="section-divider"><span>Identificación</span></div>
<div class="form-grid">
    <label class="field"><span>Código interno <b>*</b></span><input name="code" value="{{ old('code', $product?->code) }}" maxlength="40" required></label>
    <label class="field"><span>Nombre <b>*</b></span><input name="name" value="{{ old('name', $product?->name) }}" maxlength="180" required></label>
    <label class="field"><span>Categoría <b>*</b></span><select name="category_id" required><option value="">Selecciona una categoría</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected((string) old('category_id', $product?->category_id) === (string) $category->id)>{{ $category->parent ? $category->parent->name.' · ' : '' }}{{ $category->name }}</option>@endforeach</select></label>
    <label class="field"><span>Marca</span><select name="brand_id"><option value="">Sin marca específica</option>@foreach ($brands as $brand)<option value="{{ $brand->id }}" @selected((string) old('brand_id', $product?->brand_id) === (string) $brand->id)>{{ $brand->name }}</option>@endforeach</select></label>
    <label class="field"><span>Tipo de producto <b>*</b></span><select name="product_kind" required>@foreach ($productKinds as $value => $label)<option value="{{ $value }}" @selected(old('product_kind', $product?->product_kind ?? 'alcoholic_beverage') === $value)>{{ $label }}</option>@endforeach</select></label>
    <label class="field"><span>Grado alcohólico</span><div class="input-suffix"><input name="alcohol_by_volume" type="number" min="0.01" max="100" step="0.01" value="{{ old('alcohol_by_volume', $product?->alcohol_by_volume) }}"><span>% Alc.</span></div></label>
    <label class="field field--wide"><span>Descripción</span><textarea name="description" maxlength="255">{{ old('description', $product?->description) }}</textarea></label>
</div>

<div class="section-divider"><span>Tratamiento tributario de venta</span></div>
<div class="form-grid">
    <label class="field"><span>Tratamiento IVA <b>*</b></span><select name="vat_treatment" required>@foreach ($vatTreatments as $value => $label)<option value="{{ $value }}" @selected(old('vat_treatment', $product?->vat_treatment ?? 'general') === $value)>{{ $label }}{{ $value === 'general' && $currentTaxRate ? ' · '.$currentTaxRate->rate.'%' : '' }}</option>@endforeach</select></label>
    <label class="field"><span>Referencia ICE recibida en compra <b>*</b></span><select name="ice_treatment" required>@foreach ($iceTreatments as $value => $label)<option value="{{ $value }}" @selected(old('ice_treatment', $product?->ice_treatment ?? 'alcoholic_beverage') === $value)>{{ $label }}</option>@endforeach</select></label>
    <label class="field field--wide"><span>Código ICE / trazabilidad de origen</span><input name="ice_code" value="{{ old('ice_code', $product?->ice_code) }}" maxlength="40" placeholder="Opcional, según la información del proveedor"></label>
</div>
@if ($shouldBreakDownVat)
    <div class="info-box"><x-icon name="tax" /><span>La licorería es comercializadora minorista: el POS calcula únicamente el IVA aplicable. El ICE, cuando existe, ya fue generado por el fabricante o importador y se conserva aquí solo como referencia de compra.</span></div>
@else
    <div class="info-box"><x-icon name="tax" /><span>El producto conserva su clasificación de IVA como referencia fiscal, pero las ventas acogidas a RIMPE Negocio Popular no lo desglosan. Tampoco se recalcula el ICE recibido en la compra.</span></div>
@endif

@if ($includePackage)
    <div class="section-divider"><span>Presentación y precio inicial</span></div>
    <div class="form-grid">
        <label class="field"><span>Presentación de venta <b>*</b></span><select name="presentation_id" required><option value="">Selecciona una presentación</option>@foreach ($presentations as $presentation) @php($factor = $presentation->measurementUnit->dimension === 'quantity' ? (int) $presentation->quantity : 1)<option value="{{ $presentation->id }}" @selected((string) old('presentation_id') === (string) $presentation->id)>{{ $presentation->name }} · {{ $factor }} unidad(es) de inventario</option>@endforeach</select></label>
        <label class="field"><span>GTIN/EAN/UPC del fabricante</span><input name="barcode" value="{{ old('barcode') }}" maxlength="80" inputmode="numeric" placeholder="Escanea o digita el código de la etiqueta"></label>
        <label class="field"><span>Precio base registrado <b>*</b></span><div class="input-suffix"><span>$</span><input name="price_before_tax" type="number" min="0" step="0.0001" value="{{ old('price_before_tax') }}" required></div></label>
    </div>
@endif

<label class="check-field catalog-active"><input name="is_active" type="checkbox" value="1" @checked(old('is_active', $product?->is_active ?? true))><span>Producto activo</span></label>
@if (($product?->product_kind ?? 'alcoholic_beverage') === 'alcoholic_beverage')<div class="no-expiration-note"><x-icon name="check" /><span>Las bebidas alcohólicas se controlarán sin fecha de caducidad.</span></div>@endif
