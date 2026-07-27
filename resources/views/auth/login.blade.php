@extends('layouts.setup')

@section('title', 'Iniciar sesión')
@section('heading', 'Bienvenido de nuevo')
@section('description', 'Ingresa con tu cuenta para acceder a la operación de CloudPOS.')

@section('content')
    <form method="POST" action="{{ route('login.store') }}" class="setup-form">
        @csrf
        <div class="form-grid form-grid--single">
            <label class="field">
                <span>Correo electrónico <b>*</b></span>
                <input name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                @error('email')<small class="field__error">{{ $message }}</small>@enderror
            </label>
            <label class="field">
                <span>Contraseña <b>*</b></span>
                <input name="password" type="password" autocomplete="current-password" required>
                @error('password')<small class="field__error">{{ $message }}</small>@enderror
            </label>
        </div>
        <label class="check-field"><input name="remember" type="checkbox" value="1"><span>Mantener mi sesión iniciada</span></label>
        <button class="button button--primary button--block setup-submit" type="submit">Ingresar a CloudPOS <x-icon name="arrow-right" /></button>
    </form>
@endsection
