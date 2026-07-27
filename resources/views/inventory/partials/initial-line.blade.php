@php
    $selectedPackageId = (int) ($lineValue['product_package_id'] ?? 0);
    $packageQuantity = $lineValue['package_quantity'] ?? '';
    $selectedPackage = $packageGroups->flatten()->firstWhere('id', $selectedPackageId);
    $lineUnits = $selectedPackage ? $selectedPackage->units_per_package * (int) $packageQuantity : 0;
@endphp

<div class="inventory-initial-line" data-initial-line>
    <label class="field">
        <span>Presentación <b>*</b></span>
        <select name="lines[{{ $lineIndex }}][product_package_id]" data-initial-package required>
            <option value="">Selecciona una presentación</option>
            @foreach ($packageGroups as $group)
                @foreach ($group as $package)
                    <option
                        value="{{ $package->id }}"
                        data-product-id="{{ $package->product_id }}"
                        data-factor="{{ $package->units_per_package }}"
                        @selected($selectedPackageId === $package->id)
                    >{{ $package->presentation->name }} · factor {{ $package->units_per_package }}</option>
                @endforeach
            @endforeach
        </select>
    </label>
    <label class="field">
        <span>Cantidad <b>*</b></span>
        <input name="lines[{{ $lineIndex }}][package_quantity]" value="{{ $packageQuantity }}" data-initial-quantity type="number" min="1" step="1" required>
    </label>
    <div class="inventory-initial-line__subtotal">
        <span>Conversión</span>
        <output data-initial-subtotal>{{ number_format($lineUnits, 0, ',', '.') }} unidades</output>
    </div>
    <button class="icon-button inventory-initial-line__remove" type="button" data-initial-remove aria-label="Quitar presentación"><x-icon name="close" size="17" /></button>
</div>
