<section class="dashboard-metrics" aria-label="Resumen del día">
    <a class="dashboard-metric dashboard-metric--petrol" href="{{ route('trabajadores.index') }}" aria-label="Ver trabajadores activos">
        <span class="dashboard-metric__label">Equipo activo</span>
        <strong class="dashboard-metric__value">{{ $metrics['trabajadores'] }}</strong>
        <span class="dashboard-metric__caption">trabajadores en {{ $metrics['areas'] }} áreas</span>
    </a>

    <a class="dashboard-metric dashboard-metric--blue" href="{{ route('asistencias.index', ['fecha_desde' => today()->toDateString(), 'fecha_hasta' => today()->toDateString()]) }}" aria-label="Ver asistencias de hoy">
        <span class="dashboard-metric__label">Asistencias de hoy</span>
        <strong class="dashboard-metric__value">{{ $metrics['asistencias'] }}</strong>
        <span class="dashboard-metric__caption">{{ $metrics['puntuales'] }} registros puntuales</span>
    </a>

    <a class="dashboard-metric dashboard-metric--sand" href="{{ route('asistencias.index', ['fecha_desde' => today()->toDateString(), 'fecha_hasta' => today()->toDateString(), 'estado' => 'TARDANZA']) }}" aria-label="Ver tardanzas de hoy">
        <span class="dashboard-metric__label">Tardanzas de hoy</span>
        <strong class="dashboard-metric__value">{{ $metrics['tardanzas'] }}</strong>
        <span class="dashboard-metric__caption">requieren seguimiento</span>
    </a>

    <a class="dashboard-metric dashboard-metric--graphite" href="{{ route('marcaciones.index', ['fecha_desde' => today()->toDateString(), 'fecha_hasta' => today()->toDateString(), 'estado' => 'ACTIVA']) }}" aria-label="Ver marcaciones válidas de hoy">
        <span class="dashboard-metric__label">Marcaciones</span>
        <strong class="dashboard-metric__value">{{ $metrics['marcaciones'] }}</strong>
        <span class="dashboard-metric__caption">entradas y salidas válidas</span>
    </a>
</section>

<div class="dashboard-columns">
    <section class="dashboard-panel dashboard-panel--wide">
        <div class="dashboard-panel__header">
            <div>
                <span class="dashboard-panel__eyebrow">OPERACIÓN POR ÁREA</span>
                <h3>Cobertura de asistencia de hoy</h3>
            </div>
            <a class="dashboard-text-link" href="{{ route('asistencias.index') }}">Ver asistencias <span>→</span></a>
        </div>

        <div class="dashboard-area-list">
            @forelse($areasResumen as $area)
                <article class="dashboard-area">
                    <div class="dashboard-area__main">
                        <div>
                            <strong>{{ $area->nombre }}</strong>
                            <span>{{ $area->trabajadores_activos }} trabajadores activos</span>
                        </div>
                        <span class="dashboard-area__percentage">{{ $area->cobertura }}%</span>
                    </div>
                    <div class="dashboard-progress" aria-label="{{ $area->cobertura }} por ciento de cobertura de asistencia">
                        <span style="width: {{ $area->cobertura }}%"></span>
                    </div>
                    <div class="dashboard-area__details">
                        <span>{{ $area->asistencias_hoy }} asistieron</span>
                        <span>{{ $area->tardanzas_hoy }} tardanzas</span>
                        <span>{{ $area->sin_asistencia_hoy }} sin asistencia</span>
                        <span>{{ $area->justificadas_hoy }} justificados</span>
                        <span>{{ $area->sin_procesar }} pendientes de registrar</span>
                    </div>
                </article>
            @empty
                <div class="dashboard-empty">Aún no hay áreas activas registradas.</div>
            @endforelse
        </div>
    </section>

    <aside class="dashboard-panel">
        <div class="dashboard-panel__header">
            <div>
                <span class="dashboard-panel__eyebrow">POR ATENDER</span>
                <h3>Bandeja administrativa</h3>
            </div>
        </div>

        <div class="dashboard-attention-list">
            <a href="{{ route('solicitudes.index', ['estado' => 'PENDIENTE']) }}" class="dashboard-attention">
                <span class="dashboard-attention__marker dashboard-attention__marker--blue">SO</span>
                <span><strong>Solicitudes</strong><small>Permisos y justificaciones</small></span>
                <b>{{ $alerts['solicitudes'] }}</b>
            </a>
            <a href="{{ route('vacaciones.index', ['estado' => 'PENDIENTE']) }}" class="dashboard-attention">
                <span class="dashboard-attention__marker dashboard-attention__marker--sand">VA</span>
                <span><strong>Vacaciones</strong><small>Solicitudes por revisar</small></span>
                <b>{{ $alerts['vacaciones'] }}</b>
            </a>
            <a href="{{ route('reportesAvance.index', ['estado' => 'PENDIENTE']) }}" class="dashboard-attention">
                <span class="dashboard-attention__marker">RA</span>
                <span><strong>Reportes de avance</strong><small>Reportes sin revisar</small></span>
                <b>{{ $alerts['reportes'] }}</b>
            </a>
            <a href="{{ route('reportesAvance.index', ['pestana' => 'pendientes']) }}" class="dashboard-attention">
                <span class="dashboard-attention__marker dashboard-attention__marker--graphite">PE</span>
                <span><strong>Actividades pendientes</strong><small>Abiertas o por validar</small></span>
                <b>{{ $alerts['pendientes'] }}</b>
            </a>
        </div>
    </aside>
</div>

<section class="dashboard-panel dashboard-panel--table">
    <div class="dashboard-panel__header">
        <div>
            <span class="dashboard-panel__eyebrow">ACTUALIZACIÓN RECIENTE</span>
            <h3>Últimas asistencias procesadas</h3>
        </div>
        <a class="button button--secondary button--small" href="{{ route('trabajadores.index') }}">Gestionar trabajadores</a>
    </div>

    @include('dashboard.partials.asistencias', ['asistencias' => $asistenciasRecientes, 'mostrarArea' => true])
</section>

@if($reporteControl)
    @include('dashboard.partials.reporte-control', ['reporte' => $reporteControl])
@endif
