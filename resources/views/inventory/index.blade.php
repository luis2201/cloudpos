@extends('layouts.app')

@section('title', 'Inventario')

@php
    $nextDirection = $direction === 'asc' ? 'desc' : 'asc';
    $sortUrl = fn (string $column) => route('inventory.index', array_merge(request()->query(), [
        'warehouse_id' => $selectedWarehouse->id,
        'sort' => $column,
        'direction' => $sort === $column ? $nextDirection : 'asc',
    ]));
    $packageGroups = $packages->groupBy('product_id');
    $nowForInput = now('America/Guayaquil')->format('Y-m-d\TH:i');
    $initialLines = old('lines');
    $initialLines = is_array($initialLines) && count($initialLines) > 0
        ? $initialLines
        : [['product_package_id' => '', 'package_quantity' => '']];
    $initialTotal = collect($initialLines)->sum(function (array $line) use ($packages): int {
        $package = $packages->firstWhere('id', (int) ($line['product_package_id'] ?? 0));

        return $package ? $package->units_per_package * (int) ($line['package_quantity'] ?? 0) : 0;
    });
@endphp

@section('content')
    <div class="page-heading">
        <div><span class="eyebrow">Control por bodega</span><h1>Inventario</h1><p>Existencias, movimientos y trazabilidad inmutable en unidades de producto.</p></div>
        <div class="heading-actions inventory-actions">
            <button class="button button--secondary" type="button" data-dialog-open="initial-stock"><x-icon name="plus" /> Inventario inicial</button>
            <button class="button button--secondary" type="button" data-dialog-open="new-adjustment"><x-icon name="edit" /> Ajustar conteo</button>
            <button class="button button--secondary" type="button" data-dialog-open="new-transfer" @disabled($warehouses->count() < 2)><x-icon name="arrow-right" /> Transferir</button>
            <button class="button button--primary" type="button" data-dialog-open="new-movement"><x-icon name="income" /> Entrada o salida</button>
        </div>
    </div>

    <form class="panel inventory-warehouse-bar" method="GET" action="{{ route('inventory.index') }}">
        <div><span class="eyebrow">Ubicación consultada</span><strong>{{ $selectedWarehouse->code }} · {{ $selectedWarehouse->name }}</strong><small>{{ $selectedWarehouse->establishment->name }} · Stock negativo {{ $selectedWarehouse->allow_negative_stock ? 'permitido' : 'bloqueado' }}</small></div>
        <label class="select-field"><x-icon name="box" size="17" /><select name="warehouse_id" aria-label="Seleccionar bodega">@foreach ($warehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected($warehouse->is($selectedWarehouse))>{{ $warehouse->code }} · {{ $warehouse->name }}</option>@endforeach</select></label>
        <button class="button button--secondary" type="submit">Cambiar bodega</button>
    </form>

    <section class="metrics-grid" aria-label="Resumen de existencias">
        <x-stat-card label="Productos" :value="$totalProducts" detail="activos en el catálogo" icon="bottle" tone="blue" />
        <x-stat-card label="Con existencias" :value="$positiveProducts" detail="productos sobre cero" icon="check" tone="green" />
        <x-stat-card label="Sin existencias" :value="$zeroProducts" detail="productos en cero" icon="box" tone="orange" />
        <x-stat-card label="Unidades físicas" :value="$totalUnits" :detail="$negativeProducts.' producto(s) en negativo'" icon="ruler" tone="purple" />
    </section>

    <section class="panel table-panel">
        <div class="panel__header panel__header--table"><div><span class="eyebrow">Saldo actual</span><h2>Existencias en {{ $selectedWarehouse->name }}</h2></div><span class="record-count">{{ $stocks->total() }} productos</span></div>
        <form class="filters filters--wrap" method="GET" action="{{ route('inventory.index') }}">
            <input type="hidden" name="warehouse_id" value="{{ $selectedWarehouse->id }}">
            <label class="search-field"><x-icon name="search" size="17" /><input name="search" value="{{ $search }}" placeholder="Producto, código o barras"><span class="sr-only">Buscar producto</span></label>
            <label class="select-field"><select name="stock_status"><option value="">Todos los saldos</option><option value="positive" @selected($stockStatus === 'positive')>Con existencias</option><option value="zero" @selected($stockStatus === 'zero')>En cero</option><option value="negative" @selected($stockStatus === 'negative')>En negativo</option></select></label>
            <button class="button button--secondary" type="submit">Aplicar</button>
            @if ($search !== '' || $stockStatus !== '')<a class="button button--ghost" href="{{ route('inventory.index', ['warehouse_id' => $selectedWarehouse->id]) }}">Limpiar</a>@endif
        </form>
        <div class="table-wrap"><table class="data-table"><thead><tr>
            <th><a href="{{ $sortUrl('code') }}">Código <x-icon name="sort" size="13" /></a></th>
            <th><a href="{{ $sortUrl('name') }}">Producto <x-icon name="sort" size="13" /></a></th>
            <th>Categoría</th><th>Presentación base</th>
            <th><a href="{{ $sortUrl('stock') }}">Existencia <x-icon name="sort" size="13" /></a></th><th>Política</th>
        </tr></thead><tbody>
            @forelse ($stocks as $product)
                <tr>
                    <td data-label="Código"><strong class="code-chip">{{ $product->code }}</strong></td>
                    <td data-label="Producto"><div class="table-primary"><span class="table-primary__icon"><x-icon name="bottle" /></span><span><strong>{{ $product->name }}</strong><small>{{ $product->brand?->name ?: 'Sin marca' }}</small></span></div></td>
                    <td data-label="Categoría">{{ $product->category->name }}</td>
                    <td data-label="Presentación base">{{ $product->basePackage?->presentation?->name ?: 'Sin presentación' }}</td>
                    <td data-label="Existencia"><strong @class(['stock-number', 'is-negative' => (int) $product->stock_quantity < 0, 'is-zero' => (int) $product->stock_quantity === 0])>{{ number_format((int) $product->stock_quantity, 0, ',', '.') }}</strong><small class="price-note">unidad(es)</small></td>
                    <td data-label="Política"><span class="status-badge {{ $selectedWarehouse->allow_negative_stock ? 'status-badge--info' : 'status-badge--muted' }}">{{ $selectedWarehouse->allow_negative_stock ? 'Negativo permitido' : 'Negativo bloqueado' }}</span></td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty-state"><span><x-icon name="box" /></span><strong>Sin resultados</strong><small>Cambia los filtros de existencias.</small></div></td></tr>
            @endforelse
        </tbody></table></div>
        <x-pagination :paginator="$stocks" />
    </section>

    <section class="panel table-panel inventory-section-gap">
        <div class="panel__header panel__header--table"><div><span class="eyebrow">Trazabilidad append-only</span><h2>Kárdex de {{ $selectedWarehouse->name }}</h2></div><span class="record-count">{{ $ledger->total() }} movimientos</span></div>
        <form class="filters filters--wrap" method="GET" action="{{ route('inventory.index') }}">
            <input type="hidden" name="warehouse_id" value="{{ $selectedWarehouse->id }}">
            <label class="search-field"><x-icon name="search" size="17" /><input name="ledger_search" value="{{ $ledgerSearch }}" placeholder="Producto, motivo o referencia"><span class="sr-only">Buscar en kárdex</span></label>
            <label class="select-field"><select name="ledger_type"><option value="">Todos los movimientos</option>@foreach ($movementTypes as $value => $label)<option value="{{ $value }}" @selected($ledgerType === $value)>{{ $label }}</option>@endforeach</select></label>
            <label class="select-field"><select name="ledger_product_id"><option value="">Todos los productos</option>@foreach ($products as $product)<option value="{{ $product->id }}" @selected((string) $ledgerProductId === (string) $product->id)>{{ $product->name }}</option>@endforeach</select></label>
            <label class="field field--compact"><span>Desde</span><input name="date_from" type="date" value="{{ $dateFrom }}"></label>
            <label class="field field--compact"><span>Hasta</span><input name="date_to" type="date" value="{{ $dateTo }}"></label>
            <button class="button button--secondary" type="submit">Aplicar</button>
        </form>
        <div class="table-wrap"><table class="data-table"><thead><tr><th>Fecha</th><th>Movimiento</th><th>Producto</th><th>Empaque</th><th>Cambio</th><th>Saldo</th><th>Auditoría</th></tr></thead><tbody>
            @forelse ($ledger as $movement)
                <tr>
                    <td data-label="Fecha"><strong>{{ $movement->operation->occurred_at->format('d/m/Y') }}</strong><small class="price-note">{{ $movement->operation->occurred_at->format('H:i') }}</small></td>
                    <td data-label="Movimiento"><span class="status-badge status-badge--info">{{ $movementTypes[$movement->operation->type] }}</span><small class="price-note">INV-{{ str_pad((string) $movement->operation->id, 8, '0', STR_PAD_LEFT) }}</small>@if ($movement->operation->type === 'transfer')<small class="price-note">{{ $movement->operation->sourceWarehouse->code }} → {{ $movement->operation->destinationWarehouse->code }}</small>@endif</td>
                    <td data-label="Producto"><strong>{{ $movement->product->name }}</strong><small class="price-note">{{ $movement->product->code }}</small></td>
                    <td data-label="Empaque">@if ($movement->productPackage)<strong>{{ $movement->package_quantity }} × {{ $movement->productPackage->presentation->name }}</strong><small class="price-note">Factor histórico: {{ $movement->units_per_package }}</small>@else<span>Conteo físico</span>@endif</td>
                    <td data-label="Cambio"><strong @class(['movement-delta', 'is-entry' => $movement->quantity_delta > 0, 'is-exit' => $movement->quantity_delta < 0])>{{ $movement->quantity_delta > 0 ? '+' : '' }}{{ $movement->quantity_delta }}</strong></td>
                    <td data-label="Saldo"><strong>{{ $movement->balance_before }} → {{ $movement->balance_after }}</strong></td>
                    <td data-label="Auditoría"><strong>{{ $movement->operation->creator->name }}</strong><small class="price-note">{{ $movement->operation->reason }}</small><small class="price-note">{{ $movement->operation->reference ?: 'Sin referencia' }}</small></td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="empty-state"><span><x-icon name="receipt" /></span><strong>Kárdex sin movimientos</strong><small>Carga el inventario inicial o registra la primera entrada.</small></div></td></tr>
            @endforelse
        </tbody></table></div>
        <x-pagination :paginator="$ledger" />
    </section>

    <div class="info-box catalog-reference"><x-icon name="shield" /><span>El kárdex no admite edición ni eliminación. Si un movimiento fue incorrecto, registra un ajuste compensatorio con su motivo y referencia.</span></div>
@endsection

@push('dialogs')
    <dialog class="dialog dialog--wide" id="initial-stock" @if (old('lines') !== null) data-auto-open @endif><form method="POST" action="{{ route('inventory.initial.store') }}" data-initial-stock-form>@csrf
        <div class="dialog__header"><div><span class="dialog__icon"><x-icon name="box" /></span><span><span class="eyebrow">Puesta en marcha</span><h2>Ingresar inventario inicial</h2></span></div><button class="icon-button" type="button" data-dialog-close><x-icon name="close" /></button></div>
        <div class="dialog__body"><div class="form-grid">
            <label class="field"><span>Bodega <b>*</b></span><select name="warehouse_id" required>@foreach ($warehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected((int) old('warehouse_id', $selectedWarehouse->id) === $warehouse->id)>{{ $warehouse->code }} · {{ $warehouse->name }}</option>@endforeach</select></label>
            <label class="field"><span>Fecha y hora <b>*</b></span><input name="occurred_at" type="datetime-local" max="{{ $nowForInput }}" value="{{ old('occurred_at', $nowForInput) }}" required></label>
            <label class="field field--wide"><span>Producto <b>*</b></span><select name="product_id" data-initial-product required><option value="">Selecciona un producto</option>@foreach ($packageGroups as $group)<option value="{{ $group->first()->product_id }}" @selected((int) old('product_id') === $group->first()->product_id)>{{ $group->first()->product->name }} · {{ $group->first()->product->code }}</option>@endforeach</select></label>
            <section class="inventory-initial-builder field--wide" aria-label="Presentaciones del conteo inicial">
                <div class="inventory-initial-builder__header"><div><strong>Presentaciones contadas</strong><small>Combina cajas, paquetes y unidades del mismo producto.</small></div><button class="button button--secondary button--small" type="button" data-initial-add><x-icon name="plus" size="16" /> Agregar presentación</button></div>
                <div class="inventory-initial-lines" data-initial-lines>
                    @foreach ($initialLines as $lineIndex => $lineValue)
                        @include('inventory.partials.initial-line', compact('lineIndex', 'lineValue', 'packageGroups'))
                    @endforeach
                </div>
                <div class="inventory-initial-total"><span>Total que ingresará al inventario</span><output data-initial-total>{{ number_format($initialTotal, 0, ',', '.') }} unidades</output></div>
                <template data-initial-line-template>@include('inventory.partials.initial-line', ['lineIndex' => '__INDEX__', 'lineValue' => [], 'packageGroups' => $packageGroups])</template>
            </section>
            <label class="field"><span>Referencia</span><input name="reference" value="{{ old('reference') }}" maxlength="60" placeholder="Acta o documento"></label>
            <label class="field field--wide"><span>Motivo <b>*</b></span><textarea name="reason" maxlength="255" required>{{ old('reason', 'Conteo inicial de existencias') }}</textarea></label>
        </div><div class="info-box"><x-icon name="shield" /><span>La combinación se registra en una sola operación. Solo puede cargarse una vez por producto y bodega; después se utilizan entradas o ajustes.</span></div></div>
        <div class="dialog__footer"><button class="button button--ghost" type="button" data-dialog-close>Cancelar</button><button class="button button--primary" type="submit">Registrar saldo inicial</button></div>
    </form></dialog>

    <dialog class="dialog" id="new-movement"><form method="POST" action="{{ route('inventory.movements.store') }}">@csrf
        <div class="dialog__header"><div><span class="dialog__icon"><x-icon name="income" /></span><span><span class="eyebrow">Movimiento manual</span><h2>Registrar entrada o salida</h2></span></div><button class="icon-button" type="button" data-dialog-close><x-icon name="close" /></button></div>
        <div class="dialog__body"><div class="form-grid">
            <label class="field"><span>Tipo <b>*</b></span><select name="type" required><option value="entry">Entrada</option><option value="exit">Salida</option></select></label>
            <label class="field"><span>Bodega <b>*</b></span><select name="warehouse_id" required>@foreach ($warehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected($warehouse->is($selectedWarehouse))>{{ $warehouse->code }} · {{ $warehouse->name }}</option>@endforeach</select></label>
            <label class="field field--wide"><span>Producto y presentación <b>*</b></span><select name="product_package_id" required><option value="">Selecciona un empaque</option>@foreach ($packageGroups as $group)<optgroup label="{{ $group->first()->product->name }}">@foreach ($group as $package)<option value="{{ $package->id }}">{{ $package->presentation->name }} · factor {{ $package->units_per_package }}</option>@endforeach</optgroup>@endforeach</select></label>
            <label class="field"><span>Cantidad de empaques <b>*</b></span><input name="package_quantity" type="number" min="1" step="1" required></label>
            <label class="field"><span>Fecha y hora <b>*</b></span><input name="occurred_at" type="datetime-local" max="{{ $nowForInput }}" value="{{ $nowForInput }}" required></label>
            <label class="field"><span>Referencia</span><input name="reference" maxlength="60"></label>
            <label class="field field--wide"><span>Motivo <b>*</b></span><textarea name="reason" maxlength="255" required></textarea></label>
        </div></div><div class="dialog__footer"><button class="button button--ghost" type="button" data-dialog-close>Cancelar</button><button class="button button--primary" type="submit">Registrar movimiento</button></div>
    </form></dialog>

    <dialog class="dialog" id="new-adjustment"><form method="POST" action="{{ route('inventory.adjustments.store') }}">@csrf
        <div class="dialog__header"><div><span class="dialog__icon"><x-icon name="edit" /></span><span><span class="eyebrow">Conteo físico</span><h2>Ajustar existencia</h2></span></div><button class="icon-button" type="button" data-dialog-close><x-icon name="close" /></button></div>
        <div class="dialog__body"><div class="form-grid">
            <label class="field"><span>Bodega <b>*</b></span><select name="warehouse_id" required>@foreach ($warehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected($warehouse->is($selectedWarehouse))>{{ $warehouse->code }} · {{ $warehouse->name }}</option>@endforeach</select></label>
            <label class="field"><span>Producto <b>*</b></span><select name="product_id" required><option value="">Selecciona un producto</option>@foreach ($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select></label>
            <label class="field"><span>Conteo real en unidades <b>*</b></span><input name="counted_quantity" type="number" min="0" step="1" required></label>
            <label class="field"><span>Fecha y hora <b>*</b></span><input name="occurred_at" type="datetime-local" max="{{ $nowForInput }}" value="{{ $nowForInput }}" required></label>
            <label class="field"><span>Referencia</span><input name="reference" maxlength="60" placeholder="Acta de ajuste"></label>
            <label class="field field--wide"><span>Motivo obligatorio <b>*</b></span><textarea name="reason" maxlength="255" required></textarea></label>
        </div><div class="info-box"><x-icon name="ruler" /><span>Registra el total contado, no la diferencia. CloudPOS calculará la entrada o salida necesaria.</span></div></div>
        <div class="dialog__footer"><button class="button button--ghost" type="button" data-dialog-close>Cancelar</button><button class="button button--primary" type="submit">Aplicar ajuste</button></div>
    </form></dialog>

    <dialog class="dialog" id="new-transfer"><form method="POST" action="{{ route('inventory.transfers.store') }}">@csrf
        <div class="dialog__header"><div><span class="dialog__icon"><x-icon name="arrow-right" /></span><span><span class="eyebrow">Entre bodegas</span><h2>Transferir existencias</h2></span></div><button class="icon-button" type="button" data-dialog-close><x-icon name="close" /></button></div>
        <div class="dialog__body"><div class="form-grid">
            <label class="field"><span>Bodega origen <b>*</b></span><select name="source_warehouse_id" required>@foreach ($warehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected($warehouse->is($selectedWarehouse))>{{ $warehouse->code }} · {{ $warehouse->name }}</option>@endforeach</select></label>
            <label class="field"><span>Bodega destino <b>*</b></span><select name="destination_warehouse_id" required><option value="">Selecciona destino</option>@foreach ($warehouses as $warehouse)<option value="{{ $warehouse->id }}" @disabled($warehouse->is($selectedWarehouse))>{{ $warehouse->code }} · {{ $warehouse->name }}</option>@endforeach</select></label>
            <label class="field field--wide"><span>Producto y presentación <b>*</b></span><select name="product_package_id" required><option value="">Selecciona un empaque</option>@foreach ($packageGroups as $group)<optgroup label="{{ $group->first()->product->name }}">@foreach ($group as $package)<option value="{{ $package->id }}">{{ $package->presentation->name }} · factor {{ $package->units_per_package }}</option>@endforeach</optgroup>@endforeach</select></label>
            <label class="field"><span>Cantidad de empaques <b>*</b></span><input name="package_quantity" type="number" min="1" step="1" required></label>
            <label class="field"><span>Fecha y hora <b>*</b></span><input name="occurred_at" type="datetime-local" max="{{ $nowForInput }}" value="{{ $nowForInput }}" required></label>
            <label class="field"><span>Referencia</span><input name="reference" maxlength="60" placeholder="Guía interna"></label>
            <label class="field field--wide"><span>Motivo <b>*</b></span><textarea name="reason" maxlength="255" required>Transferencia entre bodegas</textarea></label>
        </div><div class="info-box"><x-icon name="shield" /><span>La salida y la entrada se registran en una sola transacción. Si falla alguna validación, ninguna bodega cambia.</span></div></div>
        <div class="dialog__footer"><button class="button button--ghost" type="button" data-dialog-close>Cancelar</button><button class="button button--primary" type="submit">Confirmar transferencia</button></div>
    </form></dialog>
@endpush
