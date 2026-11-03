@extends('layouts.app')

@section('title', 'Trabajo en campo')
@section('page-title', 'Trabajo en campo')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/asignaciones-campo.css') }}">
@endpush

@section('content')
    {{-- ENCABEZADO --}}
    <div class="page-header">
        <div>
            <h2 class="page-header__title">Trabajo en campo</h2>
            <p class="page-header__description">
                @if ($esAdministrador)
                    Asigna trabajo fuera de oficina y aprueba las marcaciones remotas del personal.
                @else
                    Registra tus salidas de campo y marca tu asistencia desde la web.
                @endif
            </p>
        </div>

        <div class="page-header__actions">
            <button type="button" class="button button--primary" data-modal-open="modalCrearAsignacion">
                <x-ui-icon name="plus" /> Nueva asignación
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
                @if ($esAdministrador)
                    <label class="form-label">Área</label>
                    <select name="area_id" class="form-select">
                        <option value="">Todas</option>
                        @foreach ($areas as $area)
                            <option value="{{ $area->id }}" @selected(request('area_id') == $area->id)>
                                {{ $area->nombre }}
                            </option>
                        @endforeach
                    </select>
                @else
                    <label class="form-label">Fecha</label>
                    <input type="date" name="fecha" value="{{ request('fecha') }}" class="form-control">
                @endif
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

            @if ($esAdministrador)
                <div class="form-group">
                    <label class="form-label">Fecha</label>
                    <input type="date" name="fecha" value="{{ request('fecha') }}" class="form-control">
                </div>
            @endif

            <div class="campo-filtros__actions">
                <button type="submit" class="button button--primary"><x-ui-icon name="filter" /> Filtrar</button>
                <a href="{{ route('asignaciones-campo.index') }}" class="button button--secondary"><x-ui-icon name="reset" /> Restablecer</a>
            </div>
        </form>
    </section>

    @if ($esAdministrador)
        <section class="campo-review panel" aria-labelledby="revisionMarcacionesTitulo">
            <div class="campo-section-heading">
                <div>
                    <span>REVISIÓN ADMINISTRATIVA</span>
                    <h3 id="revisionMarcacionesTitulo">Marcaciones remotas pendientes</h3>
                    <p>Solo las marcaciones aprobadas se incorporan al cálculo de asistencia.</p>
                </div>
                <strong class="campo-counter">{{ $marcacionesPendientes->count() }}</strong>
            </div>

            <div class="campo-review-list">
                @forelse ($marcacionesPendientes as $marcacionCampo)
                    <article class="campo-review-item">
                        <div class="campo-review-item__identity">
                            <strong>{{ $marcacionCampo->trabajador?->nombres }} {{ $marcacionCampo->trabajador?->apellidos }}</strong>
                            <span>{{ $marcacionCampo->trabajador?->area?->nombre ?? 'Sin área' }} · DNI {{ $marcacionCampo->trabajador?->dni ?? '—' }}</span>
                        </div>
                        <div class="campo-review-item__mark">
                            <span class="campo-mark-type">{{ ucfirst(strtolower($marcacionCampo->tipo)) }}</span>
                            <strong>{{ $marcacionCampo->fecha_hora?->format('d/m/Y H:i') }}</strong>
                            <small>{{ $marcacionCampo->asignacion?->lugar ?: 'Lugar no especificado' }}</small>
                        </div>
                        <div class="campo-review-item__activity">
                            <small>Actividad declarada</small>
                            <p>{{ $marcacionCampo->asignacion?->actividad }}</p>
                        </div>
                        <form method="POST" action="{{ route('marcaciones-campo.revisar', $marcacionCampo) }}" class="campo-review-form">
                            @csrf
                            @method('PATCH')
                            <label class="form-group">
                                <span class="form-label">Observación (obligatoria al rechazar)</span>
                                <input type="text" name="observacion_revision" class="form-control" maxlength="500"
                                    placeholder="Motivo o comentario de la revisión">
                            </label>
                            <div>
                                <button type="submit" name="decision" value="RECHAZAR" class="button button--danger button--small">Rechazar</button>
                                <button type="submit" name="decision" value="APROBAR" class="button button--primary button--small">Aprobar</button>
                            </div>
                        </form>
                    </article>
                @empty
                    <div class="campo-review-empty">
                        <strong>No hay marcaciones por revisar</strong>
                        <span>Las nuevas marcaciones web aparecerán aquí.</span>
                    </div>
                @endforelse
            </div>
        </section>
    @else
        <section class="campo-worker-note">
            <strong>¿Cómo funciona?</strong>
            <span>Registra una asignación, marca durante el periodo indicado y espera la aprobación. Hasta que el administrador apruebe, la marcación no contará en tu asistencia.</span>
        </section>
    @endif

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
                        <span class="campo-info__label">Registrado por</span>
                        <strong>
                            {{ $asignacion->autorizadoPor?->name ?? ($asignacion->autorizadoPor?->email ?? 'Usuario del sistema') }}
                            · {{ strtoupper((string) $asignacion->autorizadoPor?->rol?->nombre) === 'TRABAJADOR' ? 'Autoasignación' : 'Administración' }}
                        </strong>
                    </div>

                    @if ($asignacion->marcacionesRemotas->isNotEmpty())
                        <div class="campo-info campo-info--full">
                            <span class="campo-info__label">Marcaciones web</span>
                            <div class="campo-card-marks">
                                @foreach ($asignacion->marcacionesRemotas as $marcacionCampo)
                                    <span class="campo-mini-mark campo-mini-mark--{{ strtolower($marcacionCampo->estado) }}">
                                        {{ ucfirst(strtolower($marcacionCampo->tipo)) }} {{ $marcacionCampo->fecha_hora?->format('d/m H:i') }}
                                        · {{ ucfirst(strtolower($marcacionCampo->estado)) }}
                                        @if ($marcacionCampo->marcacion?->anulada) · Anulada @endif
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="campo-card__footer">
                    @if ($esTrabajador && $asignacion->estado === 'ACTIVA')
                        <form method="POST" action="{{ route('asignaciones-campo.marcar', $asignacion) }}">
                            @csrf
                            <button type="submit" class="button button--primary button--small">
                                <x-ui-icon name="clock" /> Marcar ahora
                            </button>
                        </form>
                    @elseif ($esAdministrador && $asignacion->estado !== 'CANCELADA')
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
                    @elseif ($asignacion->estado === 'CANCELADA')
                        <span class="campo-card__cancelada">Esta asignación fue cancelada</span>
                    @elseif ($esTrabajador)
                        <span class="campo-card__cancelada">El marcaje web se habilita durante el periodo asignado</span>
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

    <section class="campo-history panel" aria-labelledby="historialCampoTitulo">
        <div class="campo-section-heading">
            <div>
                <span>TRAZABILIDAD</span>
                <h3 id="historialCampoTitulo">{{ $esAdministrador ? 'Historial de marcaciones remotas' : 'Mis marcaciones remotas' }}</h3>
                <p>Últimas 30 marcaciones registradas desde la web.</p>
            </div>
        </div>
        <div class="campo-history-table-wrap">
            <table class="table campo-history-table">
                <thead><tr><th>Fecha y hora</th>@if($esAdministrador)<th>Trabajador</th>@endif<th>Tipo</th><th>Actividad</th><th>Estado</th><th>Revisión</th></tr></thead>
                <tbody>
                    @forelse ($marcacionesRecientes as $marcacionCampo)
                        <tr>
                            <td>{{ $marcacionCampo->fecha_hora?->format('d/m/Y H:i') }}</td>
                            @if($esAdministrador)<td>{{ $marcacionCampo->trabajador?->nombres }} {{ $marcacionCampo->trabajador?->apellidos }}</td>@endif
                            <td>{{ ucfirst(strtolower($marcacionCampo->tipo)) }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($marcacionCampo->asignacion?->actividad, 60) }}</td>
                            <td><span class="campo-mini-mark campo-mini-mark--{{ strtolower($marcacionCampo->estado) }}">{{ ucfirst(strtolower($marcacionCampo->estado)) }}</span></td>
                            <td>
                                @if ($marcacionCampo->fecha_revision)
                                    {{ $marcacionCampo->revisadoPor?->name ?? 'Administrador' }} · {{ $marcacionCampo->fecha_revision->format('d/m H:i') }}
                                    @if ($marcacionCampo->observacion_revision)<small>{{ $marcacionCampo->observacion_revision }}</small>@endif
                                @else
                                    Pendiente
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $esAdministrador ? 6 : 5 }}">Todavía no hay marcaciones remotas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- MODAL CREAR --}}
    <x-modal id="modalCrearAsignacion" title="Nueva asignación de campo">
        <form method="POST" action="{{ route('asignaciones-campo.store') }}" class="form">
            @csrf

            @if ($esAdministrador)
                <div class="form-group">
                    <label class="form-label">Trabajador *</label>
                    <select name="trabajador_id" class="form-select" required>
                        <option value="">Seleccione</option>
                        @foreach ($trabajadores as $trabajador)
                            <option value="{{ $trabajador->id }}">
                                {{ $trabajador->apellidos }}, {{ $trabajador->nombres }}
                                - {{ $trabajador->area?->nombre ?? 'Sin área' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="form-grid form-grid--2">
                <div class="form-group">
                    <label class="form-label">Fecha de inicio *</label>
                    <input type="date" name="fecha_inicio" value="{{ $esTrabajador ? today()->toDateString() : '' }}"
                        @if($esTrabajador) min="{{ today()->toDateString() }}" @endif class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Fecha de fin *</label>
                    <input type="date" name="fecha_fin" value="{{ $esTrabajador ? today()->toDateString() : '' }}"
                        @if($esTrabajador) min="{{ today()->toDateString() }}" @endif class="form-control" required>
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

    @if ($esAdministrador)
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
    @endif

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
