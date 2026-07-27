@extends('layouts.setup', ['step' => 1])

@section('title', 'Crear administrador')
@section('step-label', 'Paso 1 de 4')
@section('heading', 'Crea el administrador principal')
@section('description', 'Será la primera cuenta y tendrá control total para configurar usuarios, roles y la empresa.')

@section('content')
    <form method="POST" action="{{ route('setup.admin.store') }}" class="setup-form">
        @csrf
        <div class="form-grid">
            <label class="field field--wide">
                <span>Nombre completo <b>*</b></span>
                <input name="name" value="{{ old('name') }}" maxlength="120" autocomplete="name" required autofocus>
            </label>
            <label class="field">
                <span>Correo electrónico <b>*</b></span>
                <input name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
            </label>
            <label class="field">
                <span>Teléfono</span>
                <input name="phone" value="{{ old('phone') }}" maxlength="30" autocomplete="tel">
            </label>
            <label class="field">
                <span>Contraseña <b>*</b></span>
                <input name="password" type="password" minlength="10" autocomplete="new-password" required>
            </label>
            <label class="field">
                <span>Confirmar contraseña <b>*</b></span>
                <input name="password_confirmation" type="password" minlength="10" autocomplete="new-password" required>
            </label>
        </div>
        <div class="info-box"><x-icon name="help" /><span>Usa al menos 10 caracteres, incluyendo letras y números. Después de crear esta cuenta, el registro inicial quedará cerrado.</span></div>
        <button class="button button--primary button--block setup-submit" type="submit">Crear administrador y continuar <x-icon name="arrow-right" /></button>
    </form>
@endsection
