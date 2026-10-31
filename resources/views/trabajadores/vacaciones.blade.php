@extends('layouts.app')

@section('title', 'Vacaciones')
@section('page-title', 'Vacaciones')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/vacaciones.css') }}">
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

    {{-- CABECERA --}}
    <div class="page-header">
        <div>
            <h2 class="page-header__title">Gestión de vacaciones</h2>
            <p class="page-header__description">
                @if ($esAdministrador)
                    Revisa, aprueba, rechaza y reprograma los periodos de vacaciones del personal.
                @else
                    Registra y consulta tus solicitudes de vacaciones.
                @endif
            </p>
        </div>

        @unless ($esAdministrador)
            <div class="page-header__actions">
                <button type="button" class="button button--primary" data-modal-open="modalCrearVacaciones">
                    <x-ui-icon name="plus" /> Solicitar vacaciones
                </button>
            </div>
        @endunless
    </div>

    {{-- FILTROS --}}
    <section class="panel">
        <form method="GET" action="{{ route('vacaciones.index') }}" class="vacaciones-filtros">
            @if ($esAdministrador)
                <div class="form-group">
                    <label class="form-label">Buscar</label>
                    <input type="text" name="buscar" value="{{ request('buscar') }}" class="form-control"
                        placeholder="Trabajador o DNI...">
                </div>
            @endif

            <div class="form-group">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <option value="">Todos</option>
                    <option value="PENDIENTE" @selected(request('estado') === 'PENDIENTE')>Pendiente</option>
                    <option value="PROGRAMADA" @selected(request('estado') === 'PROGRAMADA')>Programada</option>
                    <option value="EN_CURSO" @selected(request('estado') === 'EN_CURSO')>En curso</option>
                    <option value="GOZADA" @selected(request('estado') === 'GOZADA')>Gozada</option>
                    <option value="RECHAZADA" @selected(request('estado') === 'RECHAZADA')>Rechazada</option>
                    <option value="CANCELADA" @selected(request('estado') === 'CANCELADA')>Cancelada</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Fecha</label>
                <input type="date" name="fecha" value="{{ request('fecha') }}" class="form-control">
            </div>

            <div class="form-group">
                <label class="form-label">Año</label>
                <input type="number" name="anio" value="{{ request('anio') }}" class="form-control" min="2020"
                    max="2100" placeholder="{{ now()->year }}">
            </div>

            <div class="vacaciones-filtros__actions">
                <button type="submit" class="button button--primary"><x-ui-icon name="filter" /> Filtrar</button>
                <a href="{{ route('vacaciones.index') }}" class="button button--secondary"><x-ui-icon name="reset" /> Restablecer</a>
            </div>
        </form>
    </section>

    {{-- LISTADO --}}
    <section class="vacaciones-grid">
        @forelse($vacaciones as $vacacion)
            <article class="vacacion-card">
                {{-- CABECERA CARD --}}
                <div class="vacacion-card__header">
                    <div class="vacacion-card__persona">
                        <div class="vacacion-card__avatar">
                            {{ strtoupper(substr($vacacion->trabajador?->nombres ?? '?', 0, 1)) }}
                        </div>

                        <div>
                            @if ($esAdministrador)
                                <h3>{{ $vacacion->trabajador?->nombres }} {{ $vacacion->trabajador?->apellidos }}</h3>
                                <span>DNI: {{ $vacacion->trabajador?->dni ?? '-' }} ·
                                    {{ $vacacion->trabajador?->area?->nombre ?? 'Sin área' }}</span>
                            @else
                                <h3>Periodo de vacaciones</h3>
                                <span>Solicitud #{{ $vacacion->id }}</span>
                            @endif
                        </div>
                    </div>

                    <span
                        class="vacacion-estado vacacion-estado--{{ strtolower(str_replace('_', '-', $vacacion->estado)) }}">
                        {{ match ($vacacion->estado) {
                            'PENDIENTE' => 'Pendiente',
                            'APROBADA' => 'Aprobada',
                            'PROGRAMADA' => 'Programada',
                            'EN_CURSO' => 'En curso',
                            'GOZADA' => 'Gozada',
                            'RECHAZADA' => 'Rechazada',
                            'CANCELADA' => 'Cancelada',
                            default => $vacacion->estado,
                        } }}
                    </span>
                </div>

                {{-- CUERPO CARD --}}
                <div class="vacacion-card__body">
                    <div class="vacacion-periodo">
                        <div class="vacacion-fecha">
                            <span class="vacacion-info__label">Inicio</span>
                            <strong>{{ $vacacion->fecha_inicio?->format('d/m/Y') }}</strong>
                        </div>

                        <div class="vacacion-periodo__linea"></div>

                        <div class="vacacion-fecha">
                            <span class="vacacion-info__label">Fin</span>
                            <strong>{{ $vacacion->fecha_fin?->format('d/m/Y') }}</strong>
                        </div>
                    </div>

                    <div class="vacacion-dias">
                        <span>{{ $vacacion->dias_solicitados }}</span>
                        <small>{{ $vacacion->dias_solicitados == 1 ? 'día' : 'días' }}</small>
                    </div>

                    @if ($vacacion->observacion)
                        <div class="vacacion-info vacacion-info--full">
                            <span class="vacacion-info__label">Observación</span>
                            <p>{{ $vacacion->observacion }}</p>
                        </div>
                    @endif

                    <div class="vacacion-info">
                        <span class="vacacion-info__label">Registrada</span>
                        <strong>{{ $vacacion->created_at?->format('d/m/Y H:i') }}</strong>
                    </div>

                    @if ($vacacion->estado !== 'PENDIENTE' && $vacacion->fecha_revision)
                        <div class="vacacion-info">
                            <span class="vacacion-info__label">Revisada</span>
                            <strong>{{ $vacacion->fecha_revision?->format('d/m/Y H:i') }}</strong>
                        </div>
                    @endif
                </div>

                {{-- REVISIÓN --}}
                @if ($vacacion->revisado_por || $vacacion->comentario_revision)
                    <div class="vacacion-card__revision">
                        <div>
                            <span>Revisado por</span>
                            <strong>{{ $vacacion->revisadoPor?->name ?? ($vacacion->revisadoPor?->email ?? 'Administrador') }}</strong>
                        </div>

                        @if ($vacacion->comentario_revision)
                            <div class="vacacion-card__comentario">
                                <span>Comentario</span>
                                <p>{{ $vacacion->comentario_revision }}</p>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- HISTORIAL DE REPROGRAMACIONES --}}
                @if ($vacacion->reprogramaciones && $vacacion->reprogramaciones->count())
                    <details class="vacacion-historial">
                        <summary>
                            Historial de reprogramaciones
                            <span>{{ $vacacion->reprogramaciones->count() }}</span>
                        </summary>

                        <div class="vacacion-historial__contenido">
                            @foreach ($vacacion->reprogramaciones as $reprogramacion)
                                <div class="vacacion-reprogramacion">
                                    <div>
                                        <span>Periodo anterior</span>
                                        <strong>{{ $reprogramacion->fecha_inicio_anterior?->format('d/m/Y') }} -
                                            {{ $reprogramacion->fecha_fin_anterior?->format('d/m/Y') }}</strong>
                                    </div>

                                    <div>
                                        <span>Nuevo periodo</span>
                                        <strong>{{ $reprogramacion->fecha_inicio_nueva?->format('d/m/Y') }} -
                                            {{ $reprogramacion->fecha_fin_nueva?->format('d/m/Y') }}</strong>
                                    </div>

                                    <div class="vacacion-reprogramacion__motivo">
                                        <span>Motivo</span>
                                        <p>{{ $reprogramacion->motivo }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </details>
                @endif

                {{-- ACCIONES --}}
                <div class="vacacion-card__footer">
                    @if ($esAdministrador && $vacacion->estado === 'PENDIENTE')
                        <button type="button" class="button button--primary button--small" data-revisar-vacacion
                            data-url="{{ route('vacaciones.revisar', $vacacion) }}"
                            data-trabajador="{{ $vacacion->trabajador?->nombres }} {{ $vacacion->trabajador?->apellidos }}"
                            data-periodo="{{ $vacacion->fecha_inicio?->format('d/m/Y') }} - {{ $vacacion->fecha_fin?->format('d/m/Y') }}">
                            Revisar
                        </button>
                    @endif

                    @if ($esAdministrador && in_array($vacacion->estado, ['APROBADA', 'PROGRAMADA']))
                        <button type="button" class="button button--secondary button--small" data-reprogramar-vacacion
                            data-url="{{ route('vacaciones.reprogramar', $vacacion) }}"
                            data-trabajador="{{ $vacacion->trabajador?->nombres }} {{ $vacacion->trabajador?->apellidos }}"
                            data-inicio="{{ $vacacion->fecha_inicio?->format('Y-m-d') }}"
                            data-fin="{{ $vacacion->fecha_fin?->format('Y-m-d') }}">
                            Reprogramar
                        </button>
                    @endif

                    @if ($esAdministrador && in_array($vacacion->estado, ['PENDIENTE', 'APROBADA', 'PROGRAMADA']))
                        <button type="button" class="button button--danger button--small" data-cancelar-vacacion
                            data-url="{{ route('vacaciones.cancelar', $vacacion) }}"
                            data-periodo="{{ $vacacion->fecha_inicio?->format('d/m/Y') }} - {{ $vacacion->fecha_fin?->format('d/m/Y') }}">
                            Cancelar
                        </button>
                    @endif

                    @if (!$esAdministrador && $vacacion->estado === 'PENDIENTE')
                        <button type="button" class="button button--secondary button--small" data-editar-vacacion
                            data-url="{{ route('vacaciones.update', $vacacion) }}"
                            data-inicio="{{ $vacacion->fecha_inicio?->format('Y-m-d') }}"
                            data-fin="{{ $vacacion->fecha_fin?->format('Y-m-d') }}"
                            data-observacion="{{ $vacacion->observacion }}">
                            Editar
                        </button>

                        <button type="button" class="button button--danger button--small" data-cancelar-vacacion
                            data-url="{{ route('vacaciones.cancelar', $vacacion) }}"
                            data-periodo="{{ $vacacion->fecha_inicio?->format('d/m/Y') }} - {{ $vacacion->fecha_fin?->format('d/m/Y') }}">
                            Cancelar
                        </button>
                    @endif
                </div>
            </article>
        @empty
            <div class="empty-state">
                <strong>No existen vacaciones registradas</strong>
                <span>
                    @if ($esAdministrador)
                        No existen solicitudes para los filtros seleccionados.
                    @else
                        Aún no has registrado solicitudes de vacaciones.
                    @endif
                </span>
            </div>
        @endforelse
    </section>

    <div class="pagination-container">
        {{ $vacaciones->links() }}
    </div>

    {{-- MODAL CREAR --}}
    @unless ($esAdministrador)
        <x-modal id="modalCrearVacaciones" title="Solicitar vacaciones">
            <form method="POST" action="{{ route('vacaciones.store') }}" class="form">
                @csrf
                <div class="vacaciones-form-info">
                    <strong>Selecciona tu periodo de vacaciones</strong>
                    <span>La solicitud será enviada al administrador para su revisión.</span>
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

                <div class="vacaciones-calculo">
                    <span>Días seleccionados</span>
                    <strong id="crearVacacionesDias">—</strong>
                </div>

                <div class="form-group">
                    <label class="form-label">Observación</label>
                    <textarea name="observacion" class="form-textarea" rows="4" maxlength="500"
                        placeholder="Puedes indicar alguna observación adicional..."></textarea>
                </div>

                <div class="modal__actions">
                    <button type="button" class="button button--secondary" data-modal-close>Cancelar</button>
                    <button type="submit" class="button button--primary">Enviar solicitud</button>
                </div>
            </form>
        </x-modal>

        {{-- MODAL EDITAR --}}
        <x-modal id="modalEditarVacaciones" title="Editar solicitud de vacaciones">
            <form method="POST" id="formEditarVacaciones" class="form">
                @csrf
                @method('PUT')

                <div class="form-grid form-grid--2">
                    <div class="form-group">
                        <label class="form-label">Fecha de inicio *</label>
                        <input type="date" name="fecha_inicio" id="editarVacacionesInicio" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Fecha de fin *</label>
                        <input type="date" name="fecha_fin" id="editarVacacionesFin" class="form-control" required>
                    </div>
                </div>

                <div class="vacaciones-calculo">
                    <span>Días seleccionados</span>
                    <strong id="editarVacacionesDias">—</strong>
                </div>

                <div class="form-group">
                    <label class="form-label">Observación</label>
                    <textarea name="observacion" id="editarVacacionesObservacion" class="form-textarea" rows="4" maxlength="500"></textarea>
                </div>

                <div class="modal__actions">
                    <button type="button" class="button button--secondary" data-modal-close>Cancelar</button>
                    <button type="submit" class="button button--primary">Guardar cambios</button>
                </div>
            </form>
        </x-modal>
    @endunless

    {{-- MODAL REVISAR - ADMIN --}}
    @if ($esAdministrador)
        <x-modal id="modalRevisarVacaciones" title="Revisar vacaciones">
            <form method="POST" id="formRevisarVacaciones" class="form">
                @csrf
                @method('PATCH')

                <div class="vacaciones-revision-resumen">
                    <span>Trabajador</span>
                    <strong id="revisionVacacionesTrabajador">-</strong>
                    <small id="revisionVacacionesPeriodo">-</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Decisión *</label>
                    <select name="decision" id="revisionVacacionesDecision" class="form-select" required>
                        <option value="">Seleccione</option>
                        <option value="APROBADA">Aprobar vacaciones</option>
                        <option value="RECHAZADA">Rechazar vacaciones</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Comentario de revisión</label>
                    <textarea name="comentario_revision" class="form-textarea" rows="4" maxlength="500"
                        placeholder="Indique observaciones o motivo del rechazo..."></textarea>
                    <small class="form-help">Al rechazar, el comentario es obligatorio.</small>
                </div>

                <div class="modal__actions">
                    <button type="button" class="button button--secondary" data-modal-close>Cancelar</button>
                    <button type="submit" class="button button--primary">Guardar decisión</button>
                </div>
            </form>
        </x-modal>

        {{-- MODAL REPROGRAMAR --}}
        <x-modal id="modalReprogramarVacaciones" title="Reprogramar vacaciones">
            <form method="POST" id="formReprogramarVacaciones" class="form">
                @csrf
                @method('PATCH')

                <div class="vacaciones-revision-resumen">
                    <span>Trabajador</span>
                    <strong id="reprogramarVacacionesTrabajador">-</strong>
                    <small>Se conservará el periodo anterior en el historial.</small>
                </div>

                <div class="form-grid form-grid--2">
                    <div class="form-group">
                        <label class="form-label">Nueva fecha de inicio *</label>
                        <input type="date" name="fecha_inicio_nueva" id="reprogramarVacacionesInicio"
                            class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nueva fecha de fin *</label>
                        <input type="date" name="fecha_fin_nueva" id="reprogramarVacacionesFin" class="form-control"
                            required>
                    </div>
                </div>

                <div class="vacaciones-calculo">
                    <span>Nuevos días</span>
                    <strong id="reprogramarVacacionesDias">—</strong>
                </div>

                <div class="form-group">
                    <label class="form-label">Motivo de reprogramación *</label>
                    <textarea name="motivo" class="form-textarea" rows="4" maxlength="2000" required
                        placeholder="Indica por qué se modifica el periodo..."></textarea>
                </div>

                <div class="modal__actions">
                    <button type="button" class="button button--secondary" data-modal-close>Cancelar</button>
                    <button type="submit" class="button button--primary">Reprogramar</button>
                </div>
            </form>
        </x-modal>
    @endif

    {{-- MODAL CANCELAR --}}
    <x-modal id="modalCancelarVacaciones" title="Cancelar vacaciones" size="small">
        <form method="POST" id="formCancelarVacaciones">
            @csrf
            @method('PATCH')

            <p>¿Seguro que deseas cancelar el periodo <strong id="cancelarVacacionesPeriodo"></strong>?</p>

            <div class="modal__actions">
                <button type="button" class="button button--secondary" data-modal-close>Volver</button>
                <button type="submit" class="button button--danger">Cancelar vacaciones</button>
            </div>
        </form>
    </x-modal>

@endsection

@push('scripts')
    <script src="{{ asset('js/pages/vacaciones.js') }}"></script>
@endpush
