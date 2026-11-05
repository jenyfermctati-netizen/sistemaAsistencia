@php
    $rutaAvances = array_merge($parametrosVista, ['pestana' => 'avances']);
    $filtrosDescarga = request()->only(['buscar', 'estado', 'area_id', 'fecha_desde', 'fecha_hasta', 'historial']);
    $mesSolicitado = request()->query('mes_calendario');
    $mesValido = is_string($mesSolicitado) && preg_match('/^[1-9][0-9]{3}-(0[1-9]|1[0-2])$/D', $mesSolicitado);
    $fechaSeleccionada = request()->query('fecha_desde');
    $diasPorFecha = $diasConReportes->keyBy(fn ($dia) => $dia->fecha_presentacion->toDateString());
    $mes = $mesValido
        ? \Illuminate\Support\Carbon::parse($mesSolicitado.'-01')
        : ($diasPorFecha->get(is_string($fechaSeleccionada) ? $fechaSeleccionada : '')?->fecha_presentacion
            ?? $diasConReportes->first()?->fecha_presentacion
            ?? today())->copy()->startOfMonth();
    $fechaFinal = request()->query('fecha_hasta');
    $inicioRango = request()->query('inicio_calendario');
    $inicioValido = is_string($inicioRango)
        && preg_match('/^[1-9][0-9]{3}-[0-9]{2}-[0-9]{2}$/D', $inicioRango)
        && checkdate((int) substr($inicioRango, 5, 2), (int) substr($inicioRango, 8, 2), (int) substr($inicioRango, 0, 4));
    $filtrosCalendario = $rutaAvances + $filtrosDescarga + ($inicioValido ? ['inicio_calendario' => $inicioRango] : []);
    $filtrosRango = $rutaAvances + request()->only(['buscar', 'estado', 'area_id', 'historial']);
@endphp

<div class="ra-query-actions">
    <details class="ra-report-days" id="calendario-reportes" @if($mesValido || $inicioValido) open @endif>
        <summary>Período y calendario</summary>

        <div class="ra-calendar-periods">
        @php
            $hoy = today();
            $periodos = [
                'Hoy' => [$hoy, $hoy],
                'Esta semana' => [$hoy->copy()->startOfWeek(), $hoy->copy()->endOfWeek()],
                'Este mes' => [$hoy->copy()->startOfMonth(), $hoy->copy()->endOfMonth()],
                'Todo el historial' => null,
            ];
            $filtrosPeriodo = $rutaAvances + request()->only(['buscar', 'estado', 'area_id']);
        @endphp
        @foreach($periodos as $etiqueta => $rango)
            @php
                $fechas = $rango ? ['fecha_desde' => $rango[0]->toDateString(), 'fecha_hasta' => $rango[1]->toDateString()] : ['historial' => 1];
                $periodoActivo = $rango
                    ? request('fecha_desde') === $fechas['fecha_desde'] && request('fecha_hasta') === $fechas['fecha_hasta']
                    : request()->boolean('historial') && !request()->filled('fecha_desde') && !request()->filled('fecha_hasta');
            @endphp
            <a class="ra-btn {{ $periodoActivo ? 'ra-btn--primary' : 'ra-btn--outline' }}"
                href="{{ route('reportesAvance.index', $filtrosPeriodo + $fechas) }}"
                @if($periodoActivo) aria-current="true" @endif>{{ $etiqueta }}</a>
        @endforeach
        </div>

        @if($diasConReportes->isEmpty())
            <p>Aún no hay reportes registrados.</p>
        @else
            <div class="ra-calendar">
                <p class="ra-calendar__range">
                    @if($inicioValido)
                        Inicio seleccionado: {{ \Illuminate\Support\Carbon::parse($inicioRango)->format('d/m/Y') }}. Elige el otro extremo del rango.
                        <a href="{{ route('reportesAvance.index', $rutaAvances + $filtrosDescarga + ['mes_calendario' => $mes->format('Y-m')]) }}#calendario-reportes">Cancelar selección</a>
                    @elseif($fechaSeleccionada && $fechaFinal && is_string($fechaSeleccionada) && is_string($fechaFinal))
                        Rango aplicado: {{ $fechaSeleccionada }} a {{ $fechaFinal }}. Elige un día para cambiarlo.
                        <a href="{{ route('reportesAvance.index', $filtrosRango + ['mes_calendario' => $mes->format('Y-m')]) }}#calendario-reportes">Quitar rango</a>
                    @else
                        Elige el primer día y luego el último. Para consultar un solo día, selecciónalo dos veces.
                    @endif
                </p>
                <nav class="ra-calendar__header" aria-label="Mes del calendario">
                    <a href="{{ route('reportesAvance.index', $filtrosCalendario + ['mes_calendario' => $mes->copy()->subMonth()->format('Y-m')]) }}#calendario-reportes"
                        aria-label="Mes anterior">‹</a>
                    <strong>{{ $mes->locale('es')->translatedFormat('F Y') }}</strong>
                    <a href="{{ route('reportesAvance.index', $filtrosCalendario + ['mes_calendario' => $mes->copy()->addMonth()->format('Y-m')]) }}#calendario-reportes"
                        aria-label="Mes siguiente">›</a>
                </nav>

                <div class="ra-calendar__grid">
                    @foreach(['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $diaSemana)
                        <span class="ra-calendar__weekday">{{ $diaSemana }}</span>
                    @endforeach

                    @for($espacio = 1; $espacio < $mes->dayOfWeekIso; $espacio++)
                        <span aria-hidden="true"></span>
                    @endfor

                    @for($numero = 1; $numero <= $mes->daysInMonth; $numero++)
                        @php
                            $fecha = $mes->copy()->day($numero)->toDateString();
                            $dia = $diasPorFecha->get($fecha);
                            $enRango = $inicioValido
                                ? $fecha === $inicioRango
                                : (is_string($fechaSeleccionada) && is_string($fechaFinal) && $fecha >= $fechaSeleccionada && $fecha <= $fechaFinal);
                            $parametrosDia = $inicioValido
                                ? $filtrosRango + ['fecha_desde' => min($inicioRango, $fecha), 'fecha_hasta' => max($inicioRango, $fecha)]
                                : $rutaAvances + $filtrosDescarga + ['inicio_calendario' => $fecha];
                        @endphp
                        <a class="ra-calendar__day {{ $dia ? 'ra-calendar__day--record' : '' }} {{ $dia?->por_revisar ? 'ra-calendar__day--review' : '' }} {{ $enRango ? 'ra-calendar__day--selected' : '' }}"
                            href="{{ route('reportesAvance.index', $parametrosDia + ['mes_calendario' => $mes->format('Y-m')]) }}#calendario-reportes"
                            aria-label="{{ $mes->copy()->day($numero)->format('d/m/Y') }}, {{ $dia?->total ?? 0 }} registros, {{ $dia?->por_revisar ?? 0 }} por revisar{{ $enRango ? ', seleccionado' : '' }}">
                            {{ $numero }}
                        </a>
                    @endfor
                </div>

                <p class="ra-calendar__legend">El punto rojo indica que hay reportes por revisar.</p>
            </div>
        @endif
    </details>

    <x-download-menu
        :excel="route('reportesAvance.index', $rutaAvances + $filtrosDescarga + ['descargar' => 'xlsx'])"
        :pdf="route('reportesAvance.index', $rutaAvances + $filtrosDescarga + ['descargar' => 'pdf'])" />
</div>
