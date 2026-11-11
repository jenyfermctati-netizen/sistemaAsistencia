@extends('layouts.app')

@section('title', 'Inicio | CONNEXT')
@section('page-title', 'Inicio')
@section('page-subtitle', 'Resumen de la plataforma multiempresa')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/superadmin.css') }}">
@endpush

@section('content')
    @php
        $totalEmpresas = max(0, (int) $metricas['empresas']);
        $activas = (int) $metricas['activas'];
        $suspendidas = (int) $metricas['suspendidas'];
        $inactivas = (int) $metricas['inactivas'];
        $porcentajeActivas = $totalEmpresas ? round($activas / $totalEmpresas * 100, 2) : 0;
        $porcentajeSuspendidas = $totalEmpresas ? round($suspendidas / $totalEmpresas * 100, 2) : 0;
        $finSuspendidas = $porcentajeActivas + $porcentajeSuspendidas;
        $fondoEmpresas = $totalEmpresas
            ? "conic-gradient(#0f9f77 0% {$porcentajeActivas}%, #d58a24 {$porcentajeActivas}% {$finSuspendidas}%, #9aa7b0 {$finSuspendidas}% 100%)"
            : 'conic-gradient(#e5edef 0% 100%)';
        $maximoDiario = max(1, (int) $tendenciaEmpresas->max('total'));
    @endphp

    <div class="dashboard-page dashboard-modern sa-page sa-dashboard-page">
        <section class="sa-hero">
            <div><span class="sa-eyebrow">PLATAFORMA SAAS</span><h2>Empresas</h2><p>Resumen del estado y crecimiento de tus empresas.</p></div>
            <a class="sa-button" href="{{ route('superadmin.empresas.index', ['section' => 'new']) }}#nueva-empresa"><span aria-hidden="true">+</span> Nueva empresa</a>
        </section>

        <section class="sa-grid sa-dashboard-metrics" aria-label="Resumen de empresas">
            @foreach(['empresas' => 'Total de empresas', 'activas' => 'Activas', 'suspendidas' => 'Suspendidas', 'inactivas' => 'Inactivas', 'nuevas' => 'Nuevas · 30 días'] as $clave => $etiqueta)
                <article class="sa-card"><span>{{ $etiqueta }}</span><strong>{{ number_format($metricas[$clave]) }}</strong></article>
            @endforeach
        </section>

        <section class="dashboard-analytics sa-dashboard-analytics" aria-label="Gráficos de empresas">
            <article class="dashboard-panel dashboard-panel--chart">
                <div class="dashboard-panel__header"><div><span class="dashboard-panel__eyebrow">ESTADO ACTUAL</span><h3>Estado de empresas</h3><p class="dashboard-panel__description">Distribución de las cuentas empresariales.</p></div></div>
                <div class="dashboard-chart-container">
                    <div class="dashboard-donut-chart" role="img" aria-label="{{ $totalEmpresas }} empresas: {{ $activas }} activas, {{ $suspendidas }} suspendidas, {{ $inactivas }} inactivas">
                        <div class="dashboard-donut-chart__ring" style="background: {{ $fondoEmpresas }}">
                            <div class="dashboard-donut-chart__center"><strong>{{ number_format($totalEmpresas) }}</strong><span>empresas</span></div>
                        </div>
                    </div>
                </div>
                <div class="dashboard-chart-legend sa-company-legend">
                    <span class="dashboard-chart-legend__item"><i style="background:#0f9f77"></i><span>Activas <b>{{ number_format($activas) }}</b></span></span>
                    <span class="dashboard-chart-legend__item"><i style="background:#d58a24"></i><span>Suspendidas <b>{{ number_format($suspendidas) }}</b></span></span>
                    <span class="dashboard-chart-legend__item"><i style="background:#9aa7b0"></i><span>Inactivas <b>{{ number_format($inactivas) }}</b></span></span>
                </div>
            </article>

            <article class="dashboard-panel dashboard-panel--chart dashboard-panel--chart-wide">
                <div class="dashboard-panel__header"><div><span class="dashboard-panel__eyebrow">CRECIMIENTO</span><h3>Nuevas empresas</h3><p class="dashboard-panel__description">Registros creados durante los últimos siete días.</p></div></div>
                <div class="dashboard-chart-container sa-signups-chart" role="list" aria-label="Nuevas empresas por día">
                    <div class="sa-signups-bars">
                        @foreach($tendenciaEmpresas as $dia)
                            <div class="sa-signups-bar" role="listitem" aria-label="{{ $dia['label'] }} {{ $dia['fecha'] }}: {{ $dia['total'] }} {{ $dia['total'] === 1 ? 'empresa' : 'empresas' }}">
                                <strong class="sa-signups-bar__value">{{ $dia['total'] }}</strong>
                                <div class="sa-signups-bar__track"><span class="sa-signups-bar__fill" style="height: {{ $dia['total'] > 0 ? max(8, round($dia['total'] / $maximoDiario * 100)) : 2 }}%"></span></div>
                                <strong class="sa-signups-bar__day">{{ $dia['label'] }}</strong>
                                <small>{{ \Carbon\Carbon::parse($dia['fecha'])->format('d/m') }}</small>
                            </div>
                        @endforeach
                    </div>
                </div>
            </article>
        </section>

        <section class="sa-panel">
            <div class="sa-section-heading"><div><span class="sa-eyebrow">DIRECTORIO</span><h2>Empresas recientes</h2></div><a class="sa-button sa-button--quiet" href="{{ route('superadmin.empresas.index') }}#informacion">Ver todas <span aria-hidden="true">→</span></a></div>
            <div class="sa-table-wrap"><table class="sa-table"><thead><tr><th>Empresa</th><th>RUC</th><th>Estado</th><th>Administradores</th><th><span class="sr-only">Acciones</span></th></tr></thead><tbody>
                @forelse($empresasRecientes as $empresa)
                    <tr><td><strong>{{ $empresa->nombre_comercial ?: $empresa->razon_social }}</strong><small>{{ $empresa->razon_social }}</small></td><td>{{ $empresa->ruc }}</td><td><span class="sa-status sa-status--{{ $empresa->estado }}">{{ ucfirst(strtolower($empresa->estado)) }}</span></td><td>{{ number_format($empresa->administradores_count) }}</td><td><a class="sa-icon-button" href="{{ route('superadmin.empresas.show', $empresa) }}" aria-label="Ver {{ $empresa->razon_social }}" title="Ver empresa"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2.5 12s3.3-6 9.5-6 9.5 6 9.5 6-3.3 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg></a></td></tr>
                @empty
                    <tr><td colspan="5" class="sa-empty-state"><strong>Aún no hay empresas</strong><span>Registra una para comenzar a administrar la plataforma.</span></td></tr>
                @endforelse
            </tbody></table></div>
        </section>
    </div>
@endsection
