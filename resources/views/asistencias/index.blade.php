@extends('layouts.app')

@section('title', 'Asistencias')
@section('page-title', 'Asistencias')
@section('page-subtitle', 'Control diario de asistencia del personal')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/asistencias.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/asistencias-modern.css') }}">
@endpush

@section('content')
    {{-- ENCABEZADO --}}
    <div class="page-header">
        <div>
            <h2 class="page-header__title">Asistencias</h2>
            <p class="page-header__description">
                @if ($puedeProcesar)
                    Las marcaciones generan la asistencia automáticamente. Usa el cierre diario solo para completar faltas o
                    recalcular registros.
                @else
                    Consulta la asistencia, las horas trabajadas y las tardanzas del personal.
                @endif
            </p>
        </div>

        @if ($puedeProcesar)
            <button type="button" class="button button--primary" data-modal-open="modalProcesarAsistencia">
                Reprocesar día
            </button>
        @endif
    </div>

    {{-- FILTROS --}}
    <section class="panel asistencias-panel">
        <div class="panel__header">
            <form method="GET" action="{{ route('asistencias.index') }}" class="asistencias-filtros">
                <div class="form-group">
                    <label class="form-label">Trabajador</label>
                    <input type="text" name="buscar" value="{{ request('buscar') }}" class="form-control"
                        placeholder="DNI o nombre...">
                </div>

                <div class="form-group">
                    <label class="form-label">Desde</label>
                    <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}" class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}" class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="">Todos</option>
                        @foreach ([
            'INCIDENCIAS' => 'Todas las incidencias',
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
        ] as $valor => $texto)
                            <option value="{{ $valor }}" @selected(request('estado') === $valor)>
                                {{ $texto }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Control</label>
                    <select name="tipo_control" class="form-select">
                        <option value="">Todos</option>
                        <option value="OBLIGATORIO" @selected(request('tipo_control') === 'OBLIGATORIO')>Obligatorio</option>
                        <option value="REFERENCIAL" @selected(request('tipo_control') === 'REFERENCIAL')>Referencial</option>
                    </select>
                </div>

                <div class="asistencias-filtros__actions">
                    <button type="submit" class="button button--primary">Filtrar</button>
                    <a href="{{ route('asistencias.index') }}" class="button button--secondary">Limpiar</a>
                </div>
            </form>
        </div>

        {{-- TABLA --}}
        <div class="table-container">
            <table class="table asistencias-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Trabajador</th>
                        <th>Horario</th>
                        <th>Control</th>
                        <th>Origen</th>
                        <th>Programado</th>
                        <th>Entrada mañana</th>
                        <th>Salida refrigerio</th>
                        <th>Retorno / entrada tarde</th>
                        <th>Salida final</th>
                        <th>Trabajado</th>
                        <th>Tardanza</th>
                        <th>Estado</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($asistencias as $asistencia)
                        <tr class="asistencia-row">
                            <td>
                                <strong>{{ $asistencia->fecha?->format('d/m/Y') }}</strong>
                            </td>
                            <td>
                                <div class="asistencia-trabajador">
                                    <strong>{{ $asistencia->trabajador?->nombres }}
                                        {{ $asistencia->trabajador?->apellidos }}</strong>
                                    <span>DNI: {{ $asistencia->trabajador?->dni ?? '-' }}</span>
                                    <small>{{ $asistencia->trabajador?->area?->nombre ?? '-' }}</small>
                                </div>
                            </td>
                            <td>{{ $asistencia->horario?->nombre ?? '-' }}</td>
                            <td>
                                @if ($asistencia->tipo_control_aplicado === 'OBLIGATORIO')
                                    <span class="badge badge--primary">Obligatorio</span>
                                @elseif($asistencia->tipo_control_aplicado === 'REFERENCIAL')
                                    <span class="badge badge--info">Referencial</span>
                                @else
                                    <span class="table-secondary-text">-</span>
                                @endif
                            </td>
                            <td>
                                @switch($asistencia->origen_marcacion)
                                    @case('BIOMETRICO')
                                        <span class="badge badge--success">Biométrico</span>
                                    @break

                                    @case('MANUAL')
                                        <span class="badge badge--warning">Manual</span>
                                    @break

                                    @case('MIXTO')
                                        <span class="badge badge--info">Mixto</span>
                                    @break

                                    @default
                                        <span class="table-secondary-text">Sin marcación</span>
                                @endswitch
                            </td>
                            <td>
                                @if ($asistencia->hora_programada_entrada)
                                    <div class="horario-programado">
                                        <span>{{ substr($asistencia->hora_programada_entrada, 0, 5) }}</span>
                                        <span>-</span>
                                        @if ($asistencia->hora_programada_salida_refrigerio)
                                            <span>{{ substr($asistencia->hora_programada_salida_refrigerio, 0, 5) }}</span>
                                            <span>/</span>
                                            <span>{{ substr($asistencia->hora_programada_retorno_refrigerio, 0, 5) }}</span>
                                            <span>-</span>
                                        @endif
                                        <span>{{ substr($asistencia->hora_programada_salida, 0, 5) }}</span>
                                    </div>
                                    <small class="table-secondary-text">Tol. {{ $asistencia->tolerancia_aplicada }}
                                        min</small>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if ($asistencia->hora_entrada)
                                    <strong class="hora-marcacion">{{ substr($asistencia->hora_entrada, 0, 5) }}</strong>
                                @else
                                    <span class="sin-marcacion">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($asistencia->hora_salida_refrigerio)
                                    <strong
                                        class="hora-marcacion">{{ substr($asistencia->hora_salida_refrigerio, 0, 5) }}</strong>
                                @else
                                    <span class="sin-marcacion">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($asistencia->hora_retorno_refrigerio)
                                    <strong
                                        class="hora-marcacion">{{ substr($asistencia->hora_retorno_refrigerio, 0, 5) }}</strong>
                                    @if ($asistencia->retorno_refrigerio_estimado)
                                        <small class="table-secondary-text">Automática</small>
                                    @endif
                                @else
                                    <span class="sin-marcacion">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($asistencia->hora_salida)
                                    <strong class="hora-marcacion">{{ substr($asistencia->hora_salida, 0, 5) }}</strong>
                                    @if ($asistencia->salida_estimada)
                                        <small class="table-secondary-text">Automática</small>
                                    @endif
                                @else
                                    <span class="sin-marcacion">—</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $horas = intdiv($asistencia->minutos_trabajados, 60);
                                    $minutos = $asistencia->minutos_trabajados % 60;
                                @endphp
                                @if ($asistencia->minutos_trabajados > 0)
                                    <strong>{{ $horas }}h {{ $minutos }}m</strong>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if ($asistencia->minutos_tardanza > 0)
                                    <span class="tardanza-minutos">{{ $asistencia->minutos_tardanza }} min</span>
                                @else
                                    <span class="table-secondary-text">0 min</span>
                                @endif
                            </td>
                            <td>
                                @switch($asistencia->estado)
                                    @case('PRESENTE')
                                        <span class="badge badge--success">Presente</span>
                                    @break

                                    @case('TARDANZA')
                                        <span class="badge badge--warning">Tardanza</span>
                                    @break

                                    @case('FALTA')
                                        <span class="badge badge--danger">Falta</span>
                                    @break

                                    @case('SIN_REGISTRO')
                                    @case('NO_LABORABLE')
                                        <span
                                            class="badge badge--secondary">{{ ucfirst(strtolower(str_replace('_', ' ', $asistencia->estado))) }}</span>
                                    @break

                                    @case('CAMPO')
                                    @case('VACACIONES')

                                    @case('PERMISO')
                                    @case('FERIADO')

                                    @case('JUSTIFICADO')
                                        <span class="badge badge--info">{{ ucfirst(strtolower($asistencia->estado)) }}</span>
                                    @break

                                    @default
                                        <span class="badge">{{ $asistencia->estado }}</span>
                                @endswitch

                                @if ($asistencia->observacion)
                                    <div class="asistencia-observacion">{{ $asistencia->observacion }}</div>
                                @endif
                            </td>
                            <td class="asistencia-action-cell">
                                <button type="button" class="asistencia-detail-button" data-asistencia-detail>Ver detalle <span aria-hidden="true">⌄</span></button>
                            </td>
                        </tr>
                        @empty
                            <tr>
                                <td colspan="14" class="table-empty">
                                    <div class="empty-state">
                                        <strong>No existen asistencias procesadas</strong>
                                        <span>
                                            @if ($puedeProcesar)
                                                Las asistencias aparecerán al recibir marcaciones. Usa el cierre diario para
                                                registrar faltas o excepciones sin marcación.
                                            @else
                                                No hay registros disponibles para los filtros seleccionados.
                                            @endif
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <dialog id="asistenciaDetalleDialog" class="asistencia-detail-dialog" aria-labelledby="asistenciaDetalleTitulo">
            <div class="asistencia-detail-dialog__header">
                <div>
                    <span>CONTROL DE ASISTENCIA</span>
                    <h3 id="asistenciaDetalleTitulo">Detalle del registro</h3>
                </div>
                <button type="button" data-asistencia-close aria-label="Cerrar detalle">×</button>
            </div>
            <div class="asistencia-detail-dialog__body" id="asistenciaDetalleContenido"></div>
        </dialog>

        <div class="pagination-container">
            {{ $asistencias->links() }}
        </div>

        @if ($puedeProcesar)
            {{-- MODAL PROCESAR --}}
            <x-modal id="modalProcesarAsistencia" title="Reprocesar asistencia" size="small">
                <form method="POST" action="{{ route('asistencias.procesar') }}" class="form">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Fecha a reprocesar *</label>
                        <input type="date" name="fecha" value="{{ now()->subDay()->toDateString() }}" class="form-control" required>
                    </div>

                    <div class="procesar-info">
                        <strong>¿Qué hará este proceso?</strong>
                        <p>Reprocesará la fecha seleccionada para todo el personal activo. En días terminados también completará
                            el retorno del refrigerio y la salida final olvidados usando el horario programado.</p>
                    </div>

                    <div class="modal__actions">
                        <button type="button" class="button button--secondary" data-modal-close>Cancelar</button>
                        <button type="submit" class="button button--primary">Reprocesar día</button>
                    </div>
                </form>
            </x-modal>
        @endif
    @endsection

@push('scripts')
<script>
    document.addEventListener('click', function (event) {
        const dialog = document.getElementById('asistenciaDetalleDialog');
        if (!dialog) return;
        const trigger = event.target.closest('[data-asistencia-detail]');
        if (trigger) {
            const row = trigger.closest('tr');
            const headings = Array.from(row.closest('table').querySelectorAll('thead th'));
            const cells = Array.from(row.children);
            const content = document.getElementById('asistenciaDetalleContenido');
            content.replaceChildren();
            const title = document.getElementById('asistenciaDetalleTitulo');
            title.textContent = row.querySelector('.asistencia-trabajador strong')?.textContent.trim() || 'Detalle del registro';
            cells.forEach((cell, index) => {
                if (index === cells.length - 1 || index === 1) return;
                const item = document.createElement('div');
                item.className = 'asistencia-detail-dialog__item';
                const label = document.createElement('span');
                label.textContent = headings[index]?.textContent.trim() || 'Dato';
                const value = document.createElement('div');
                Array.from(cell.childNodes).forEach(node => value.appendChild(node.cloneNode(true)));
                item.append(label, value);
                content.appendChild(item);
            });
            dialog.showModal();
        }
        if (event.target.closest('[data-asistencia-close]')) dialog.close();
        if (event.target === dialog) dialog.close();
    });
</script>
@endpush

