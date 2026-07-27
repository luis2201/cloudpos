<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#071d49">
    <title>@yield('title', 'Panel') · {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-cloudpos.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar" id="app-sidebar">
            <div class="brand">
                <img class="brand__logo" src="{{ asset('images/logo-cloudpos.png') }}" alt="Logo CloudPOS">
                <div>
                    <strong>Cloud<span>POS</span></strong>
                    <small>Gestión inteligente</small>
                </div>
            </div>

            <nav class="sidebar__nav" aria-label="Navegación principal">
                <span class="nav-heading">General</span>
                <x-nav-item :href="route('dashboard')" icon="home" :active="request()->routeIs('dashboard')">Resumen</x-nav-item>

                <span class="nav-heading">Operación</span>
                @can('income.manage')<x-nav-item :href="route('modules.show', 'ingresos')" icon="income" :active="request()->route('module') === 'ingresos'">Ingresos</x-nav-item>@endcan
                @can('sales.manage')<x-nav-item :href="route('modules.show', 'ventas')" icon="cart" :active="request()->route('module') === 'ventas'">Ventas</x-nav-item>@endcan
                @can('cash.manage')<x-nav-item :href="route('modules.show', 'caja')" icon="cash" :active="request()->route('module') === 'caja'">Caja</x-nav-item>@endcan
                @can('inventory.manage')<x-nav-item :href="route('inventory.index')" icon="box" :active="request()->routeIs('inventory.*')">Inventario</x-nav-item>@endcan
                @can('expenses.manage')<x-nav-item :href="route('modules.show', 'gastos')" icon="receipt" :active="request()->route('module') === 'gastos'">Gastos</x-nav-item>@endcan

                <span class="nav-heading">Administración</span>
                @can('catalog.manage')
                    <x-nav-item :href="route('catalogs.index')" icon="category" :active="request()->routeIs('catalogs.*') && request()->query('section') !== 'presentations'">Catálogo base</x-nav-item>
                    <x-nav-item :href="route('catalogs.index', ['section' => 'presentations'])" icon="package" :active="request()->routeIs('catalogs.*') && request()->query('section') === 'presentations'">Presentaciones</x-nav-item>
                    <x-nav-item :href="route('products.index')" icon="bottle" :active="request()->routeIs('products.*')">Productos</x-nav-item>
                @endcan
                @can('organization.manage')
                    <x-nav-item :href="route('settings.company.edit')" icon="company" :active="request()->routeIs('settings.company.*')">Empresa</x-nav-item>
                    <x-nav-item :href="route('establishments.index')" icon="store" :active="request()->routeIs('establishments.*')">Establecimientos</x-nav-item>
                    <x-nav-item :href="route('warehouses.index')" icon="box" :active="request()->routeIs('warehouses.*')">Bodegas</x-nav-item>
                @endcan
                @can('users.manage')
                    <x-nav-item :href="route('users.index')" icon="users" :active="request()->routeIs('users.*')">Usuarios</x-nav-item>
                    <x-nav-item :href="route('roles.index')" icon="shield" :active="request()->routeIs('roles.*')">Roles y permisos</x-nav-item>
                @endcan
                @can('taxes.manage')<x-nav-item :href="route('settings.tax-rates.index')" icon="tax" :active="request()->routeIs('settings.tax-rates.*')">Impuestos</x-nav-item>@endcan
            </nav>

            <div class="sidebar__footer">
                <div class="sidebar-help">
                    <span class="sidebar-help__icon"><x-icon name="help" /></span>
                    <div><strong>¿Necesitas ayuda?</strong><small>Consulta la guía inicial</small></div>
                </div>
                <span class="version">CloudPOS · v0.1.0</span>
            </div>
        </aside>

        <button class="sidebar-backdrop" type="button" data-sidebar-close aria-label="Cerrar navegación"></button>

        <div class="app-main">
            <header class="topbar">
                <button class="icon-button topbar__menu" type="button" data-sidebar-open aria-label="Abrir navegación">
                    <x-icon name="menu" size="23" />
                </button>
                <div class="topbar__context">
                    <span>{{ $company?->trade_name ?: $company?->legal_name ?: 'Punto de venta' }}</span>
                    <strong>{{ $establishment ? $establishment->code.' · '.$establishment->name : 'Sin establecimiento' }}</strong>
                </div>
                <div class="topbar__actions">
                    <div class="cash-status"><span></span>Caja cerrada</div>
                    <div class="user-menu">
                        @php
                            $initials = collect(preg_split('/\s+/', trim(auth()->user()->name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join('');
                        @endphp
                        <span class="user-menu__avatar">{{ $initials }}</span>
                        <div><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->roles->pluck('name')->join(', ') }}</small></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="icon-button" type="submit" aria-label="Cerrar sesión" title="Cerrar sesión"><x-icon name="logout" size="18" /></button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="content">
                @yield('content')
            </main>
        </div>
    </div>

    @if (session('success'))
        <dialog class="dialog dialog--alert" data-auto-open>
            <div class="dialog__alert-icon"><x-icon name="check" size="28" /></div>
            <h2>Operación completada</h2>
            <p>{{ session('success') }}</p>
            <button class="button button--primary button--block" type="button" data-dialog-close>Entendido</button>
        </dialog>
    @endif

    @if ($errors->any())
        <dialog class="dialog dialog--alert dialog--error" data-auto-open>
            <div class="dialog__alert-icon"><x-icon name="close" size="28" /></div>
            <h2>No se pudo completar</h2>
            <p>{{ $errors->first() }}</p>
            <button class="button button--primary button--block" type="button" data-dialog-close>Revisar</button>
        </dialog>
    @endif

    @stack('dialogs')
</body>
</html>
