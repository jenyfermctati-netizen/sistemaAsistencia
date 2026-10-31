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


<div id="dashboardChartData" hidden aria-hidden="true">
    @foreach($areasResumen as $area)
        <span data-name="{{ $area->nombre }}" data-coverage="{{ $area->cobertura }}" data-present="{{ $area->asistencias_hoy }}" data-late="{{ $area->tardanzas_hoy }}" data-absent="{{ $area->sin_asistencia_hoy }}" data-justified="{{ $area->justificadas_hoy }}"></span>
    @endforeach
</div>

<section class="dashboard-analytics" aria-label="Gráficos de asistencia">
    <article class="dashboard-panel dashboard-panel--chart">
        <div class="dashboard-panel__header"><div><span class="dashboard-panel__eyebrow">PANORAMA ACTUAL</span><h3>Estado del equipo supervisado</h3></div></div>
        <div class="dashboard-chart-container"><canvas id="dashboardDonut" aria-label="Gráfico de distribución de asistencia" role="img"></canvas></div>
        <div id="dashboardDonutLegend" class="dashboard-chart-legend"></div>
    </article>
    <article class="dashboard-panel dashboard-panel--chart dashboard-panel--chart-wide">
        <div class="dashboard-panel__header"><div><span class="dashboard-panel__eyebrow">COMPARATIVA</span><h3>Cobertura del área</h3></div></div>
        <div class="dashboard-chart-container dashboard-chart-container--wide"><canvas id="dashboardAreasChart" aria-label="Gráfico de cobertura por área" role="img"></canvas></div>
    </article>
</section>

<section class="dashboard-panel dashboard-panel--table">
    <div class="dashboard-panel__header">
        <div>
            <span class="dashboard-panel__eyebrow">JORNADA DE HOY</span>
            <h3>Marcaciones recientes</h3>
        </div>
    </div>

    @include('dashboard.partials.asistencias', ['asistencias' => $asistenciasRecientes, 'mostrarArea' => !$areaVinculada])
</section>