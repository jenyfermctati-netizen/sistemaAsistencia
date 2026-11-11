@php
    $total = $metricas['empresas'];
    $activas = $total ? $metricas['activas'] / $total * 100 : 0;
    $suspendidas = $total ? $metricas['suspendidas'] / $total * 100 : 0;
    $maximo = max(1, $registrosMensuales->max('total'));
@endphp
<section class="sa-charts" aria-label="Gráficos de la plataforma">
    <article class="sa-panel">
        <h3>Estado de las empresas</h3><p class="sa-muted">Distribución actual de la plataforma</p>
        @if($total)
            <div class="sa-donut-layout">
                <div class="sa-donut" aria-hidden="true" style="--activas: {{ $activas }}%; --suspendidas: {{ $activas + $suspendidas }}%"><div><strong>{{ $total }}</strong><span>empresas</span></div></div>
                <ul class="sa-chart-legend">
                @foreach(['activas' => ['Activas', 'ACTIVA'], 'suspendidas' => ['Suspendidas', 'SUSPENDIDA'], 'inactivas' => ['Inactivas', 'INACTIVA']] as $clave => [$etiqueta, $estado])
                    <li><a href="{{ route('superadmin.empresas.index', ['estado' => $estado]) }}"><span class="sa-chart-dot sa-chart-dot--{{ $clave }}"></span><span>{{ $etiqueta }}</span><strong>{{ $metricas[$clave] }}</strong><small>{{ round($metricas[$clave] / $total * 100) }}%</small></a></li>
                @endforeach
                </ul>
            </div><p class="sa-chart-note">Selecciona un estado para ver sus empresas.</p>
        @else
            <div class="sa-chart-empty"><x-ui-icon name="report" /><strong>Aún no hay empresas</strong><p>El gráfico aparecerá al registrar tu primera empresa.</p><a href="{{ route('superadmin.empresas.create') }}">Crear empresa</a></div>
        @endif
    </article>
    <article class="sa-panel">
        <h3>Empresas registradas por mes</h3><p class="sa-muted">Últimos 6 meses · {{ $registrosMensuales->sum('total') }} registros</p>
        <div class="sa-monthly" role="list" aria-label="Empresas registradas por mes">
        @foreach($registrosMensuales as $mes)
            <div class="sa-monthly-column" role="listitem" aria-label="{{ $mes['etiqueta'] }}: {{ $mes['total'] }} empresas">
                <div class="sa-monthly-track" aria-hidden="true"><div class="sa-monthly-bar" style="height: {{ $mes['total'] / $maximo * 100 }}%"></div><strong style="bottom: {{ $mes['total'] / $maximo * 100 }}%">{{ $mes['total'] }}</strong></div>
                <span aria-hidden="true">{{ $mes['etiqueta'] }}</span>
            </div>
        @endforeach
        </div><p class="sa-chart-note">{{ $registrosMensuales->sum('total') ? 'Cada barra muestra las empresas creadas ese mes, sin importar su estado actual.' : 'No se registraron empresas durante este período.' }}</p>
    </article>
</section>
