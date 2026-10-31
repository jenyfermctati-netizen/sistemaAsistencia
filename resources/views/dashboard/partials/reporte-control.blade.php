@php
    $filtrosExportacion = array_filter([
        'desde' => $reporte['desde']->toDateString(),
        'hasta' => $reporte['hasta']->toDateString(),
        'area_id' => $reporte['areaSeleccionada'],
    ], fn ($valor) => $valor !== null && $valor !== '');
    $horasReporte = fn (int $minutos) => intdiv($minutos, 60).' h '.($minutos % 60).' min';
    $estadoReporte = fn (?string $valor) => ucfirst(strtolower(str_replace('_', ' ', $valor ?? 'Sin revisión')));
@endphp

<section id="reporte-control" class="dashboard-panel dashboard-report">
    <div class="dashboard-panel__header dashboard-report__header">
        <div>
            <span class="dashboard-panel__eyebrow">
                {{ $reporte['esVistaGeneral'] ? 'CONTROL SEMANAL' : 'RESUMEN DEL ÁREA' }}
            </span>
            <h3>Asistencia, avances y tareas</h3>
            <p>
                Del {{ $reporte['desde']->format('d/m/Y') }} al {{ $reporte['hasta']->format('d/m/Y') }}
                · {{ $reporte['alcanceLabel'] }}
            </p>
        </div>

        <div class="dashboard-report__actions">
            <a class="button button--secondary button--small"
                href="{{ route('dashboard.reporteControl.pdf', $filtrosExportacion) }}">
                <x-ui-icon name="download" /> Descargar PDF
            </a>
            <a class="button button--primary button--small"
                href="{{ route('dashboard.reporteControl.excel', $filtrosExportacion) }}">
                <x-ui-icon name="download" /> Descargar Excel
            </a>
        </div>
    </div>

    <form class="dashboard-report__filters" method="GET" action="{{ route('dashboard') }}#reporte-control">
        <label>
            <span>Desde</span>
            <input class="form-control" type="date" name="reporte_desde"
                value="{{ $reporte['desde']->toDateString() }}" required>
        </label>
        <label>
            <span>Hasta</span>
            <input class="form-control" type="date" name="reporte_hasta"
                value="{{ $reporte['hasta']->toDateString() }}" required>
        </label>
        @if($reporte['esVistaGeneral'])
            <label>
                <span>Área</span>
                <select class="form-select" name="reporte_area_id">
                    <option value="">Todas las áreas</option>
                    @foreach($reporte['areasDisponibles'] as $areaReporte)
                        <option value="{{ $areaReporte->id }}" @selected($reporte['areaSeleccionada'] === $areaReporte->id)>
                            {{ $areaReporte->nombre }}
                        </option>
                    @endforeach
                </select>
            </label>
        @endif
        <button class="button button--primary button--small" type="submit">Actualizar</button>
        <small>Periodo máximo: 31 días.</small>
    </form>

    @if($errors->has('reporte_desde') || $errors->has('reporte_hasta') || $errors->has('reporte_area_id'))
        <div class="dashboard-report__alert">
            {{ $errors->first('reporte_desde') ?: ($errors->first('reporte_hasta') ?: $errors->first('reporte_area_id')) }}
        </div>
    @endif

    <div class="dashboard-report__metrics" aria-label="Indicadores del periodo">
        <article><span>Trabajadores</span><strong>{{ $reporte['metricas']['trabajadores'] }}</strong></article>
        <article><span>Días registrados</span><strong>{{ $reporte['metricas']['dias_registrados'] }}</strong></article>
        <article><span>Horas trabajadas</span><strong>{{ $horasReporte($reporte['metricas']['minutos_trabajados']) }}</strong></article>
        <article><span>Tardanzas</span><strong>{{ $reporte['metricas']['tardanzas'] }}</strong></article>
        <article><span>Faltas</span><strong>{{ $reporte['metricas']['faltas'] }}</strong></article>
        <article><span>Actividades</span><strong>{{ $reporte['metricas']['actividades'] }}</strong></article>
        <article><span>Pendientes</span><strong>{{ $reporte['metricas']['pendientes_abiertos'] }}</strong></article>
        <article><span>Finalizadas</span><strong>{{ $reporte['metricas']['finalizadas'] }}</strong></article>
    </div>

    <div class="dashboard-report__table-wrap">
        <table class="dashboard-report__table">
            <thead>
                <tr>
                    @if($reporte['esVistaGeneral'])<th>Área</th>@endif
                    <th>Trabajador</th>
                    <th>Asistencia</th>
                    <th>Horas trabajadas</th>
                    <th>Actividades finalizadas</th>
                    <th>Pendientes del área</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reporte['areasReporte'] as $areaData)
                    @foreach($areaData['trabajadores'] as $trabajadorData)
                        @php
                            $m = $trabajadorData['metricas'];
                            $tieneFalta = $m['faltas'] > 0;
                            $tieneAsistencia = $m['dias_registrados'] > 0;
                        @endphp
                        <tr>
                            @if($reporte['esVistaGeneral'])<td data-label="Área">{{ $areaData['area']->nombre }}</td>@endif
                            <td data-label="Trabajador">
                                <strong>{{ $trabajadorData['trabajador']->nombres }} {{ $trabajadorData['trabajador']->apellidos }}</strong>
                                <small>DNI {{ $trabajadorData['trabajador']->dni }}</small>
                            </td>
                            <td data-label="Asistencia">
                                @if($tieneFalta)
                                    <span class="dashboard-report__attendance dashboard-report__attendance--absent">✕ Falta</span>
                                @elseif($tieneAsistencia)
                                    <span class="dashboard-report__attendance dashboard-report__attendance--present">✓ Asistencia</span>
                                @else
                                    <span class="dashboard-report__attendance dashboard-report__attendance--empty">— Sin registro</span>
                                @endif
                                <small>
                                    {{ $m['dias_registrados'] }} días registrados
                                    @if($m['tardanzas']) · {{ $m['tardanzas'] }} tardanzas @endif
                                    @if($m['faltas']) · {{ $m['faltas'] }} faltas @endif
                                </small>
                            </td>
                            <td data-label="Horas trabajadas">{{ $horasReporte($m['minutos_trabajados']) }}</td>
                            <td data-label="Actividades finalizadas">{{ $m['finalizadas'] }}</td>
                            <td data-label="Pendientes del área">{{ $areaData['metricas']['pendientes_abiertos'] }}</td>
                        </tr>
                    @endforeach
                @empty
                    <tr><td colspan="{{ $reporte['esVistaGeneral'] ? 6 : 5 }}" class="dashboard-empty">Sin información para el periodo.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($reporte['esVistaGeneral'])
        <div class="dashboard-report__detail">
            <h4>Detalle administrativo</h4>
            @foreach($reporte['areasReporte'] as $areaData)
                <details class="dashboard-report__area">
                    <summary>
                        <span><strong>{{ $areaData['area']->nombre }}</strong><small>{{ $areaData['metricas']['trabajadores'] }} trabajadores</small></span>
                        <span>{{ $areaData['metricas']['actividades'] }} actividades</span>
                        <span>{{ $areaData['metricas']['finalizadas'] }} finalizadas</span>
                        <b>Ver detalle</b>
                    </summary>
                    <div class="dashboard-report__workers">
                        @foreach($areaData['trabajadores'] as $trabajadorData)
                            <details class="dashboard-report__worker">
                                <summary>
                                    <span>
                                        <strong>{{ $trabajadorData['trabajador']->nombres }} {{ $trabajadorData['trabajador']->apellidos }}</strong>
                                        <small>{{ $trabajadorData['trabajador']->cargo ?? 'Sin cargo' }}</small>
                                    </span>
                                    <b>Entradas, salidas y avances</b>
                                </summary>
                                <div class="dashboard-report__worker-body">
                                    <div>
                                        <h5>Asistencia</h5>
                                        <ul>
                                            @forelse($trabajadorData['asistencias'] as $asistencia)
                                                <li>
                                                    <time>{{ $asistencia->fecha->format('d/m/Y') }}</time>
                                                    <span>
                                                        Entrada {{ $asistencia->hora_entrada ? substr($asistencia->hora_entrada, 0, 5) : '—' }} ·
                                                        @if($asistencia->hora_programada_salida_refrigerio)
                                                            Refrigerio {{ $asistencia->hora_salida_refrigerio ? substr($asistencia->hora_salida_refrigerio, 0, 5) : '—' }} ·
                                                            Retorno {{ $asistencia->hora_retorno_refrigerio ? substr($asistencia->hora_retorno_refrigerio, 0, 5) : '—' }} ·
                                                        @endif
                                                        Salida final {{ $asistencia->hora_salida ? substr($asistencia->hora_salida, 0, 5) : '—' }} ·
                                                        {{ $estadoReporte($asistencia->estado) }}
                                                    </span>
                                                </li>
                                            @empty
                                                <li>Sin asistencias registradas.</li>
                                            @endforelse
                                        </ul>
                                    </div>
                                    <div>
                                        <h5>Actividades</h5>
                                        <ul>
                                            @forelse($trabajadorData['actividades'] as $actividad)
                                                <li><time>{{ $actividad->reporte->fecha_presentacion->format('d/m') }}</time><span>{{ $actividad->descripcion }}</span></li>
                                            @empty
                                                <li>Sin actividades registradas.</li>
                                            @endforelse
                                        </ul>
                                    </div>
                                    <div>
                                        <h5>Tareas</h5>
                                        <ul>
                                            @forelse($trabajadorData['pendientes'] as $pendiente)
                                                <li>
                                                    <time>{{ $pendiente->created_at->format('d/m') }}</time>
                                                    <span>{{ $pendiente->descripcion }} · {{ $estadoReporte($pendiente->estado_revision ?? $pendiente->estado) }}</span>
                                                </li>
                                            @empty
                                                <li>Sin tareas relacionadas.</li>
                                            @endforelse
                                        </ul>
                                    </div>
                                </div>
                            </details>
                        @endforeach
                    </div>
                </details>
            @endforeach
        </div>
    @endif
</section>
