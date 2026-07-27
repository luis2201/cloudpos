@extends('layouts.setup', ['step' => 2])

@section('title', 'Registrar empresa')
@section('step-label', 'Paso 2 de 4')
@section('heading', 'Identifica a la empresa')
@section('description', 'Registra los datos tributarios y comerciales que representarán al negocio en CloudPOS.')

@section('content')
    <form method="POST" action="{{ route('setup.company.store') }}" class="setup-form">
        @csrf
        <div class="form-grid">
            <label class="field"><span>RUC <b>*</b></span><input name="ruc" value="{{ old('ruc') }}" inputmode="numeric" minlength="13" maxlength="13" pattern="[0-9]{13}" required autofocus></label>
            <div class="field"><span>Perfil tributario</span><div class="check-stack"><span class="check-field"><x-icon name="check" /><span>Persona natural</span></span><span class="check-field"><x-icon name="check" /><span>RIMPE Negocio Popular</span></span><span class="check-field"><x-icon name="check" /><span>No obligado a llevar contabilidad</span></span></div></div>
            <label class="field field--wide"><span>Razón social <b>*</b></span><input name="legal_name" value="{{ old('legal_name') }}" maxlength="255" required></label>
            <label class="field field--wide"><span>Nombre comercial</span><input name="trade_name" value="{{ old('trade_name') }}" maxlength="255"></label>
            <label class="field"><span>Correo de la empresa</span><input name="email" type="email" value="{{ old('email') }}"></label>
            <label class="field"><span>Teléfono</span><input name="phone" value="{{ old('phone') }}" maxlength="30"></label>
            <label class="field field--wide"><span>Dirección de la matriz <b>*</b></span><textarea name="address" required>{{ old('address') }}</textarea></label>
            <label class="check-card field--wide"><input name="is_franchise" type="checkbox" value="1" @checked(old('is_franchise'))><span><strong>El negocio opera como franquicia</strong><small>Déjalo desmarcado para un negocio independiente. Esta condición cambia la aplicación del ICE a fundas plásticas.</small></span></label>
        </div>
        <div class="info-box"><x-icon name="help" /><span>El RUC se valida como un identificador numérico de 13 dígitos. Las ventas de la actividad RIMPE Negocio Popular no desglosan IVA. Para un negocio independiente no aplica ICE a fundas plásticas con este perfil; si se marca como franquicia, CloudPOS activará esa obligación especial.</span></div>
        <button class="button button--primary button--block setup-submit" type="submit">Guardar empresa y continuar <x-icon name="arrow-right" /></button>
    </form>
@endsection
