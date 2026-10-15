@extends('layouts.app')

@section('title','Marcación manual')
@section('page-title','Marcación manual')
@section('page-subtitle','Registro rápido de entrada y salida del personal')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/marcaciones.css') }}">
@endpush

@section('content')
<div class="marcaciones-page">

    <header class="marcaciones-header">
        <div>
            <a href="{{ route('marcaciones.index') }}" class="marcaciones-back">← Volver a marcaciones</a>
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
                <h3>Trabajadores</h3>
                <p>Marca la entrada o salida según el estado actual del trabajador.</p>
            </div>

            <button type="button" class="button button--secondary" id="toggleRegistroEspecial">
                Registro especial
            </button>
        </div>

        <form method="GET" action="{{ route('marcaciones.manual') }}" class="manual-filters">
            <input type="search" name="buscar" value="{{ request('buscar') }}" class="form-control"
                placeholder="Buscar por nombre o DNI...">

            <select name="area_id" class="form-select">
                <option value="">Todas las áreas</option>
                @foreach($areas as $area)
                    <option value="{{ $area->id }}" @selected(request('area_id')==$area->id)>{{ $area->nombre }}</option>
                @endforeach
            </select>

            <button type="submit" class="button button--primary">Buscar</button>
            <a href="{{ route('marcaciones.manual') }}" class="button button--secondary">Limpiar</a>
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
                    <select name="trabajador_id" class="form-select" required>
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
                    <input type="datetime-local" name="fecha_hora" value="{{ old('fecha_hora') }}" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Tipo *</label>
                    <select name="tipo" class="form-select" required>
                        <option value="">Seleccionar</option>
                        <option value="ENTRADA">Entrada</option>
                        <option value="SALIDA">Salida</option>
                    </select>
                </div>

                <div class="form-group registro-especial__motivo">
                    <label class="form-label">Motivo *</label>
                    <textarea name="motivo_manual" class="form-textarea" rows="3" maxlength="500"
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
                        <th>Trabajador</th>
                        <th>Área</th>
                        <th>Entrada</th>
                        <th>Salida</th>
                        <th>Estado de hoy</th>
                        <th>Acción</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($trabajadores as $trabajador)
                        @php
                            $marcas = $marcacionesHoy->get($trabajador->id,collect());
                            $entrada = $marcas->firstWhere('tipo','ENTRADA');
                            $salida = $marcas->firstWhere('tipo','SALIDA');
                            $completo = $entrada && $salida;
                        @endphp

                        <tr>
                            <td>
                                <div class="trabajador-info">
                                    <div class="trabajador-info__avatar">{{ mb_strtoupper(mb_substr($trabajador->nombres ?? 'T',0,1)) }}</div>
                                    <div>
                                        <strong>{{ $trabajador->nombres }} {{ $trabajador->apellidos }}</strong>
                                        <span>DNI: {{ $trabajador->dni }}</span>
                                    </div>
                                </div>
                            </td>

                            <td>{{ $trabajador->area?->nombre ?? 'Sin área' }}</td>

                            <td>
                                @if($entrada)
                                    <strong class="manual-time">{{ $entrada->fecha_hora?->format('H:i:s') }}</strong>
                                @else
                                    <span class="manual-empty">--:--</span>
                                @endif
                            </td>

                            <td>
                                @if($salida)
                                    <strong class="manual-time">{{ $salida->fecha_hora?->format('H:i:s') }}</strong>
                                @else
                                    <span class="manual-empty">--:--</span>
                                @endif
                            </td>

                            <td>
                                @if($completo)
                                    <span class="badge badge--success">Completo</span>
                                @elseif($entrada)
                                    <span class="badge badge--warning">En jornada</span>
                                @else
                                    <span class="badge badge--neutral">Sin marcar</span>
                                @endif
                            </td>

                            <td>
                                @if($completo)
                                    <span class="manual-complete">✓ Jornada marcada</span>
                                @else
                                    <form method="POST" action="{{ route('marcaciones.marcarAhora',$trabajador) }}"
                                        data-marcar-asistencia data-trabajador="{{ $trabajador->nombres }} {{ $trabajador->apellidos }}"
                                        data-tipo="{{ $entrada ? 'SALIDA' : 'ENTRADA' }}">
                                        @csrf

                                        <button type="submit" class="button {{ $entrada ? 'button--secondary' : 'button--primary' }} button--small">
                                            {{ $entrada ? 'Marcar salida' : 'Marcar entrada' }}
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
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