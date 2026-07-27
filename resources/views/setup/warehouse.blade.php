@extends('layouts.setup', ['step' => 4])

@section('title', 'Crear bodega')
@section('step-label', 'Paso 4 de 4')
@section('heading', 'Crea la bodega principal')
@section('description', 'Se asociará al establecimiento {{ $establishment->code }} · {{ $establishment->name }} para iniciar el control de existencias.')

@section('content')
    <form method="POST" action="{{ route('setup.warehouse.store') }}" class="setup-form">
        @csrf
        <div class="form-grid">
            <label class="field"><span>Código interno <b>*</b></span><input name="code" value="{{ old('code', 'BOD-PRINCIPAL') }}" maxlength="20" pattern="[A-Za-z0-9_-]+" required autofocus></label>
            <label class="field"><span>Nombre <b>*</b></span><input name="name" value="{{ old('name', 'Bodega principal') }}" maxlength="150" required></label>
            <label class="field field--wide"><span>Dirección o ubicación</span><textarea name="address">{{ old('address', $establishment->address) }}</textarea></label>
            <label class="field field--wide"><span>Descripción</span><textarea name="description">{{ old('description', 'Bodega principal del establecimiento matriz') }}</textarea></label>
        </div>
        <div class="info-box"><x-icon name="box" /><span>La bodega quedará activa, será la principal y no permitirá stock negativo. Estas reglas protegen el inventario desde el inicio.</span></div>
        <button class="button button--primary button--block setup-submit" type="submit">Finalizar configuración <x-icon name="check" /></button>
    </form>
@endsection
