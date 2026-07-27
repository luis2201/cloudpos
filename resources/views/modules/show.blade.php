@extends('layouts.app')

@section('title', $definition['title'])

@section('content')
    <div class="page-heading">
        <div>
            <span class="eyebrow">Módulo operativo</span>
            <h1>{{ $definition['title'] }}</h1>
            <p>{{ $definition['description'] }}</p>
        </div>
    </div>

    <section class="module-preview panel">
        <div class="module-preview__icon"><x-icon :name="$definition['icon']" size="34" /></div>
        <span class="status-badge status-badge--info">Próxima etapa</span>
        <h2>Estructura preparada</h2>
        <p>Este módulo ya forma parte de la navegación principal. Sus reglas de negocio se implementarán en las siguientes decisiones del proyecto.</p>
        <div class="feature-list">
            @foreach ($definition['features'] as $index => $feature)
                <div><span>{{ $index + 1 }}</span>{{ $feature }}</div>
            @endforeach
        </div>
        <a href="{{ route('dashboard') }}" class="button button--secondary">Volver al resumen</a>
    </section>
@endsection
