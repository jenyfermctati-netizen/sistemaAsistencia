@extends('layouts.app')

@section('title','Marcaciones')
@section('page-title','Marcaciones')
@section('page-subtitle','Registro y consulta de marcaciones del personal')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/marcaciones.css') }}">
@endpush

@section('content')
<div class="marcaciones-page">

    <header class="marcaciones-header">
        <div>
            <h2>Marcaciones</h2>
            <p>Consulta los registros del biométrico y las marcaciones realizadas manualmente.</p>
        </div>

        <a href="{{ route('marcaciones.manual') }}" class="button button--primary">
            + Registrar asistencia manual
        </a>
    </header>

    @if(session('success'))
        <div class="marcaciones-alert marcaciones-alert--success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="marcaciones-alert marcaciones-alert--error">{{ session('error') }}</div>
    @endif

    <div data-live-region="marcaciones" data-live-poll="5000">
        <section class="marcaciones-panel">

            <form method="GET" action="{{ route('marcaciones.index') }}" class="marcaciones-filtros">
                <input type="text" name="buscar" value="{{ request('buscar') }}" class="form-control" placeholder="DNI, trabajador o biométrico...">
                <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}" class="form-control">
                <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}" class="form-control">

                <select name="tipo" class="form-select">
                    <option value="">Todos los tipos</option>
                    <option value="ENTRADA" @selected(request('tipo')==='ENTRADA')>Entrada</option>
                    <option value="SALIDA" @selected(request('tipo')==='SALIDA')>Salida</option>
                </select>

                <select name="origen" class="form-select">
                    <option value="">Todos los orígenes</option>
                    <option value="BIOMETRICO" @selected(request('origen')==='BIOMETRICO')>Biométrico</option>
                    <option value="MANUAL" @selected(request('origen')==='MANUAL')>Manual</option>
                </select>

                <select name="estado" class="form-select">
                    <option value="">Todos los estados</option>
                    <option value="ACTIVA" @selected(request('estado')==='ACTIVA')>Activas</option>
                    <option value="ANULADA" @selected(request('estado')==='ANULADA')>Anuladas</option>
                </select>

                <div class="marcaciones-filtros__actions">
                    <button type="submit" class="button button--primary">Filtrar</button>
                    <a href="{{ route('marcaciones.index') }}" class="button button--secondary">Limpiar</a>
                </div>
            </form>

            <div class="table-container">
                <table class="marcaciones-table">
                    <thead>
                        <tr>
                            <th>Trabajador</th>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Tipo</th>
                            <th>Origen</th>
                            <th>Dispositivo / Motivo</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($marcaciones as $marcacion)
                            <tr class="{{ $marcacion->anulada ? 'row-anulada' : '' }}">
                                <td>
                                    <div class="trabajador-info">
                                        <div class="trabajador-info__avatar">
                                            {{ mb_strtoupper(mb_substr($marcacion->trabajador?->nombres ?? 'T',0,1)) }}
                                        </div>
                                        <div>
                                            <strong>{{ $marcacion->trabajador?->nombres }} {{ $marcacion->trabajador?->apellidos }}</strong>
                                            <span>DNI: {{ $marcacion->trabajador?->dni ?? '-' }}</span>
                                            @if($marcacion->trabajador?->area)
                                                <small>{{ $marcacion->trabajador->area->nombre }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <td>{{ $marcacion->fecha_hora?->format('d/m/Y') }}</td>
                                <td><strong class="marcacion-hora">{{ $marcacion->fecha_hora?->format('H:i:s') }}</strong></td>

                                <td>
                                    <span class="badge {{ $marcacion->tipo==='ENTRADA' ? 'badge--success' : 'badge--info' }}">
                                        {{ ucfirst(strtolower($marcacion->tipo)) }}
                                    </span>
                                </td>

                                <td>
                                    <span class="badge {{ $marcacion->origen==='BIOMETRICO' ? 'badge--primary' : 'badge--warning' }}">
                                        {{ $marcacion->origen==='BIOMETRICO' ? 'Biométrico' : 'Manual' }}
                                    </span>
                                </td>

                                <td>
                                    @if($marcacion->origen==='BIOMETRICO')
                                        <strong>{{ $marcacion->dispositivo ?? 'Biométrico' }}</strong>
                                        @if($marcacion->evento_externo_id)
                                            <span class="table-secondary-text">Evento: {{ $marcacion->evento_externo_id }}</span>
                                        @endif
                                    @else
                                        <span>{{ $marcacion->motivo_manual ?? '-' }}</span>
                                        @if($marcacion->registradoPor)
                                            <span class="table-secondary-text">Registrado por: {{ $marcacion->registradoPor->name }}</span>
                                        @endif
                                    @endif
                                </td>

                                <td>
                                    @if(!$marcacion->anulada)
                                        <span class="badge badge--success">Activa</span>
                                    @else
                                        <span class="badge badge--danger">Anulada</span>
                                        @if($marcacion->motivo_anulacion)
                                            <span class="table-secondary-text">{{ $marcacion->motivo_anulacion }}</span>
                                        @endif
                                    @endif
                                </td>

                                <td>
                                    @if(!$marcacion->anulada)
                                        <button type="button" class="button button--danger button--small"
                                            data-anular-marcacion
                                            data-url="{{ route('marcaciones.anular',$marcacion) }}"
                                            data-trabajador="{{ $marcacion->trabajador?->nombres }} {{ $marcacion->trabajador?->apellidos }}"
                                            data-fecha="{{ $marcacion->fecha_hora?->format('d/m/Y H:i') }}">
                                            Anular
                                        </button>
                                    @else
                                        <span class="table-secondary-text">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="table-empty">
                                    <div class="empty-state">
                                        <strong>No se encontraron marcaciones</strong>
                                        <span>Prueba modificando los filtros.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="pagination-container">
            {{ $marcaciones->onEachSide(1)->links('components.pagination') }}
        </div>
    </div>

    <x-modal id="modalAnularMarcacion" title="Anular marcación" size="small">
        <form method="POST" id="formAnularMarcacion" class="form" data-live-form data-live-target="marcaciones">
            @csrf
            @method('PATCH')

            <div id="anularMarcacionInfo" class="anulacion-info"></div>

            <div class="form-group">
                <label class="form-label">Motivo de anulación *</label>
                <textarea name="motivo_anulacion" class="form-textarea" rows="4" maxlength="500"
                    placeholder="Indica por qué se anula esta marcación..." required></textarea>
            </div>

            <div class="modal__actions">
                <button type="button" class="button button--secondary" data-modal-close>Cancelar</button>
                <button type="submit" class="button button--danger">Anular marcación</button>
            </div>
        </form>
    </x-modal>

</div>
@endsection

@push('scripts')
<script src="{{ asset('js/pages/marcaciones.js') }}"></script>
@endpush