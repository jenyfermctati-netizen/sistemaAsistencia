<section class="dashboard-metrics" aria-label="Resumen de supervisión">
    <article class="dashboard-metric dashboard-metric--petrol">
        <span class="dashboard-metric__label">Equipo supervisado</span>
        <strong class="dashboard-metric__value">{{ $metrics['trabajadores'] }}</strong>
        <span class="dashboard-metric__caption">trabajadores activos</span>
    </article>

    <article class="dashboard-metric dashboard-metric--blue">
        <span class="dashboard-metric__label">Asistencias de hoy</span>
        <strong class="dashboard-metric__value">{{ $metrics['asistencias'] }}</strong>
        <span class="dashboard-metric__caption">{{ $metrics['puntuales'] }} registros puntuales</span>
    </article>

    <article class="dashboard-metric dashboard-metric--sand">
        <span class="dashboard-metric__label">Incidencias</span>
        <strong class="dashboard-metric__value">{{ $metrics['incidencias'] }}</strong>
        <span class="dashboard-metric__caption">tardanzas, faltas o sin registro</span>
    </article>

    <article class="dashboard-metric dashboard-metric--graphite">
        <span class="dashboard-metric__label">Pendientes del área</span>
        <strong class="dashboard-metric__value">{{ $alerts['pendientes'] }}</strong>
        <span class="dashboard-metric__caption">actividades abiertas o por validar</span>
    </article>
</section>

<div class="dashboard-columns">
    <section class="dashboard-panel dashboard-panel--wide">
        <div class="dashboard-panel__header">
            <div>
                <span class="dashboard-panel__eyebrow">CONTROL DEL EQUIPO</span>
                <h3>Asistencia por área</h3>
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
                    <div class="dashboard-progress"><span style="width: {{ $area->cobertura }}%"></span></div>
                    <div class="dashboard-area__details">
                        <span>{{ $area->asistencias_hoy }} procesadas</span>
                        <span>{{ $area->incidencias_hoy }} incidencias</span>
                        <span>{{ $area->sin_procesar }} por procesar</span>
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
                <span><strong>Mis solicitudes</strong><small>Solicitudes todavía pendientes</small></span>
                <b>{{ $alerts['solicitudes'] }}</b>
            </a>
            <a href="{{ route('reportesAvance.index', ['estado' => 'PENDIENTE', 'area_id' => $areaVinculada?->id]) }}" class="dashboard-attention">
                <span class="dashboard-attention__marker">RA</span>
                <span><strong>Reportes del equipo</strong><small>Avances pendientes de revisión</small></span>
                <b>{{ $alerts['reportes'] }}</b>
            </a>
            <a href="{{ route('reportesAvance.index', ['pestana' => 'pendientes', 'area_id' => $areaVinculada?->id]) }}" class="dashboard-attention">
                <span class="dashboard-attention__marker dashboard-attention__marker--sand">PE</span>
                <span><strong>Actividades del área</strong><small>Trabajo aún por completar</small></span>
                <b>{{ $alerts['pendientes'] }}</b>
            </a>
        </div>

        @if(!$areaVinculada)
            <p class="dashboard-note">Esta cuenta no tiene un trabajador vinculado; por eso se muestra la organización completa.</p>
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
