@extends('layouts.app')

@section('title', 'Trabajo en campo')
@section('page-title', 'Trabajo en campo')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/asignaciones-campo.css') }}">
@endpush

@section('content')

    {{-- MENSAJES --}}
    @if (session('success'))
        <div class="alert alert--success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert--danger">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert--danger">
            <strong>Revisa la información ingresada.</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ENCABEZADO --}}
    <div class="page-header">
        <div>
            <h2 class="page-header__title">Trabajo en campo</h2>
            <p class="page-header__description">
                Registra y controla las asignaciones de personal fuera de oficina.
            </p>
        </div>

        <div class="page-header__actions">
            <button type="button" class="button button--primary" data-modal-open="modalCrearAsignacion">
                + Nueva asignación
            </button>
        </div>
    </div>

    {{-- FILTROS --}}
    <section class="panel">
        <form method="GET" action="{{ route('asignaciones-campo.index') }}" class="campo-filtros">
            <div class="form-group">
                <label class="form-label">Buscar</label>
                <input type="text" name="buscar" value="{{ request('buscar') }}" class="form-control"
                    placeholder="Trabajador, DNI, actividad o lugar...">
            </div>

            <div class="form-group">
                <label class="form-label">Área</label>
                <select name="area_id" class="form-select">
                    <option value="">Todas</option>
                    @foreach ($areas as $area)
                        <option value="{{ $area->id }}" @selected(request('area_id') == $area->id)>
                            {{ $area->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <option value="">Todos</option>
                    <option value="PROGRAMADA" @selected(request('estado') === 'PROGRAMADA')>Programada</option>
                    <option value="ACTIVA" @selected(request('estado') === 'ACTIVA')>Activa</option>
                    <option value="FINALIZADA" @selected(request('estado') === 'FINALIZADA')>Finalizada</option>
                    <option value="CANCELADA" @selected(request('estado') === 'CANCELADA')>Cancelada</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Fecha</label>
                <input type="date" name="fecha" value="{{ request('fecha') }}" class="form-control">
            </div>

            <div class="campo-filtros__actions">
                <button type="submit" class="button button--secondary">Filtrar</button>
                <a href="{{ route('asignaciones-campo.index') }}" class="button button--secondary">Limpiar</a>
            </div>
        </form>
    </section>

    {{-- LISTADO --}}
    <section class="campo-grid">
        @forelse($asignaciones as $asignacion)
            <article class="campo-card">
                <div class="campo-card__header">
                    <div class="campo-card__trabajador">
                        <div class="campo-card__avatar">
                            {{ strtoupper(substr($asignacion->trabajador?->nombres ?? '?', 0, 1)) }}
                        </div>

                        <div>
                            <h3>
                                {{ $asignacion->trabajador?->nombres }}
                                {{ $asignacion->trabajador?->apellidos }}
                            </h3>
                            <span>DNI: {{ $asignacion->trabajador?->dni ?? '-' }}</span>
                        </div>
                    </div>

                    <span class="campo-estado campo-estado--{{ strtolower($asignacion->estado) }}">
                        {{ ucfirst(strtolower($asignacion->estado)) }}
                    </span>
                </div>

                <div class="campo-card__body">
                    <div class="campo-info">
                        <span class="campo-info__label">Área</span>
                        <strong>{{ $asignacion->trabajador?->area?->nombre ?? 'Sin área' }}</strong>
                    </div>

                    <div class="campo-info">
                        <span class="campo-info__label">Periodo</span>
                        <strong>
                            {{ $asignacion->fecha_inicio?->format('d/m/Y') }}
                            -
                            {{ $asignacion->fecha_fin?->format('d/m/Y') }}
                        </strong>
                    </div>

                    <div class="campo-info">
                        <span class="campo-info__label">Lugar</span>
                        <strong>{{ $asignacion->lugar ?: 'No especificado' }}</strong>
                    </div>

                    <div class="campo-info campo-info--full">
                        <span class="campo-info__label">Actividad</span>
                        <p>{{ $asignacion->actividad }}</p>
                    </div>

                    @if ($asignacion->observacion)
                        <div class="campo-info campo-info--full">
                            <span class="campo-info__label">Observación</span>
                            <p>{{ $asignacion->observacion }}</p>
                        </div>
                    @endif

                    <div class="campo-info campo-info--full">
                        <span class="campo-info__label">Autorizado por</span>
                        <strong>
                            {{ $asignacion->autorizadoPor?->name ?? ($asignacion->autorizadoPor?->email ?? 'Usuario del sistema') }}
                        </strong>
                    </div>
                </div>

                <div class="campo-card__footer">
                    @if ($asignacion->estado !== 'CANCELADA')
                        <button type="button" class="button button--secondary button--small" data-editar-asignacion
                            data-url="{{ route('asignaciones-campo.update', $asignacion) }}"
                            data-trabajador="{{ $asignacion->trabajador_id }}"
                            data-inicio="{{ $asignacion->fecha_inicio?->format('Y-m-d') }}"
                            data-fin="{{ $asignacion->fecha_fin?->format('Y-m-d') }}"
                            data-lugar="{{ $asignacion->lugar }}" data-actividad="{{ $asignacion->actividad }}"
                            data-observacion="{{ $asignacion->observacion }}">
                            Editar
                        </button>

                        <button type="button" class="button button--danger button--small" data-cancelar-asignacion
                            data-url="{{ route('asignaciones-campo.cancelar', $asignacion) }}"
                            data-trabajador="{{ $asignacion->trabajador?->nombres }} {{ $asignacion->trabajador?->apellidos }}">
                            Cancelar
                        </button>
                    @else
                        <span class="campo-card__cancelada">Esta asignación fue cancelada</span>
                    @endif
                </div>
            </article>
        @empty
            <div class="empty-state">
                <strong>No existen asignaciones de campo</strong>
                <span>Registra la primera asignación para comenzar.</span>
            </div>
        @endforelse
    </section>

    <div class="pagination-container">
        {{ $asignaciones->links() }}
    </div>

    {{-- MODAL CREAR --}}
    <x-modal id="modalCrearAsignacion" title="Nueva asignación de campo">
        <form method="POST" action="{{ route('asignaciones-campo.store') }}" class="form">
            @csrf

            <div class="form-group">
                <label class="form-label">Trabajador *</label>
                <select name="trabajador_id" class="form-select" required>
                    <option value="">Seleccione</option>
                    @foreach ($trabajadores as $trabajador)
                        <option value="{{ $trabajador->id }}">
                            {{ $trabajador->apellidos }}, {{ $trabajador->nombres }}
                            -
                            {{ $trabajador->area?->nombre ?? 'Sin área' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-grid form-grid--2">
                <div class="form-group">
                    <label class="form-label">Fecha de inicio *</label>
                    <input type="date" name="fecha_inicio" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Fecha de fin *</label>
                    <input type="date" name="fecha_fin" class="form-control" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Lugar</label>
                <input type="text" name="lugar" class="form-control" maxlength="250"
                    placeholder="Ej. Almacén del cliente, obra, proveedor...">
            </div>

            <div class="form-group">
                <label class="form-label">Actividad *</label>
                <textarea name="actividad" class="form-textarea" rows="4" required
                    placeholder="Describe la actividad que realizará..."></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Observación</label>
                <textarea name="observacion" class="form-textarea" rows="3" maxlength="500"></textarea>
            </div>

            <div class="modal__actions">
                <button type="button" class="button button--secondary" data-modal-close>Cancelar</button>
                <button type="submit" class="button button--primary">Registrar asignación</button>
            </div>
        </form>
    </x-modal>

    {{-- MODAL EDITAR --}}
    <x-modal id="modalEditarAsignacion" title="Editar asignación de campo">
        <form method="POST" id="formEditarAsignacion" class="form">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label class="form-label">Trabajador *</label>
                <select name="trabajador_id" id="editarAsignacionTrabajador" class="form-select" required>
                    @foreach ($trabajadores as $trabajador)
                        <option value="{{ $trabajador->id }}">
                            {{ $trabajador->apellidos }}, {{ $trabajador->nombres }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-grid form-grid--2">
                <div class="form-group">
                    <label class="form-label">Fecha de inicio *</label>
                    <input type="date" name="fecha_inicio" id="editarAsignacionInicio" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Fecha de fin *</label>
                    <input type="date" name="fecha_fin" id="editarAsignacionFin" class="form-control" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Lugar</label>
                <input type="text" name="lugar" id="editarAsignacionLugar" class="form-control" maxlength="250">
            </div>

            <div class="form-group">
                <label class="form-label">Actividad *</label>
                <textarea name="actividad" id="editarAsignacionActividad" class="form-textarea" rows="4" required></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Observación</label>
                <textarea name="observacion" id="editarAsignacionObservacion" class="form-textarea" rows="3" maxlength="500"></textarea>
            </div>

            <div class="modal__actions">
                <button type="button" class="button button--secondary" data-modal-close>Cancelar</button>
                <button type="submit" class="button button--primary">Guardar cambios</button>
            </div>
        </form>
    </x-modal>

    {{-- MODAL CANCELAR --}}
    <x-modal id="modalCancelarAsignacion" title="Cancelar asignación" size="small">
        <form method="POST" id="formCancelarAsignacion" class="form">
            @csrf
            @method('PATCH')

            <p id="cancelarAsignacionMensaje"></p>

            <div class="form-group">
                <label class="form-label">Motivo de cancelación *</label>
                <textarea name="motivo_cancelacion" class="form-textarea" maxlength="500" rows="3" required></textarea>
            </div>

            <div class="modal__actions">
                <button type="button" class="button button--secondary" data-modal-close>Volver</button>
                <button type="submit" class="button button--danger">Cancelar asignación</button>
            </div>
        </form>
    </x-modal>

@endsection

@push('scripts')
    <script src="{{ asset('js/pages/asignaciones-campo.js') }}"></script>
@endpush
