<section class="dashboard-metrics" aria-label="Resumen de supervisión">
    <a class="dashboard-metric dashboard-metric--petrol" href="{{ route('asistencias.index', ['fecha_desde' => today()->toDateString(), 'fecha_hasta' => today()->toDateString()]) }}" aria-label="Ver asistencia del equipo supervisado">
        <span class="dashboard-metric__label">Equipo supervisado</span>
        <strong class="dashboard-metric__value">{{ $metrics['trabajadores'] }}</strong>
        <span class="dashboard-metric__caption">trabajadores activos</span>
    </a>

    <a class="dashboard-metric dashboard-metric--blue" href="{{ route('asistencias.index', ['fecha_desde' => today()->toDateString(), 'fecha_hasta' => today()->toDateString()]) }}" aria-label="Ver asistencias de hoy">
        <span class="dashboard-metric__label">Asistencias de hoy</span>
        <strong class="dashboard-metric__value">{{ $metrics['asistencias'] }}</strong>
        <span class="dashboard-metric__caption">{{ $metrics['puntuales'] }} registros puntuales</span>
    </a>

    <a class="dashboard-metric dashboard-metric--sand" href="{{ route('asistencias.index', ['fecha_desde' => today()->toDateString(), 'fecha_hasta' => today()->toDateString(), 'estado' => 'INCIDENCIAS']) }}" aria-label="Ver incidencias de asistencia">
        <span class="dashboard-metric__label">Incidencias</span>
        <strong class="dashboard-metric__value">{{ $metrics['incidencias'] }}</strong>
        <span class="dashboard-metric__caption">tardanzas, faltas o sin registro</span>
    </a>

    <a class="dashboard-metric dashboard-metric--graphite" href="{{ route('reportesAvance.index', ['pestana' => 'pendientes']) }}" aria-label="Ver pendientes del área">
        <span class="dashboard-metric__label">Pendientes del área</span>
        <strong class="dashboard-metric__value">{{ $alerts['pendientes'] }}</strong>
        <span class="dashboard-metric__caption">actividades abiertas o por validar</span>
    </a>
</section>

<div class="dashboard-columns">
    <section class="dashboard-panel dashboard-panel--wide">
        <div class="dashboard-panel__header">
            <div>
                <span class="dashboard-panel__eyebrow">CONTROL DEL EQUIPO</span>
                <h3>Cobertura de asistencia de hoy</h3>
            </div>
            <a class="dashboard-text-link" href="{{ route('asistencias.index') }}">Consultar horas <span>→</span></a>
        </div>

        <div class="dashboard-area-list">
            @forelse($areasResumen as $area)
                <article class="dashboard-area">
                    <div class="dashboard-area__main">
                        <div>
                            <strong>{{ $area->nombre }}</strong>
                            <span>{{ $area->trabajadores_activos }} integrantes activos</span>
                        </div>
                        <span class="dashboard-area__percentage">{{ $area->cobertura }}%</span>
                    </div>
                    <div class="dashboard-progress" aria-label="{{ $area->cobertura }} por ciento de cobertura de asistencia"><span style="width: {{ $area->cobertura }}%"></span></div>
                    <div class="dashboard-area__details">
                        <span>{{ $area->asistencias_hoy }} asistieron</span>
                        <span>{{ $area->tardanzas_hoy }} tardanzas</span>
                        <span>{{ $area->sin_asistencia_hoy }} sin asistencia</span>
                        <span>{{ $area->justificadas_hoy }} justificados</span>
                        <span>{{ $area->sin_procesar }} pendientes de registrar</span>
                    </div>
                </article>
            @empty
                <div class="dashboard-empty">No hay un área activa disponible para esta cuenta.</div>
            @endforelse
        </div>
    </section>

    <aside class="dashboard-panel">
        <div class="dashboard-panel__header">
            <div>
                <span class="dashboard-panel__eyebrow">SEGUIMIENTO</span>
                <h3>Trabajo por revisar</h3>
            </div>
        </div>

        <div class="dashboard-attention-list">
            <a href="{{ route('solicitudes.index', ['estado' => 'PENDIENTE']) }}" class="dashboard-attention">
                <span class="dashboard-attention__marker dashboard-attention__marker--blue">SO</span>
                <span><strong>Solicitudes del equipo</strong><small>Permisos y justificaciones por revisar</small></span>
                <b>{{ $alerts['solicitudes'] }}</b>
            </a>
            <a href="{{ route('reportesAvance.index', ['estado' => 'PENDIENTE']) }}" class="dashboard-attention">
                <span class="dashboard-attention__marker">RA</span>
                <span><strong>Reportes del equipo</strong><small>Avances pendientes de revisión</small></span>
                <b>{{ $alerts['reportes'] }}</b>
            </a>
            <a href="{{ route('reportesAvance.index', ['pestana' => 'pendientes']) }}" class="dashboard-attention">
                <span class="dashboard-attention__marker dashboard-attention__marker--sand">PE</span>
                <span><strong>Actividades del área</strong><small>Trabajo aún por completar</small></span>
                <b>{{ $alerts['pendientes'] }}</b>
            </a>
        </div>

        @if(!$areaVinculada)
            <p class="dashboard-note">Esta cuenta no tiene un área vinculada. Un administrador debe asociarla a un trabajador con área para habilitar la supervisión.</p>
        @endif
    </aside>
</div>

<section class="dashboard-panel dashboard-panel--table">
    <div class="dashboard-panel__header">
        <div>
            <span class="dashboard-panel__eyebrow">JORNADA DE HOY</span>
            <h3>Actividad reciente del equipo</h3>
        </div>
        <a class="button button--secondary button--small" href="{{ route('reportesAvance.index') }}">Ver reportes</a>
    </div>

    @include('dashboard.partials.asistencias', ['asistencias' => $asistenciasRecientes, 'mostrarArea' => !$areaVinculada])
</section>

@if($reporteControl)
    @include('dashboard.partials.reporte-control', ['reporte' => $reporteControl])
@endif
