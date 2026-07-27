@extends('layouts.setup', ['step' => 3])

@section('title', 'Crear establecimiento')
@section('step-label', 'Paso 3 de 4')
@section('heading', 'Configura el establecimiento matriz')
@section('description', 'Este será el primer local operativo de {{ $company->trade_name ?: $company->legal_name }}.')

@section('content')
    <form method="POST" action="{{ route('setup.establishment.store') }}" class="setup-form">
        @csrf
        <div class="form-grid">
            <label class="field"><span>Código SRI <b>*</b></span><input name="code" value="{{ old('code', '001') }}" inputmode="numeric" minlength="3" maxlength="3" pattern="[0-9]{3}" required autofocus></label>
            <label class="field"><span>Nombre interno <b>*</b></span><input name="name" value="{{ old('name', 'Matriz') }}" maxlength="150" required></label>
            <label class="field field--wide"><span>Nombre comercial del local</span><input name="trade_name" value="{{ old('trade_name', $company->trade_name) }}" maxlength="255"></label>
            <label class="field field--wide"><span>Dirección <b>*</b></span><textarea name="address" required>{{ old('address', $company->address) }}</textarea></label>
            <label class="field"><span>Teléfono</span><input name="phone" value="{{ old('phone', $company->phone) }}" maxlength="30"></label>
            <label class="field"><span>Correo</span><input name="email" type="email" value="{{ old('email', $company->email) }}"></label>
        </div>
        <div class="info-box"><x-icon name="help" /><span>El código identifica al establecimiento dentro del RUC. El local inicial se marca automáticamente como matriz y activo.</span></div>
        <button class="button button--primary button--block setup-submit" type="submit">Crear establecimiento y continuar <x-icon name="arrow-right" /></button>
    </form>
@endsection
