<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#071d49">
    <title>@yield('title') · {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-cloudpos.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="setup-body">
    <main class="setup-shell">
        <aside class="setup-aside">
            <div class="setup-brand">
                <img src="{{ asset('images/logo-cloudpos.png') }}" alt="Logo CloudPOS">
                <div><strong>Cloud<span>POS</span></strong><small>Gestión inteligente para tu negocio</small></div>
            </div>

            <div class="setup-aside__message">
                <span class="eyebrow">Configuración segura</span>
                <h1>Deja lista la estructura principal de tu punto de venta.</h1>
                <p>CloudPOS te acompaña paso a paso y no habilita la operación hasta completar los datos esenciales.</p>
            </div>

            @isset($step)
                @php
                    $steps = [
                        1 => ['Administrador', 'Cuenta propietaria'],
                        2 => ['Empresa', 'Identidad tributaria'],
                        3 => ['Establecimiento', 'Local matriz'],
                        4 => ['Bodega', 'Inventario principal'],
                    ];
                @endphp
                <ol class="setup-wizard" aria-label="Progreso de configuración">
                    @foreach ($steps as $number => [$label, $detail])
                        <li @class(['is-current' => $number === $step, 'is-complete' => $number < $step])>
                            <span>@if ($number < $step)<x-icon name="check" size="15" />@else{{ $number }}@endif</span>
                            <div><strong>{{ $label }}</strong><small>{{ $detail }}</small></div>
                        </li>
                    @endforeach
                </ol>
            @endisset
        </aside>

        <section class="setup-content">
            <div class="setup-card">
                <header class="setup-card__header">
                    @hasSection('step-label')<span class="eyebrow">@yield('step-label')</span>@endif
                    <h2>@yield('heading')</h2>
                    <p>@yield('description')</p>
                </header>

                @if ($errors->any())
                    <div class="form-alert" role="alert">
                        <strong>Revisa la información ingresada:</strong>
                        <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif

                @yield('content')
            </div>
            <p class="setup-footnote">Datos protegidos mediante la sesión segura de CloudPOS.</p>
        </section>
    </main>

    @if (session('success'))
        <dialog class="dialog dialog--alert" data-auto-open>
            <div class="dialog__alert-icon"><x-icon name="check" size="28" /></div>
            <h2>Avance guardado</h2>
            <p>{{ session('success') }}</p>
            <button class="button button--primary button--block" type="button" data-dialog-close>Continuar</button>
        </dialog>
    @endif
</body>
</html>
