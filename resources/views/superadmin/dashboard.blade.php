@extends('layouts.app')
@section('title', 'CONNEXT SaaS')
@section('page-title', 'Panel de plataforma')
@section('page-subtitle', 'Visión global de empresas, usuarios y dispositivos')
@push('styles')<link rel="stylesheet" href="{{ asset('css/pages/superadmin.css') }}">@endpush
@section('content')
<div class="sa-page">
    <section class="sa-hero"><div><h2>CONNEXT Multiempresa</h2><p>Administración central y estado operativo de la plataforma.</p></div><a class="sa-button" href="{{ route('superadmin.empresas.index') }}">Registrar empresa</a></section>
    <section class="sa-grid">
        @foreach(['empresas'=>'Empresas','activas'=>'Activas','suspendidas'=>'Suspendidas','trabajadores'=>'Trabajadores','usuarios'=>'Usuarios','dispositivos'=>'Dispositivos','nuevas'=>'Nuevas (30 días)'] as $clave=>$etiqueta)
            <article class="sa-card"><span>{{ $etiqueta }}</span><strong>{{ number_format($metricas[$clave]) }}</strong></article>
        @endforeach
    </section>
    <section class="sa-panel"><h3>Empresas registradas recientemente</h3><div class="sa-table-wrap"><table class="sa-table"><thead><tr><th>Empresa</th><th>RUC</th><th>Estado</th><th>Trabajadores</th><th>Usuarios</th><th>Dispositivos</th><th></th></tr></thead><tbody>
        @forelse($empresasRecientes as $empresa)<tr><td><strong>{{ $empresa->nombre_comercial ?: $empresa->razon_social }}</strong><br><small>{{ $empresa->razon_social }}</small></td><td>{{ $empresa->ruc }}</td><td><span class="sa-status sa-status--{{ $empresa->estado }}">{{ $empresa->estado }}</span></td><td>{{ $empresa->trabajadores_count }}</td><td>{{ $empresa->users_count }}</td><td>{{ $empresa->dispositivos_biometricos_count }}</td><td><a class="sa-button sa-button--secondary" href="{{ route('superadmin.empresas.show',$empresa) }}">Ver</a></td></tr>
        @empty<tr><td colspan="7" class="sa-muted">Todavía no hay empresas registradas.</td></tr>@endforelse
    </tbody></table></div></section>
</div>
@endsection

