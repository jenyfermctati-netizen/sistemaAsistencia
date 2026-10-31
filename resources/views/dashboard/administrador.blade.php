<section class="dashboard-metrics" aria-label="Resumen del día">
    <a class="dashboard-metric dashboard-metric--petrol" href="{{ route('trabajadores.index') }}" aria-label="Ver trabajadores activos">
        <span class="dashboard-metric__icon"><x-ui-icon name="users" /></span>
        <span class="dashboard-metric__label">Equipo activo</span>
        <strong class="dashboard-metric__value">{{ $metrics['trabajadores'] }}</strong>
        <span class="dashboard-metric__caption">trabajadores en {{ $metrics['areas'] }} áreas</span>
    </a>

    <a class="dashboard-metric dashboard-metric--blue" href="{{ route('asistencias.index', ['fecha_desde' => today()->toDateString(), 'fecha_hasta' => today()->toDateString()]) }}" aria-label="Ver asistencias de hoy">
        <span class="dashboard-metric__icon"><x-ui-icon name="attendance" /></span>
        <span class="dashboard-metric__label">Asistencias de hoy</span>
        <strong class="dashboard-metric__value">{{ $metrics['asistencias'] }}</strong>
        <span class="dashboard-metric__caption">{{ $metrics['puntuales'] }} registros puntuales</span>
    </a>

    <a class="dashboard-metric dashboard-metric--sand" href="{{ route('asistencias.index', ['fecha_desde' => today()->toDateString(), 'fecha_hasta' => today()->toDateString(), 'estado' => 'TARDANZA']) }}" aria-label="Ver tardanzas de hoy">
        <span class="dashboard-metric__icon"><x-ui-icon name="alert" /></span>
        <span class="dashboard-metric__label">Tardanzas de hoy</span>
        <strong class="dashboard-metric__value">{{ $metrics['tardanzas'] }}</strong>
        <span class="dashboard-metric__caption">requieren seguimiento</span>
    </a>

    <a class="dashboard-metric dashboard-metric--graphite" href="{{ route('marcaciones.index', ['fecha_desde' => today()->toDateString(), 'fecha_hasta' => today()->toDateString(), 'estado' => 'ACTIVA']) }}" aria-label="Ver marcaciones válidas de hoy">
        <span class="dashboard-metric__icon"><x-ui-icon name="clock" /></span>
        <span class="dashboard-metric__label">Marcaciones</span>
        <strong class="dashboard-metric__value">{{ $metrics['marcaciones'] }}</strong>
        <span class="dashboard-metric__caption">entradas y salidas válidas</span>
    </a>
</section>


<div id="dashboardChartData" hidden aria-hidden="true">
    @foreach($areasResumen as $area)
        <span data-name="{{ $area->nombre }}" data-coverage="{{ $area->cobertura }}" data-present="{{ $area->asistencias_hoy }}" data-late="{{ $area->tardanzas_hoy }}" data-absent="{{ $area->sin_asistencia_hoy }}" data-justified="{{ $area->justificadas_hoy }}"></span>
    @endforeach
</div>

<section class="dashboard-analytics" aria-label="Gráficos de asistencia">
    <article class="dashboard-panel dashboard-panel--chart">
        <div class="dashboard-panel__header"><div><span class="dashboard-panel__eyebrow">PANORAMA ACTUAL</span><h3>Distribución de asistencia de hoy</h3></div></div>
        <div class="dashboard-chart-container"><canvas id="dashboardDonut" aria-label="Gráfico de distribución de asistencia" role="img"></canvas></div>
        <div id="dashboardDonutLegend" class="dashboard-chart-legend"></div>
    </article>
    <article class="dashboard-panel dashboard-panel--chart dashboard-panel--chart-wide">
        <div class="dashboard-panel__header"><div><span class="dashboard-panel__eyebrow">COMPARATIVA</span><h3>Asistencia por área</h3></div></div>
        <div class="dashboard-chart-container dashboard-chart-container--wide"><canvas id="dashboardAreasChart" aria-label="Gráfico de cobertura por área" role="img"></canvas></div>
    </article>
</section>

<section class="dashboard-panel dashboard-panel--table">
    <div class="dashboard-panel__header">
        <div>
            <span class="dashboard-panel__eyebrow">ACTUALIZACIÓN RECIENTE</span>
            <h3>Marcaciones recientes</h3>
        </div>
    </div>

    @include('dashboard.partials.asistencias', ['asistencias' => $asistenciasRecientes, 'mostrarArea' => true])
</section>

