<section class="dashboard-metrics" aria-label="Resumen de supervisión">
    <a class="dashboard-metric dashboard-metric--petrol" href="{{ route('asistencias.index', ['fecha_desde' => today()->toDateString(), 'fecha_hasta' => today()->toDateString()]) }}" aria-label="Ver asistencia del equipo supervisado">
        <span class="dashboard-metric__icon"><x-ui-icon name="users" /></span>
        <span class="dashboard-metric__label">Equipo supervisado</span>
        <strong class="dashboard-metric__value">{{ $metrics['trabajadores'] }}</strong>
        <span class="dashboard-metric__caption">trabajadores activos</span>
    </a>

    <a class="dashboard-metric dashboard-metric--blue" href="{{ route('asistencias.index', ['fecha_desde' => today()->toDateString(), 'fecha_hasta' => today()->toDateString()]) }}" aria-label="Ver asistencias de hoy">
        <span class="dashboard-metric__icon"><x-ui-icon name="attendance" /></span>
        <span class="dashboard-metric__label">Asistencias de hoy</span>
        <strong class="dashboard-metric__value">{{ $metrics['asistencias'] }}</strong>
        <span class="dashboard-metric__caption">{{ $metrics['puntuales'] }} registros puntuales</span>
    </a>

    <a class="dashboard-metric dashboard-metric--sand" href="{{ route('asistencias.index', ['fecha_desde' => today()->toDateString(), 'fecha_hasta' => today()->toDateString(), 'estado' => 'INCIDENCIAS']) }}" aria-label="Ver incidencias de asistencia">
        <span class="dashboard-metric__icon"><x-ui-icon name="alert" /></span>
        <span class="dashboard-metric__label">Incidencias</span>
        <strong class="dashboard-metric__value">{{ $metrics['incidencias'] }}</strong>
        <span class="dashboard-metric__caption">tardanzas, faltas o sin registro</span>
    </a>

    <a class="dashboard-metric dashboard-metric--graphite" href="{{ route('reportesAvance.index', ['pestana' => 'pendientes']) }}" aria-label="Ver pendientes del área">
        <span class="dashboard-metric__icon"><x-ui-icon name="report" /></span>
        <span class="dashboard-metric__label">Pendientes del área</span>
        <strong class="dashboard-metric__value">{{ $alerts['pendientes'] }}</strong>
        <span class="dashboard-metric__caption">actividades abiertas o por validar</span>
    </a>
</section>

@if(!$areaVinculada)
    <section class="dashboard-panel dashboard-unlinked" role="status">
        <span class="dashboard-unlinked__icon"><x-ui-icon name="alert" /></span>
        <div>
            <h3>Falta vincular un área de supervisión</h3>
            <p>El panel mostrará el equipo, la cobertura y las incidencias cuando el administrador relacione tu trabajador con un área activa.</p>
        </div>
    </section>
@else
    <section class="dashboard-manager-actions" aria-label="Pendientes que requieren atención">
        <a href="{{ route('solicitudes.index', ['estado' => 'PENDIENTE']) }}" class="dashboard-action-card">
            <span class="dashboard-action-card__icon"><x-ui-icon name="calendar" /></span>
            <span class="dashboard-action-card__content">
                <small>Solicitudes por revisar</small>
                <strong>{{ $alerts['solicitudes'] }} {{ $alerts['solicitudes'] === 1 ? 'pendiente' : 'pendientes' }}</strong>
            </span>
            <x-ui-icon name="chevron-right" class="dashboard-action-card__arrow" />
        </a>
        <a href="{{ route('reportesAvance.index', ['vista' => 'equipo', 'pestana' => 'avances']) }}" class="dashboard-action-card">
            <span class="dashboard-action-card__icon dashboard-action-card__icon--blue"><x-ui-icon name="report" /></span>
            <span class="dashboard-action-card__content">
                <small>Avances por revisar</small>
                <strong>{{ $alerts['reportes'] }} {{ $alerts['reportes'] === 1 ? 'reporte' : 'reportes' }}</strong>
            </span>
            <x-ui-icon name="chevron-right" class="dashboard-action-card__arrow" />
        </a>
        <a href="{{ route('reportesAvance.index', ['pestana' => 'pendientes']) }}" class="dashboard-action-card">
            <span class="dashboard-action-card__icon dashboard-action-card__icon--warning"><x-ui-icon name="alert" /></span>
            <span class="dashboard-action-card__content">
                <small>Actividades abiertas</small>
                <strong>{{ $alerts['pendientes'] }} {{ $alerts['pendientes'] === 1 ? 'actividad' : 'actividades' }}</strong>
            </span>
            <x-ui-icon name="chevron-right" class="dashboard-action-card__arrow" />
        </a>
    </section>
@endif

<div id="dashboardChartData" hidden aria-hidden="true">
    @foreach($areasResumen as $area)
        <span
            data-name="{{ $area->nombre }}"
            data-coverage="{{ $area->cobertura }}"
            data-present="{{ $area->puntuales_hoy }}"
            data-late="{{ $area->tardanzas_hoy }}"
            data-absent="{{ $area->sin_asistencia_hoy }}"
            data-justified="{{ $area->justificadas_hoy }}"
            data-processed="{{ $area->procesadas_hoy }}"
            data-pending="{{ $area->sin_procesar }}"
            data-workers="{{ $area->trabajadores_activos }}"
        ></span>
    @endforeach
</div>

<section class="dashboard-analytics" aria-label="Gráficos de asistencia">
    <article class="dashboard-panel dashboard-panel--chart">
        <div class="dashboard-panel__header">
            <div>
                <span class="dashboard-panel__eyebrow">PANORAMA DE HOY</span>
                <h3>Estado del equipo</h3>
                <p class="dashboard-panel__description">Distribución de los registros ya procesados.</p>
            </div>
        </div>
        <div class="dashboard-chart-container">
            <div id="dashboardDonut" class="dashboard-chart-host" aria-live="polite"></div>
        </div>
        <div id="dashboardDonutLegend" class="dashboard-chart-legend"></div>
    </article>
    <article class="dashboard-panel dashboard-panel--chart dashboard-panel--chart-wide">
        <div class="dashboard-panel__header">
            <div>
                <span class="dashboard-panel__eyebrow">COBERTURA OPERATIVA</span>
                <h3>Procesamiento del área</h3>
                <p class="dashboard-panel__description">Compara la asistencia efectiva con el equipo esperado y muestra los registros pendientes.</p>
            </div>
            <a class="dashboard-text-link" href="{{ route('asistencias.index', ['fecha_desde' => today()->toDateString(), 'fecha_hasta' => today()->toDateString()]) }}">
                Ver detalle <span aria-hidden="true">→</span>
            </a>
        </div>
        <div class="dashboard-chart-container dashboard-chart-container--wide dashboard-chart-container--coverage">
            <div id="dashboardAreasChart" class="dashboard-chart-host" aria-live="polite"></div>
        </div>
    </article>
</section>

<section class="dashboard-panel dashboard-panel--table">
    <div class="dashboard-panel__header">
        <div>
            <span class="dashboard-panel__eyebrow">JORNADA DE HOY</span>
            <h3>Últimas asistencias procesadas</h3>
            <p class="dashboard-panel__description">Consulta el detalle sin salir de este panel.</p>
        </div>
        <a class="dashboard-text-link" href="{{ route('asistencias.index', ['fecha_desde' => today()->toDateString(), 'fecha_hasta' => today()->toDateString()]) }}">
            Ver todas <span aria-hidden="true">→</span>
        </a>
    </div>

    @include('dashboard.partials.asistencias', ['asistencias' => $asistenciasRecientes, 'mostrarArea' => !$areaVinculada])
</section>
