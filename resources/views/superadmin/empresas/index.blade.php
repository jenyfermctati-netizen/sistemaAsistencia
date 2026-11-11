@extends('layouts.app')
@section('title', 'Empresas | CONNEXT')
@section('page-title', 'Empresas')
@section('page-subtitle', 'Cada empresa, con su información, responsables y configuración')
@push('styles')<link rel="stylesheet" href="{{ asset('css/pages/superadmin.css') }}">@endpush
@section('content')
@php
    $seccionDestino = in_array(request('seccion'), ['informacion', 'administradores', 'configuracion'], true) ? request('seccion') : 'informacion';
    $etiquetaSeccion = ['informacion'=>'Información', 'administradores'=>'Administradores', 'configuracion'=>'Configuración'][$seccionDestino];
@endphp
<div class="sa-page">
    <section class="sa-hero">
        <div><h2>Tus empresas</h2><p>Agrega nuevas empresas y administra cada una desde su propia ficha.</p></div>
        <a class="sa-button" href="{{ route('superadmin.empresas.create') }}"><x-ui-icon name="plus" /> Agregar empresa</a>
    </section>
    @if(request()->has('seccion'))
        <div class="sa-selection-note"><x-ui-icon name="eye" /><span>Selecciona una empresa para abrir <strong>{{ $etiquetaSeccion }}</strong>.</span></div>
    @endif
    <section class="sa-panel">
        <form class="sa-company-filters" method="GET">
            @if(request()->has('seccion'))<input type="hidden" name="seccion" value="{{ $seccionDestino }}">@endif
            <label>Buscar empresa<input name="buscar" value="{{ request('buscar') }}" placeholder="Nombre o RUC"></label>
            <label>Estado<select name="estado"><option value="">Todos los estados</option>@foreach(['ACTIVA'=>'Activas', 'SUSPENDIDA'=>'Suspendidas', 'INACTIVA'=>'Inactivas'] as $valor=>$texto)<option value="{{ $valor }}" @selected(request('estado') === $valor)>{{ $texto }}</option>@endforeach</select></label>
            <label>Ordenar por<select name="orden"><option value="nombre" @selected(request('orden', 'nombre') === 'nombre')>Nombre (A–Z)</option><option value="recientes" @selected(request('orden') === 'recientes')>Más recientes</option></select></label>
            <div class="sa-actions"><button class="sa-button" aria-label="Aplicar filtros"><x-ui-icon name="filter" /> Buscar</button><a class="sa-button sa-button--secondary" title="Limpiar filtros" aria-label="Limpiar filtros" href="{{ route('superadmin.empresas.index', request()->has('seccion') ? ['seccion' => $seccionDestino] : []) }}"><x-ui-icon name="reset" /></a></div>
        </form>
    </section>
    <div class="sa-heading"><h3>Empresas registradas</h3><span class="sa-muted">{{ $empresas->total() }} {{ $empresas->total() === 1 ? 'empresa' : 'empresas' }}</span></div>
    <section class="sa-company-cards" aria-label="Empresas registradas">
        @forelse($empresas as $empresa)
            <article class="sa-company-card">
                <div class="sa-company-card-top"><span class="sa-company-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($empresa->nombre_comercial ?: $empresa->razon_social, 0, 2)) }}</span><span class="sa-status sa-status--{{ $empresa->estado }}">{{ ucfirst(strtolower($empresa->estado)) }}</span></div>
                <h3>{{ $empresa->nombre_comercial ?: $empresa->razon_social }}</h3>
                <p class="sa-muted">RUC {{ $empresa->ruc }}</p>
                <div class="sa-company-counts"><span><strong>{{ $empresa->trabajadores_count }}</strong> trabajadores</span><span><strong>{{ $empresa->users_count }}</strong> usuarios</span><span><strong>{{ $empresa->dispositivos_biometricos_count }}</strong> dispositivos</span></div>
                <a class="sa-button sa-company-open" href="{{ route('superadmin.empresas.show', ['empresa'=>$empresa, 'seccion'=>$seccionDestino]) }}"><x-ui-icon name="eye" /> {{ request()->has('seccion') ? 'Ver '.mb_strtolower($etiquetaSeccion) : 'Abrir empresa' }}<span class="sr-only">: {{ $empresa->razon_social }}</span></a>
                <nav class="sa-company-shortcuts" aria-label="Accesos de {{ $empresa->razon_social }}">
                    <a href="{{ route('superadmin.empresas.show', ['empresa'=>$empresa, 'seccion'=>'informacion']) }}" title="Información" aria-label="Información de {{ $empresa->razon_social }}"><x-ui-icon name="report" /></a>
                    <a href="{{ route('superadmin.empresas.show', ['empresa'=>$empresa, 'seccion'=>'administradores']) }}" title="Administradores" aria-label="Administradores de {{ $empresa->razon_social }}"><x-ui-icon name="users" /></a>
                    <a href="{{ route('superadmin.empresas.show', ['empresa'=>$empresa, 'seccion'=>'configuracion']) }}" title="Configuración" aria-label="Configuración de {{ $empresa->razon_social }}"><x-ui-icon name="settings" /></a>
                </nav>
            </article>
        @empty
            <div class="sa-panel sa-company-empty"><x-ui-icon name="users" /><h3>{{ request()->filled('buscar') || request()->filled('estado') ? 'No hay coincidencias' : 'Agrega tu primera empresa' }}</h3><p class="sa-muted">Cada empresa tendrá sus administradores y configuración independiente.</p><a class="sa-button" href="{{ route('superadmin.empresas.create') }}"><x-ui-icon name="plus" /> Agregar empresa</a></div>
        @endforelse
    </section>
    {{ $empresas->links() }}
</div>
@endsection