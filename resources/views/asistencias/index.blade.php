@extends('layouts.app')

@section('title', 'Asistencias')
@section('page-title', 'Asistencias')
@section('page-subtitle', 'Control diario de asistencia del personal')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/asistencias.css') }}">
@endpush

@php
    $estadoActual = (string) request('estado', '');
    $estadoEtiquetas = [
        'INCIDENCIAS' => 'Todas las incidencias',
        'EXCEPCIONES' => 'Todas las excepciones',
        'PRESENTE' => 'Presente',
        'TARDANZA' => 'Tardanza',
        'FALTA' => 'Falta',
        'JUSTIFICADO' => 'Justificado',
        'CAMPO' => 'Campo',
        'VACACIONES' => 'Vacaciones',
        'PERMISO' => 'Permiso',
        'FERIADO' => 'Feriado',
        'NO_LABORABLE' => 'No laborable',
        'SIN_REGISTRO' => 'Sin registro',
    ];
    $estadosVisuales = [
        'PRESENTE' => 'Presente',
        'TARDANZA' => 'Tardanza',
        'FALTA' => 'Falta',
        'JUSTIFICADO' => 'Justificado',
        'CAMPO' => 'Campo',
        'VACACIONES' => 'Vacaciones',
        'PERMISO' => 'Permiso',
        'FERIADO' => 'Feriado',
        'NO_LABORABLE' => 'No laborable',
        'SIN_REGISTRO' => 'Sin registro',
    ];
    $origenesVisuales = [
        'BIOMETRICO' => 'Biométrico',
        'MANUAL' => 'Manual',
        'MIXTO' => 'Mixto',
    ];
    $baseSinEstado = request()->except(['page', 'estado']);
    $baseSinPeriodo = request()->except(['page', 'fecha_desde', 'fecha_hasta']);
    $hoy = today();
    $fechaHoy = $hoy->toDateString();
    $fechaAyer = $hoy->copy()->subDay()->toDateString();
    $fechaInicioSemana = $hoy->copy()->subDays(6)->toDateString();
    $esHoy = request('fecha_desde') === $fechaHoy && request('fecha_hasta') === $fechaHoy;
    $esAyer = request('fecha_desde') === $fechaAyer && request('fecha_hasta') === $fechaAyer;
    $esUltimosSiete = request('fecha_desde') === $fechaInicioSemana && request('fecha_hasta') === $fechaHoy;
    $sinPeriodo = !request()->filled('fecha_desde') && !request()->filled('fecha_hasta');
    $tieneFiltros = request()->hasAny(['buscar', 'fecha_desde', 'fecha_hasta', 'estado', 'tipo_control']);
    $metricas = [
        ['clave' => '', 'titulo' => 'Total del periodo', 'valor' => $resumen['total'], 'detalle' => 'Registros procesados', 'icono' => 'attendance', 'tono' => 'total'],
        ['clave' => 'PRESENTE', 'titulo' => 'Presentes', 'valor' => $resumen['presentes'], 'detalle' => 'Sin incidencias', 'icono' => 'check', 'tono' => 'success'],
        ['clave' => 'INCIDENCIAS', 'titulo' => 'Incidencias', 'valor' => $resumen['incidencias'], 'detalle' => 'Requieren revisión', 'icono' => 'alert', 'tono' => 'warning'],
        ['clave' => 'EXCEPCIONES', 'titulo' => 'Excepciones', 'valor' => $resumen['excepciones'], 'detalle' => 'Justificadas o especiales', 'icono' => 'calendar', 'tono' => 'info'],
    ];
@endphp

@section('content')
    <div class="asistencias-page">
        <header class="page-header asistencias-hero">
            <div class="asistencias-hero__content">
                <span class="asistencias-eyebrow">Control diario</span>
                <h2 class="page-header__title">Asistencia del personal</h2>
                <p class="page-header__description">
                    Revisa primero el resultado de cada jornada y abre el detalle únicamente cuando necesites comprobar
                    horarios, marcaciones o incidencias.
                </p>
            </div>

            <div class="asistencias-hero__actions">
                <span class="asistencias-scope">
                    <x-ui-icon name="users" />
                    {{ $puedeProcesar ? 'Todo el personal' : 'Personal de tu área' }}
                </span>

                @if ($puedeProcesar)
                    <button type="button" class="button button--primary" data-modal-open="modalProcesarAsistencia">
                        <x-ui-icon name="sync" />
                        <span>Procesar fecha</span>
                    </button>
                @endif
            </div>
        </header>

        <section class="asistencias-summary" aria-label="Resumen del periodo seleccionado">
            @foreach ($metricas as $metrica)
                @php
                    $parametrosMetrica = $metrica['clave'] === ''
                        ? $baseSinEstado
                        : array_merge($baseSinEstado, ['estado' => $metrica['clave']]);
                    $metricaActiva = $estadoActual === $metrica['clave'];
                @endphp
                <a href="{{ route('asistencias.index', $parametrosMetrica) }}"
                    class="asistencias-metric asistencias-metric--{{ $metrica['tono'] }} {{ $metricaActiva ? 'is-active' : '' }}"
                    @if ($metricaActiva) aria-current="page" @endif>
                    <span class="asistencias-metric__icon"><x-ui-icon :name="$metrica['icono']" /></span>
                    <span class="asistencias-metric__content">
                        <span>{{ $metrica['titulo'] }}</span>
                        <strong>{{ number_format($metrica['valor']) }}</strong>
                        <small>{{ $metrica['detalle'] }}</small>
                    </span>
                    <x-ui-icon name="chevron-right" class="asistencias-metric__arrow" />
                </a>
            @endforeach
        </section>

        <details class="asistencias-guide">
            <summary>
                <span class="asistencias-guide__icon"><x-ui-icon name="clock" /></span>
                <span>
                    <strong>¿Cómo se obtiene el resultado de una jornada?</strong>
                    <small>Consulta el flujo antes de revisar una incidencia.</small>
                </span>
                <x-ui-icon name="chevron-down" class="asistencias-guide__chevron" />
            </summary>
            <div class="asistencias-guide__flow" aria-label="Flujo de procesamiento de asistencia">
                <div><span>1</span><strong>Marcaciones</strong><small>El biométrico o el registro manual envía las horas.</small></div>
                <div><span>2</span><strong>Horario aplicado</strong><small>Se comparan jornada, tolerancia y tipo de control.</small></div>
                <div><span>3</span><strong>Resultado</strong><small>Se calcula presencia, tardanza, falta o excepción.</small></div>
            </div>
        </details>

        <section class="panel asistencias-panel" aria-labelledby="asistenciasListadoTitulo">
            <div class="asistencias-panel__intro">
                <div>
                    <span class="asistencias-eyebrow">Registros procesados</span>
                    <h3 id="asistenciasListadoTitulo">Jornadas del personal</h3>
                    <p>La tabla resume lo importante; el botón de detalle conserva toda la información de la jornada.</p>
                </div>
                <div class="asistencias-panel__count" aria-live="polite">
                    <strong>{{ number_format($asistencias->total()) }}</strong>
                    <span>{{ $asistencias->total() === 1 ? 'registro' : 'registros' }}</span>
                    @if ($asistencias->total() > 0)
                        <small>{{ $asistencias->firstItem() }}–{{ $asistencias->lastItem() }} en esta página</small>
                    @endif
                </div>
            </div>

            <div class="panel__header asistencias-filter-panel">
                <nav class="asistencias-periods" aria-label="Periodos rápidos">
                    <span>Periodo rápido</span>
                    <a href="{{ route('asistencias.index', $baseSinPeriodo) }}" class="{{ $sinPeriodo ? 'is-active' : '' }}">Todo</a>
                    <a href="{{ route('asistencias.index', array_merge($baseSinPeriodo, ['fecha_desde' => $fechaHoy, 'fecha_hasta' => $fechaHoy])) }}"
                        class="{{ $esHoy ? 'is-active' : '' }}">Hoy</a>
                    <a href="{{ route('asistencias.index', array_merge($baseSinPeriodo, ['fecha_desde' => $fechaAyer, 'fecha_hasta' => $fechaAyer])) }}"
                        class="{{ $esAyer ? 'is-active' : '' }}">Ayer</a>
                    <a href="{{ route('asistencias.index', array_merge($baseSinPeriodo, ['fecha_desde' => $fechaInicioSemana, 'fecha_hasta' => $fechaHoy])) }}"
                        class="{{ $esUltimosSiete ? 'is-active' : '' }}">Últimos 7 días</a>
                </nav>

                <form method="GET" action="{{ route('asistencias.index') }}" class="asistencias-filtros">
                    <label class="filter-field" for="asistenciaBuscar">
                        <span class="filter-field__label">Trabajador</span>
                        <input id="asistenciaBuscar" type="search" name="buscar" value="{{ request('buscar') }}"
                            class="form-control" placeholder="DNI, nombre o apellido">
                    </label>

                    <label class="filter-field" for="asistenciaDesde">
                        <span class="filter-field__label">Desde</span>
                        <input id="asistenciaDesde" type="date" name="fecha_desde" value="{{ request('fecha_desde') }}"
                            class="form-control">
                    </label>

                    <label class="filter-field" for="asistenciaHasta">
                        <span class="filter-field__label">Hasta</span>
                        <input id="asistenciaHasta" type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}"
                            class="form-control">
                    </label>

                    <label class="filter-field" for="asistenciaEstado">
                        <span class="filter-field__label">Resultado</span>
                        <select id="asistenciaEstado" name="estado" class="form-select">
                            <option value="">Todos los resultados</option>
                            @foreach ($estadoEtiquetas as $valor => $texto)
                                <option value="{{ $valor }}" @selected($estadoActual === $valor)>{{ $texto }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="filter-field" for="asistenciaControl">
                        <span class="filter-field__label">Tipo de control</span>
                        <select id="asistenciaControl" name="tipo_control" class="form-select">
                            <option value="">Todos los controles</option>
                            <option value="OBLIGATORIO" @selected(request('tipo_control') === 'OBLIGATORIO')>Obligatorio</option>
                            <option value="REFERENCIAL" @selected(request('tipo_control') === 'REFERENCIAL')>Referencial</option>
                        </select>
                    </label>

                    <div class="asistencias-filtros__actions">
                        <button type="submit" class="button button--primary"><x-ui-icon name="filter" /> Aplicar filtros</button>
                        <a href="{{ route('asistencias.index') }}" class="button button--secondary"><x-ui-icon name="reset" /> Limpiar</a>
                    </div>
                </form>

                @if ($tieneFiltros)
                    <div class="asistencias-active-filters" aria-label="Filtros activos">
                        <strong>Vista filtrada</strong>
                        @if (request()->filled('buscar'))
                            <span>Trabajador: “{{ request('buscar') }}”</span>
                        @endif
                        @if (request()->filled('fecha_desde') || request()->filled('fecha_hasta'))
                            <span>Periodo: {{ request('fecha_desde') ?: 'Inicio' }} → {{ request('fecha_hasta') ?: 'Hoy' }}</span>
                        @endif
                        @if ($estadoActual !== '')
                            <span>Resultado: {{ $estadoEtiquetas[$estadoActual] ?? $estadoActual }}</span>
                        @endif
                        @if (request()->filled('tipo_control'))
                            <span>Control: {{ ucfirst(strtolower(request('tipo_control'))) }}</span>
                        @endif
                    </div>
                @endif
            </div>

            <div class="asistencias-table-shell">
                <table class="table attendance-ledger">
                    <caption class="sr-only">Asistencias procesadas del personal</caption>
                    <colgroup>
                        <col class="attendance-ledger__date-col">
                        <col class="attendance-ledger__worker-col">
                        <col class="attendance-ledger__context-col">
                        <col class="attendance-ledger__timeline-col">
                        <col class="attendance-ledger__balance-col">
                        <col class="attendance-ledger__result-col">
                        <col class="attendance-ledger__action-col">
                    </colgroup>
                    <thead>
                        <tr>
                            <th scope="col">Fecha</th>
                            <th scope="col">Trabajador</th>
                            <th scope="col">Jornada</th>
                            <th scope="col">Marcaciones</th>
                            <th scope="col">Balance</th>
                            <th scope="col">Resultado</th>
                            <th scope="col"><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($asistencias as $asistencia)
                            @php
                                $nombreCompleto = trim(($asistencia->trabajador?->nombres ?? '') . ' ' . ($asistencia->trabajador?->apellidos ?? '')) ?: 'Trabajador sin nombre';
                                $iniciales = mb_strtoupper(mb_substr((string) ($asistencia->trabajador?->nombres ?: 'T'), 0, 1) . mb_substr((string) ($asistencia->trabajador?->apellidos ?: ''), 0, 1));
                                $estado = (string) $asistencia->estado;
                                $estadoTexto = $estadosVisuales[$estado] ?? ucfirst(strtolower(str_replace('_', ' ', $estado)));
                                $estadoClase = strtolower(str_replace('_', '-', $estado));
                                $origen = $asistencia->origen_marcacion;
                                $origenTexto = $origenesVisuales[$origen] ?? 'Sin marcación';
                                $controlTexto = match ($asistencia->tipo_control_aplicado) {
                                    'OBLIGATORIO' => 'Obligatorio',
                                    'REFERENCIAL' => 'Referencial',
                                    default => 'Sin definir',
                                };
                                $horaEntrada = $asistencia->hora_entrada ? substr($asistencia->hora_entrada, 0, 5) : null;
                                $horaSalidaRefrigerio = $asistencia->hora_salida_refrigerio ? substr($asistencia->hora_salida_refrigerio, 0, 5) : null;
                                $horaRetornoRefrigerio = $asistencia->hora_retorno_refrigerio ? substr($asistencia->hora_retorno_refrigerio, 0, 5) : null;
                                $horaSalida = $asistencia->hora_salida ? substr($asistencia->hora_salida, 0, 5) : null;
                                $programadaEntrada = $asistencia->hora_programada_entrada ? substr($asistencia->hora_programada_entrada, 0, 5) : null;
                                $programadaSalidaRefrigerio = $asistencia->hora_programada_salida_refrigerio ? substr($asistencia->hora_programada_salida_refrigerio, 0, 5) : null;
                                $programadaRetornoRefrigerio = $asistencia->hora_programada_retorno_refrigerio ? substr($asistencia->hora_programada_retorno_refrigerio, 0, 5) : null;
                                $programadaSalida = $asistencia->hora_programada_salida ? substr($asistencia->hora_programada_salida, 0, 5) : null;
                                $programadoTexto = $programadaEntrada
                                    ? ($programadaSalidaRefrigerio
                                        ? "{$programadaEntrada} · {$programadaSalidaRefrigerio} / {$programadaRetornoRefrigerio} · {$programadaSalida}"
                                        : "{$programadaEntrada} → {$programadaSalida}")
                                    : 'Sin horario programado';
                                $horasTrabajadas = intdiv((int) $asistencia->minutos_trabajados, 60);
                                $minutosTrabajados = (int) $asistencia->minutos_trabajados % 60;
                                $trabajadoTexto = $asistencia->minutos_trabajados > 0 ? "{$horasTrabajadas} h {$minutosTrabajados} min" : 'Sin cálculo';
                                $tardanzaTexto = $asistencia->minutos_tardanza > 0 ? "{$asistencia->minutos_tardanza} min" : 'A tiempo';
                                $marcaciones = [
                                    ['etiqueta' => 'Entrada', 'hora' => $horaEntrada, 'estimada' => false],
                                    ['etiqueta' => 'Refrigerio', 'hora' => $horaSalidaRefrigerio, 'estimada' => false],
                                    ['etiqueta' => 'Retorno', 'hora' => $horaRetornoRefrigerio, 'estimada' => (bool) $asistencia->retorno_refrigerio_estimado],
                                    ['etiqueta' => 'Salida', 'hora' => $horaSalida, 'estimada' => (bool) $asistencia->salida_estimada],
                                ];
                            @endphp

                            <tr class="attendance-row attendance-row--{{ $estadoClase }}">
                                <td class="attendance-date" data-label="Fecha">
                                    <time datetime="{{ $asistencia->fecha?->toDateString() }}">
                                        <strong>{{ $asistencia->fecha?->translatedFormat('d M') }}</strong>
                                        <span>{{ $asistencia->fecha?->format('Y') }}</span>
                                    </time>
                                </td>

                                <td class="attendance-worker" data-label="Trabajador">
                                    <span class="attendance-worker__avatar" aria-hidden="true">{{ $iniciales }}</span>
                                    <span class="attendance-worker__identity">
                                        <strong>{{ $nombreCompleto }}</strong>
                                        <span>DNI {{ $asistencia->trabajador?->dni ?? '—' }}</span>
                                        <small>{{ $asistencia->trabajador?->area?->nombre ?? 'Sin área asignada' }}</small>
                                    </span>
                                </td>

                                <td class="attendance-context" data-label="Jornada">
                                    <strong>{{ $asistencia->horario?->nombre ?? 'Sin horario' }}</strong>
                                    <span class="attendance-control attendance-control--{{ strtolower($asistencia->tipo_control_aplicado ?? 'none') }}">{{ $controlTexto }}</span>
                                    <small>{{ $origenTexto }}</small>
                                </td>

                                <td class="attendance-punches" data-label="Marcaciones">
                                    <div class="attendance-timeline" aria-label="Marcaciones de {{ $nombreCompleto }}">
                                        @foreach ($marcaciones as $marcacion)
                                            <span class="attendance-timeline__step {{ $marcacion['hora'] ? 'has-value' : 'is-missing' }} {{ $marcacion['estimada'] ? 'is-estimated' : '' }}">
                                                <small>{{ $marcacion['etiqueta'] }}</small>
                                                <strong>{{ $marcacion['hora'] ?? '—' }}</strong>
                                                @if ($marcacion['estimada'])
                                                    <em>Est.</em>
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                    <span class="attendance-scheduled"><strong>Programado:</strong> {{ $programadoTexto }}</span>
                                </td>

                                <td class="attendance-balance" data-label="Balance">
                                    <span><small>Trabajado</small><strong>{{ $trabajadoTexto }}</strong></span>
                                    <span class="{{ $asistencia->minutos_tardanza > 0 ? 'has-delay' : 'is-on-time' }}">
                                        <small>Tardanza</small><strong>{{ $tardanzaTexto }}</strong>
                                    </span>
                                </td>

                                <td class="attendance-result" data-label="Resultado">
                                    <span class="attendance-status attendance-status--{{ $estadoClase }}">{{ $estadoTexto }}</span>
                                    <p title="{{ $asistencia->observacion }}">{{ $asistencia->observacion ?: 'Sin observaciones.' }}</p>
                                </td>

                                <td class="attendance-action" data-label="Detalle">
                                    <button type="button" class="attendance-detail-button" data-asistencia-detail
                                        aria-label="Ver detalle de asistencia de {{ $nombreCompleto }}" data-tooltip="Ver detalle"
                                        data-worker="{{ $nombreCompleto }}" data-dni="{{ $asistencia->trabajador?->dni ?? '—' }}"
                                        data-area="{{ $asistencia->trabajador?->area?->nombre ?? 'Sin área asignada' }}"
                                        data-date="{{ $asistencia->fecha?->translatedFormat('d \d\e F \d\e Y') ?? '—' }}"
                                        data-status="{{ $estadoTexto }}" data-status-key="{{ $estadoClase }}"
                                        data-schedule="{{ $asistencia->horario?->nombre ?? 'Sin horario' }}"
                                        data-control="{{ $controlTexto }}" data-origin="{{ $origenTexto }}"
                                        data-programmed="{{ $programadoTexto }}" data-tolerance="{{ (int) $asistencia->tolerancia_aplicada }} min"
                                        data-entry="{{ $horaEntrada ?? '—' }}" data-lunch-out="{{ $horaSalidaRefrigerio ?? '—' }}"
                                        data-lunch-in="{{ $horaRetornoRefrigerio ?? '—' }}"
                                        data-lunch-in-estimated="{{ $asistencia->retorno_refrigerio_estimado ? 'true' : 'false' }}"
                                        data-exit="{{ $horaSalida ?? '—' }}" data-exit-estimated="{{ $asistencia->salida_estimada ? 'true' : 'false' }}"
                                        data-worked="{{ $trabajadoTexto }}" data-delay="{{ $tardanzaTexto }}"
                                        data-observation="{{ $asistencia->observacion ?: 'Sin observaciones.' }}"
                                        data-processed="{{ $asistencia->procesado_en?->format('d/m/Y H:i') ?? '—' }}">
                                        <x-ui-icon name="eye" />
                                        <span>Ver detalle</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr class="attendance-empty-row">
                                <td colspan="7">
                                    <div class="attendance-empty">
                                        <span><x-ui-icon name="attendance" /></span>
                                        <strong>No encontramos asistencias</strong>
                                        <p>
                                            @if ($puedeProcesar)
                                                Ajusta los filtros o procesa una fecha para generar los registros del personal activo.
                                            @else
                                                No hay registros disponibles para los filtros seleccionados.
                                            @endif
                                        </p>
                                        @if ($tieneFiltros)
                                            <a href="{{ route('asistencias.index') }}" class="button button--secondary">Limpiar filtros</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($asistencias->hasPages())
                <div class="pagination-container asistencias-pagination">{{ $asistencias->links() }}</div>
            @endif
        </section>

        <dialog id="asistenciaDetalleDialog" class="asistencia-detail-dialog" aria-labelledby="asistenciaDetalleTitulo"
            aria-describedby="asistenciaDetalleDescripcion">
            <div class="asistencia-detail-dialog__header">
                <div>
                    <span>DETALLE DE JORNADA</span>
                    <h3 id="asistenciaDetalleTitulo">Detalle de asistencia</h3>
                    <p id="asistenciaDetalleDescripcion">Información procesada para la fecha seleccionada.</p>
                </div>
                <button type="button" data-asistencia-close aria-label="Cerrar detalle"><x-ui-icon name="close" /></button>
            </div>

            <div class="asistencia-detail-dialog__body">
                <section class="asistencia-detail-summary">
                    <div>
                        <span id="asistenciaDetalleFecha">—</span>
                        <strong id="asistenciaDetalleTrabajador">—</strong>
                        <small><span id="asistenciaDetalleDni">—</span> · <span id="asistenciaDetalleArea">—</span></small>
                    </div>
                    <span id="asistenciaDetalleEstado" class="attendance-status">—</span>
                </section>

                <section class="asistencia-detail-group" aria-labelledby="detalleContextoTitulo">
                    <h4 id="detalleContextoTitulo">Contexto laboral</h4>
                    <dl class="asistencia-detail-grid">
                        <div><dt>Horario</dt><dd id="asistenciaDetalleHorario">—</dd></div>
                        <div><dt>Control</dt><dd id="asistenciaDetalleControl">—</dd></div>
                        <div><dt>Origen</dt><dd id="asistenciaDetalleOrigen">—</dd></div>
                        <div><dt>Tolerancia</dt><dd id="asistenciaDetalleTolerancia">—</dd></div>
                    </dl>
                    <p class="asistencia-detail-programmed"><strong>Jornada programada:</strong> <span id="asistenciaDetalleProgramado">—</span></p>
                </section>

                <section class="asistencia-detail-group" aria-labelledby="detalleMarcasTitulo">
                    <h4 id="detalleMarcasTitulo">Marcaciones registradas</h4>
                    <div class="asistencia-detail-punches">
                        <div><span>Entrada</span><strong id="asistenciaDetalleEntrada">—</strong></div>
                        <div><span>Salida a refrigerio</span><strong id="asistenciaDetalleRefrigerio">—</strong></div>
                        <div><span>Retorno</span><strong id="asistenciaDetalleRetorno">—</strong></div>
                        <div><span>Salida final</span><strong id="asistenciaDetalleSalida">—</strong></div>
                    </div>
                </section>

                <section class="asistencia-detail-group" aria-labelledby="detalleResultadoTitulo">
                    <h4 id="detalleResultadoTitulo">Resultado calculado</h4>
                    <dl class="asistencia-detail-grid asistencia-detail-grid--result">
                        <div><dt>Tiempo trabajado</dt><dd id="asistenciaDetalleTrabajado">—</dd></div>
                        <div><dt>Tardanza</dt><dd id="asistenciaDetalleTardanza">—</dd></div>
                        <div><dt>Procesado</dt><dd id="asistenciaDetalleProcesado">—</dd></div>
                    </dl>
                    <div class="asistencia-detail-note"><strong>Observación</strong><p id="asistenciaDetalleObservacion">—</p></div>
                </section>
            </div>
        </dialog>

        @if ($puedeProcesar)
            <x-modal id="modalProcesarAsistencia" title="Procesar asistencia por fecha" size="small">
                <form method="POST" action="{{ route('asistencias.procesar') }}" class="form asistencia-process-form">
                    @csrf
                    <p class="asistencia-process-form__intro">Selecciona un día para recalcular la asistencia de todo el personal activo.</p>

                    <label class="form-group" for="asistenciaProcesarFecha">
                        <span class="form-label">Fecha a procesar *</span>
                        <input id="asistenciaProcesarFecha" type="date" name="fecha" value="{{ now()->subDay()->toDateString() }}"
                            class="form-control" required>
                    </label>

                    <div class="asistencia-process-info">
                        <span class="asistencia-process-info__icon"><x-ui-icon name="alert" /></span>
                        <div>
                            <strong>Este proceso crea o actualiza registros</strong>
                            <ul>
                                <li>Vuelve a comparar las marcaciones con el horario asignado.</li>
                                <li>Completa faltas y excepciones del personal sin marcación.</li>
                                <li>En días finalizados puede estimar retornos o salidas olvidadas.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="modal__actions">
                        <button type="button" class="button button--secondary" data-modal-close>Cancelar</button>
                        <button type="submit" class="button button--primary"><x-ui-icon name="sync" /> Procesar fecha</button>
                    </div>
                </form>
            </x-modal>
        @endif
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/pages/asistencias.js') }}"></script>
@endpush
