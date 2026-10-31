@extends('layouts.app')

@section('title','Marcación manual')
@section('page-title','Marcación manual')
@section('page-subtitle','Registro rápido de entrada y salida del personal')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/marcaciones.css') }}">
@endpush

@section('content')
<div class="marcaciones-page marcaciones-page--manual">

    <header class="marcaciones-header">
        <div>
            <a href="{{ route('marcaciones.index') }}" class="marcaciones-back"><x-ui-icon name="arrow-left" /> Volver a marcaciones</a>
            <h2>Registrar asistencia manual</h2>
            <p>La fecha y hora se tomarán automáticamente al momento de marcar la asistencia.</p>
        </div>

        <div class="marcaciones-now">
            <span>Fecha actual</span>
            <strong>{{ now()->format('d/m/Y') }}</strong>
        </div>
    </header>

    @if(session('success'))
        <div class="marcaciones-alert marcaciones-alert--success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="marcaciones-alert marcaciones-alert--error">{{ session('error') }}</div>
    @endif

    <section class="manual-panel">
        <div class="manual-panel__header">
            <div>
                <span class="mc-section-kicker">CONTROL DIARIO</span>
                <h3>Trabajadores</h3>
                <p>El sistema reconoce entrada, refrigerio, retorno y salida según la hora de cada marcación.</p>
            </div>

            <button type="button" class="button button--secondary" id="toggleRegistroEspecial">
                Registro especial
            </button>
        </div>

        <form method="GET" action="{{ route('marcaciones.manual') }}" class="manual-filters">
            <label class="mc-field manual-filter-field">
                <span class="mc-field__label">Buscar trabajador</span>
                <input type="search" name="buscar" value="{{ request('buscar') }}" class="form-control"
                    placeholder="Buscar por nombre o DNI...">
            </label>

            <label class="mc-field manual-filter-field">
                <span class="mc-field__label">Área</span>
                <select name="area_id" class="form-select">
                    <option value="">Todas las áreas</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id }}" @selected(request('area_id')==$area->id)>{{ $area->nombre }}</option>
                    @endforeach
                </select>
            </label>

            <div class="manual-filters__actions">
                <button type="submit" class="button button--primary">Buscar</button>
                <a href="{{ route('marcaciones.manual') }}" class="button button--secondary">Limpiar</a>
            </div>
        </form>

        <div class="registro-especial" id="registroEspecial" hidden>
            <div class="registro-especial__heading">
                <div>
                    <h4>Registro especial</h4>
                    <p>Utiliza esta opción solo cuando necesites registrar una fecha u hora distinta a la actual.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('marcaciones.store') }}" class="registro-especial__form">
                @csrf

                <div class="form-group">
                    <label class="form-label">Trabajador *</label>
                    <select name="trabajador_id" class="form-select" aria-label="Trabajador" required>
                        <option value="">Seleccionar</option>
                        @foreach($trabajadores as $trabajador)
                            <option value="{{ $trabajador->id }}">
                                {{ $trabajador->apellidos }}, {{ $trabajador->nombres }} - {{ $trabajador->dni }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Fecha y hora *</label>
                    <input type="datetime-local" name="fecha_hora" value="{{ old('fecha_hora') }}" class="form-control" aria-label="Fecha y hora" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Tipo *</label>
                    <select name="tipo" class="form-select" aria-label="Tipo de marcación" required>
                        <option value="">Seleccionar</option>
                        <option value="ENTRADA">Entrada</option>
                        <option value="SALIDA">Salida</option>
                    </select>
                </div>

                <div class="form-group registro-especial__motivo">
                    <label class="form-label">Motivo *</label>
                    <textarea name="motivo_manual" class="form-textarea" rows="3" maxlength="500" aria-label="Motivo del registro especial"
                        placeholder="Indica por qué necesitas registrar manualmente otra fecha u hora..." required></textarea>
                </div>

                <div class="registro-especial__actions">
                    <button type="submit" class="button button--primary">Registrar marcación especial</button>
                </div>
            </form>
        </div>

        <div class="manual-table-wrap">
            <table class="manual-table">
                <thead>
                    <tr>
                        <th scope="col">Trabajador</th>
                        <th scope="col">Área</th>
                        <th scope="col">Entrada mañana</th>
                        <th scope="col">Salida refrigerio</th>
                        <th scope="col">Retorno / entrada tarde</th>
                        <th scope="col">Salida final</th>
                        <th scope="col">Estado de hoy</th>
                        <th scope="col">Acción</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($trabajadores as $trabajador)
                        @php
                            $marcas = $marcacionesHoy->get($trabajador->id,collect());
                            $diaHorario = $trabajador->horarioActual?->horario?->dias?->firstWhere('dia_semana', today()->isoWeekday());
                            $esHorarioPartido = filled($diaHorario?->hora_salida_refrigerio)
                                && filled($diaHorario?->hora_retorno_refrigerio);
                            $esControlObligatorio = $trabajador->horarioActual?->tipo_control === 'OBLIGATORIO';
                            $muestraJornadaPartida = $esHorarioPartido
                                && ($esControlObligatorio || $marcas->count() >= 3);
                            $totalEsperado = $esHorarioPartido ? 4 : 2;
                            $momentos = $clasificacionesHoy->get($trabajador->id, []);

                            if ($muestraJornadaPartida) {
                                $entradaManana = $momentos['ENTRADA_MANANA'] ?? null;
                                $salidaRefrigerio = $momentos['SALIDA_REFRIGERIO'] ?? null;
                                $retorno = $momentos['RETORNO_REFRIGERIO'] ?? null;
                                $salida = $momentos['SALIDA_FINAL'] ?? null;
                            } else {
                                $esTurnoTarde = $marcas->first()
                                    && $esHorarioPartido
                                    && $marcas->first()->fecha_hora?->format('H:i:s') >= $diaHorario->hora_retorno_refrigerio;
                                $entradaManana = $esTurnoTarde ? null : $marcas->first();
                                $salidaRefrigerio = null;
                                $retorno = $esTurnoTarde ? $marcas->first() : null;
                                $salida = $marcas->count() >= 2 ? $marcas->last() : null;
                            }

                            $completo = $marcas->count() >= $totalEsperado;
                            $turnoReferencialRegistrado = !$esControlObligatorio && $marcas->count() === 2;
                        @endphp

                        <tr class="manual-row">
                            <td class="manual-cell--trabajador" data-label="Trabajador">
                                <div class="trabajador-info">
                                    <div class="trabajador-info__avatar">{{ mb_strtoupper(mb_substr($trabajador->nombres ?? 'T',0,1)) }}</div>
                                    <div>
                                        <strong>{{ $trabajador->nombres }} {{ $trabajador->apellidos }}</strong>
                                        <span>DNI: {{ $trabajador->dni }}</span>
                                    </div>
                                </div>
                            </td>

                            <td class="manual-cell--area" data-label="Área">{{ $trabajador->area?->nombre ?? 'Sin área' }}</td>

                            <td class="manual-cell--entrada" data-label="Entrada mañana">
                                @if($entradaManana)
                                    <strong class="manual-time">{{ $entradaManana->fecha_hora?->format('H:i:s') }}</strong>
                                @else
                                    <span class="manual-empty">--:--</span>
                                @endif
                            </td>

                            <td class="manual-cell--refrigerio" data-label="Salida refrigerio">
                                @if($salidaRefrigerio)
                                    <strong class="manual-time">{{ $salidaRefrigerio->fecha_hora?->format('H:i:s') }}</strong>
                                @else
                                    <span class="manual-empty">--:--</span>
                                @endif
                            </td>

                            <td class="manual-cell--retorno" data-label="Retorno / entrada tarde">
                                @if($retorno)
                                    <strong class="manual-time">{{ $retorno->fecha_hora?->format('H:i:s') }}</strong>
                                @else
                                    <span class="manual-empty">--:--</span>
                                @endif
                            </td>

                            <td class="manual-cell--salida" data-label="Salida final">
                                @if($salida)
                                    <strong class="manual-time">{{ $salida->fecha_hora?->format('H:i:s') }}</strong>
                                @else
                                    <span class="manual-empty">--:--</span>
                                @endif
                            </td>

                            <td class="manual-cell--estado" data-label="Estado de hoy">
                                @if($completo)
                                    <span class="badge badge--success">Completo</span>
                                @elseif($turnoReferencialRegistrado)
                                    <span class="badge badge--success">Turno registrado</span>
                                @elseif($marcas->isNotEmpty())
                                    <span class="badge badge--warning">En jornada</span>
                                @else
                                    <span class="badge badge--neutral">Sin marcar</span>
                                @endif
                            </td>

                            <td class="manual-cell--accion" data-label="Acción">
                                @if($completo)
                                    <span class="manual-complete">✓ Jornada marcada</span>
                                @else
                                    <form method="POST" action="{{ route('marcaciones.marcarAhora',$trabajador) }}"
                                        data-marcar-asistencia data-trabajador="{{ $trabajador->nombres }} {{ $trabajador->apellidos }}">
                                        @csrf

                                        <button type="submit" class="button {{ $marcas->isNotEmpty() ? 'button--secondary' : 'button--primary' }} button--small">
                                            {{ $turnoReferencialRegistrado ? 'Registrar retorno' : 'Registrar marca ahora' }}
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr class="manual-row--empty">
                            <td colspan="8">
                                <div class="empty-state">
                                    <strong>No se encontraron trabajadores</strong>
                                    <span>Modifica los filtros de búsqueda.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

</div>
@endsection

@push('scripts')
<script src="{{ asset('js/pages/marcaciones.js') }}"></script>
@endpush
