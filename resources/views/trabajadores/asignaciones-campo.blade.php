@extends('layouts.app')

@section('title', 'Trabajo en campo')
@section('page-title', 'Trabajo en campo')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/asignaciones-campo.css') }}">
    @if($esTrabajador)
        <link rel="stylesheet" href="{{ asset('css/pages/campo-trabajador.css') }}">
    @endif
@endpush

@section('content')
    <div class="campo-page">
    {{-- Encabezado moderno: la revisión es prioritaria para administración. --}}
    <header class="campo-topbar">
        <div class="campo-topbar__copy">
            <span class="campo-kicker">{{ $esAdministrador ? 'REVISIÓN ADMINISTRATIVA' : 'CONTROL DE ASISTENCIA' }}</span>
            <h2>{{ $esAdministrador ? 'Marcaciones remotas pendientes' : 'Trabajo en campo' }}</h2>
            <p>{{ $esAdministrador ? 'Revisa y valida las marcaciones registradas desde la web (trabajo en campo).' : 'Registra tus salidas de campo y marca tu asistencia desde la web.' }}</p>
        </div>
        <div class="campo-topbar__tools">
            @if($esAdministrador)
                <span class="campo-topbar__count"><x-ui-icon name="clock" /> {{ $marcacionesPendientes->count() }} pendientes</span>
            @endif
            <button type="button" class="button button--primary" data-modal-open="modalCrearAsignacion"><x-ui-icon name="plus" /> Nueva asignación</button>
        </div>
    </header>

    @if($esAdministrador)
        <nav class="campo-tabs" aria-label="Navegación de trabajo en campo" role="tablist">
            <a class="campo-tabs__tab campo-tabs__tab--active" href="#campoPendientes" data-campo-tab="pendientes" role="tab" aria-selected="true"><x-ui-icon name="clock" /> Pendientes ({{ $marcacionesPendientes->count() }})</a>
            <a class="campo-tabs__tab" href="#campoHistorial" data-campo-tab="historial" role="tab" aria-selected="false"><x-ui-icon name="calendar" /> Historial</a>
            <a class="campo-tabs__tab" href="#campoAsignaciones" data-campo-tab="asignaciones" role="tab" aria-selected="false"><x-ui-icon name="briefcase" /> Asignaciones</a>
        </nav>
    @endif

    @if($esTrabajador)
        <nav class="campo-tabs worker-campo-tabs" aria-label="Mis secciones de trabajo en campo">
            <a class="campo-tabs__tab campo-tabs__tab--active" href="{{ route('asignaciones-campo.index', ['vista' => 'asignaciones']) }}" data-campo-tab="asignaciones" aria-controls="campoAsignaciones"><x-ui-icon name="briefcase" /> Asignaciones</a>
            <a class="campo-tabs__tab" href="{{ route('asignaciones-campo.index', ['vista' => 'avances']) }}" data-campo-tab="avances" aria-controls="campoAvances"><x-ui-icon name="clock" /> Marcaciones pendientes</a>
            <a class="campo-tabs__tab" href="{{ route('asignaciones-campo.index', ['vista' => 'historial']) }}" data-campo-tab="historial" aria-controls="campoHistorial"><x-ui-icon name="calendar" /> Marcaciones aprobadas</a>
        </nav>
        @include('trabajadores.partials.avances-campo')
    @endif

    {{-- REVISIÓN ADMINISTRATIVA: resumen compacto y detalles bajo demanda --}}
    @if ($esAdministrador)
        <section id="campoPendientes" data-campo-panel="pendientes" role="tabpanel" class="campo-review panel" aria-labelledby="revisionMarcacionesTitulo">
            <div class="campo-review__subheading"><strong>Marcaciones por revisar</strong><span>Solo las aprobadas se incorporan al cálculo de asistencia.</span></div>

            <div class="campo-review-list">
                @forelse ($marcacionesPendientes as $marcacionCampo)
                    <article class="campo-review-item">
                        <div class="campo-review-item__identity">
                            <span class="campo-review-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($marcacionCampo->trabajador?->nombres ?? '?', 0, 1)) }}</span>
                            <div>
                                <strong>{{ $marcacionCampo->trabajador?->nombres }} {{ $marcacionCampo->trabajador?->apellidos }}</strong>
                                <span>{{ $marcacionCampo->fecha_hora?->format('d/m/Y H:i') }} · {{ ucfirst(strtolower($marcacionCampo->tipo)) }}</span>
                            </div>
                        </div>
                        <p class="campo-review-item__summary">{{ $marcacionCampo->asignacion?->actividad ?: 'Sin actividad declarada' }}</p>
                        <span class="campo-review-status">Pendiente</span>
                        <div class="campo-review-item__actions">
                            <button type="submit" form="campo-revision-{{ $marcacionCampo->getKey() }}" name="decision" value="APROBAR" class="button button--primary button--small">✓ Aprobar</button>
                        </div>
                        <div class="campo-review-item__details">
                            <button type="button" class="campo-details-trigger" data-campo-modal-target="campo-detalle-pendiente-{{ $marcacionCampo->getKey() }}" data-campo-modal-title="Detalle de marcación pendiente">Ver detalle <span aria-hidden="true">⌄</span></button>
                            <div id="campo-detalle-pendiente-{{ $marcacionCampo->getKey() }}" class="campo-modal-source" hidden>
                            <div class="campo-review-item__detail-body">
                                <dl class="campo-review-metadata">
                                    <div><dt>Área</dt><dd>{{ $marcacionCampo->trabajador?->area?->nombre ?? 'Sin área' }}</dd></div>
                                    <div><dt>DNI</dt><dd>{{ $marcacionCampo->trabajador?->dni ?? '—' }}</dd></div>
                                    <div><dt>Lugar</dt><dd>{{ $marcacionCampo->asignacion?->lugar ?: 'No especificado' }}</dd></div>
                                    <div><dt>Tipo de marcación</dt><dd>{{ ucfirst(strtolower($marcacionCampo->tipo)) }}</dd></div>
                                    <div class="campo-review-metadata__wide"><dt>Actividad</dt><dd>{{ $marcacionCampo->asignacion?->actividad ?: 'Sin actividad declarada' }}</dd></div>
                                </dl>
                                <div class="campo-review-item__evidence">
                                    @if ($marcacionCampo->evidencia_path)
                                        <a href="{{ route('marcaciones-campo.evidencia', $marcacionCampo) }}" data-campo-photo aria-label="Abrir evidencia fotográfica">
                                            <img src="{{ route('marcaciones-campo.evidencia', $marcacionCampo) }}" alt="Evidencia de {{ $marcacionCampo->trabajador?->nombres }}" loading="lazy">
                                            <span>Ver foto completa</span>
                                        </a>
                                    @else
                                        <span class="campo-no-evidence">Sin evidencia fotográfica</span>
                                    @endif
                                </div>
                                <form id="campo-revision-{{ $marcacionCampo->getKey() }}" method="POST" action="{{ route('marcaciones-campo.revisar', $marcacionCampo) }}" class="campo-review-form">
                                    @csrf
                                    @method('PATCH')
                                    <label class="form-group">
                                        <span class="form-label">Observación (obligatoria al rechazar)</span>
                                        <input type="text" name="observacion_revision" class="form-control" maxlength="500" placeholder="Motivo o comentario de la revisión">
                                    </label>
                                    <div class="campo-review-form__buttons">
                                        <button type="submit" name="decision" value="RECHAZAR" class="button button--danger button--small" data-campo-rechazar>Rechazar</button>
                                        <button type="submit" name="decision" value="APROBAR" class="button button--primary button--small">Aprobar</button>
                                    </div>
                                </form>
                            </div>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="campo-review-empty">
                        <strong>Todo al día: no hay marcaciones pendientes</strong>
                        <span>Las nuevas marcaciones web aparecerán aquí.</span>
                    </div>
                @endforelse
            </div>
        </section>
    @else
        <section class="campo-worker-note">
            <strong>¿Cómo funciona?</strong>
            <span>Registra una asignación, toma una foto del lugar al marcar y espera la aprobación. La marcación contará en tu asistencia cuando sea aprobada.</span>
        </section>
    @endif

    @if($esTrabajador)
        @include('trabajadores.partials.historial-campo')
    @else
    <section id="campoHistorial" data-campo-panel="historial" role="tabpanel" @if($esAdministrador) hidden @endif class="campo-history panel" aria-labelledby="historialCampoTitulo">
        <div class="campo-section-heading">
            <div>
                <span>HISTORIAL</span>
                <h3 id="historialCampoTitulo">{{ $esAdministrador ? 'Historial de marcaciones remotas' : 'Mis marcaciones remotas' }}</h3>
                <p>Últimas 30 marcaciones registradas desde la web.</p>
            </div>
        </div>
        <div class="campo-history-table-wrap">
            <table class="table campo-history-table">
                <thead><tr><th>Fecha y hora</th>@if($esAdministrador)<th>Trabajador</th>@endif<th>Marcación</th><th>Actividad</th><th>Estado</th><th>Foto</th></tr></thead>
                <tbody>
                    @forelse ($marcacionesRecientes as $marcacionCampo)
                        <tr>
                            <td data-label="Fecha">{{ $marcacionCampo->fecha_hora?->format('d/m/Y H:i') }}</td>
                            @if($esAdministrador)<td data-label="Trabajador">{{ $marcacionCampo->trabajador?->nombres }} {{ $marcacionCampo->trabajador?->apellidos }}</td>@endif
                            <td data-label="Marcación">{{ ucfirst(strtolower($marcacionCampo->tipo)) }}</td>
                            <td data-label="Actividad" class="campo-history-table__activity">{{ \Illuminate\Support\Str::limit($marcacionCampo->asignacion?->actividad, 65) }}</td>
                            <td data-label="Estado"><span class="campo-mini-mark campo-mini-mark--{{ strtolower($marcacionCampo->estado) }}">{{ ucfirst(strtolower($marcacionCampo->estado)) }}</span></td>
                            <td data-label="Foto" class="campo-history-table__photo-cell">
                                @if ($marcacionCampo->evidencia_path)
                                    <a href="{{ route('marcaciones-campo.evidencia', $marcacionCampo) }}"
                                        class="campo-photo-icon"
                                        data-campo-photo
                                        aria-label="Ver foto de evidencia de {{ $marcacionCampo->trabajador?->nombres }}"
                                        title="Ampliar fotografía">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                                            <circle cx="8.5" cy="8.5" r="1.5"/>
                                            <path d="m21 15-5-5L5 21"/>
                                        </svg>
                                    </a>
                                @else
                                    <span class="campo-photo-icon campo-photo-icon--disabled" aria-label="Sin fotografía disponible" title="Sin fotografía">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                                            <circle cx="8.5" cy="8.5" r="1.5"/>
                                            <path d="m21 15-5-5L5 21"/>
                                            <path d="M3 3 21 21"/>
                                        </svg>
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr class="campo-history-table__empty"><td colspan="{{ $esAdministrador ? 6 : 5 }}">Todavía no hay marcaciones remotas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    @endif


    {{-- ASIGNACIONES Y FILTROS --}}
    <section id="campoAsignaciones" data-campo-panel="asignaciones" role="tabpanel" @if($esAdministrador) hidden @endif class="campo-assignments" aria-labelledby="campoAsignacionesTitulo">
        <div class="campo-section-heading campo-assignments__heading">
            <div>
                <span>TRABAJO EN CAMPO</span>
                <h3 id="campoAsignacionesTitulo">{{ $esAdministrador ? 'Asignaciones del personal' : ($soloAsignacionesHoy ? 'Mis asignaciones de hoy' : 'Mis asignaciones') }}</h3>
                <p>{{ $soloAsignacionesHoy ? today()->format('d/m/Y').' · Usa los filtros para consultar otras fechas.' : 'Consulta las actividades y abre sus detalles cuando los necesites.' }}</p>
            </div>
            <details class="campo-filter-details" @if(request()->filled('buscar') || request()->filled('area_id') || request()->filled('fecha') || request()->filled('estado')) open @endif>
                <summary>Buscar y filtrar <span aria-hidden="true">⌄</span></summary>
                <div class="campo-filter-popover">
                    <form method="GET" action="{{ route('asignaciones-campo.index') }}" class="campo-filtros">
                        @if($esTrabajador)
                            <input type="hidden" name="todas" value="1">
                        @endif
                        <div class="form-group">
                            <label class="form-label">Buscar</label>
                            <input type="text" name="buscar" value="{{ request('buscar') }}" class="form-control" placeholder="Trabajador, DNI, actividad o lugar...">
                        </div>
                        <div class="form-group">
                            @if ($esAdministrador)
                                <label class="form-label">Área</label>
                                <select name="area_id" class="form-select">
                                    <option value="">Todas las áreas</option>
                                    @foreach ($areas as $area)
                                        <option value="{{ $area->id }}" @selected(request('area_id') == $area->id)>{{ $area->nombre }}</option>
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
                                <option value="">Todos los estados</option>
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
                            <button type="submit" class="button button--primary">Filtrar</button>
                            <a href="{{ route('asignaciones-campo.index') }}" class="button button--secondary">{{ $esTrabajador ? 'Volver a hoy' : 'Restablecer' }}</a>
                        </div>
                    </form>
                </div>
            </details>
        </div>
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

                <div class="campo-card__preview">
                    <span class="campo-card__dates"><x-ui-icon name="calendar" /> {{ $asignacion->fecha_inicio?->format('d/m/Y') }}@if($asignacion->fecha_fin?->format('Y-m-d') !== $asignacion->fecha_inicio?->format('Y-m-d')) – {{ $asignacion->fecha_fin?->format('d/m/Y') }}@endif</span>
                    <p>{{ $asignacion->actividad }}</p>
                    <span class="campo-card__place" title="{{ $asignacion->lugar ?: 'Lugar no especificado' }}"><x-ui-icon name="map-pin" /> {{ $asignacion->lugar ?: 'Lugar no especificado' }}</span>
                </div>
                <div class="campo-card__details">
                    
                    <div id="campo-asignacion-{{ $asignacion->getKey() }}" class="campo-modal-source" hidden>
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

                    <div class="campo-info campo-info--full">
                        <span class="campo-info__label">Avances de esta asignación ({{ $asignacion->avances->count() }}) · Consúltalos en Mi avance</span>
                        @if($esAdministrador)
                        @forelse($asignacion->avances->groupBy(fn ($avance) => $avance->reporte?->fecha_presentacion?->format('d/m/Y') ?? 'Sin fecha') as $fechaAvance => $avancesDelDia)
                            <div class="campo-assignment-progress">
                                <strong>{{ $fechaAvance }}</strong>
                                @foreach($avancesDelDia as $avance)
                                    <article>
                                        <p>{{ $avance->descripcion }}</p>
                                        <small>{{ $avance->created_at?->format('H:i') }} · {{ ucfirst(strtolower($avance->reporte?->estado ?? 'PENDIENTE')) }}</small>
                                        @if($avance->archivo)
                                            <a class="button button--secondary button--small" href="{{ route('reportesAvance.actividades.archivo', $avance) }}" target="_blank" rel="noopener">Ver evidencia del avance</a>
                                        @endif
                                    </article>
                                @endforeach
                            </div>
                        @empty
                            <p>Aún no hay avances vinculados a esta asignación.</p>
                        @endforelse
                        @endif
                    </div>
                    @if ($esAdministrador && $asignacion->marcacionesRemotas->isNotEmpty())
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
                    </div>
                </div>

                <div class="campo-card__footer">
                    @if($esTrabajador && $asignacion->estado === 'ACTIVA')
                        <button type="button" class="button button--secondary button--small" data-campo-modal-target="campo-avance-{{ $asignacion->id }}" data-campo-modal-title="Registrar avance de campo"><x-ui-icon name="plus" /> Registrar avance</button>
                        <div id="campo-avance-{{ $asignacion->id }}" class="campo-modal-source" hidden>
                            <form method="POST" action="{{ route('asignaciones-campo.avances.store', $asignacion) }}" enctype="multipart/form-data" class="form campo-avance-form">
                                @csrf
                                <p><strong>{{ $asignacion->actividad }}</strong><br>{{ $asignacion->lugar }} · {{ today()->format('d/m/Y') }}</p>
                                <p>Se agregará a tu reporte de hoy y quedará vinculado a esta asignación.</p>
                                <label class="form-group"><span class="form-label">¿Qué realizaste? *</span><textarea name="descripcion_avance" class="form-control" maxlength="500" required rows="4">{{ old('descripcion_avance') }}</textarea></label>
                                <label class="form-group"><span class="form-label">Evidencia del avance (opcional, máximo 5 MB)</span><input type="file" name="archivo_avance" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp"></label>
                                <button type="submit" class="button button--primary">Guardar avance</button>
                            </form>
                        </div>
                    @endif
                    <button type="button" class="campo-icon-btn campo-icon-btn--info"
                        data-campo-modal-target="campo-asignacion-{{ $asignacion->getKey() }}"
                        data-campo-modal-title="Información de asignación"
                        title="Información y avances de asignación" aria-label="Ver información y avances de la asignación">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="9" />
                                <path d="M12 11v5" />
                                <path d="M12 8h.01" />
                            </svg>
                    </button>
                    @if ($esTrabajador && $asignacion->estado === 'ACTIVA')
                        <form method="POST" action="{{ route('asignaciones-campo.marcar', $asignacion) }}"
                            enctype="multipart/form-data" class="campo-mark-form">
                            @csrf
                            <label class="campo-evidence-input">
                                <span>Foto de evidencia *</span>
                                <input type="file" name="evidencia" accept="image/jpeg,image/png,image/webp"
                                    capture="environment" required>
                            </label>
                            <button type="submit" class="button button--primary button--small">
                                <x-ui-icon name="clock" /> Marcar ahora
                            </button>
                        </form>
                    @elseif ($esAdministrador && $asignacion->estado !== 'CANCELADA')
                        <button type="button" class="campo-icon-btn campo-icon-btn--edit" title="Editar asignación" aria-label="Editar asignación" data-editar-asignacion
                            data-url="{{ route('asignaciones-campo.update', $asignacion) }}"
                            data-trabajador="{{ $asignacion->trabajador_id }}"
                            data-inicio="{{ $asignacion->fecha_inicio?->format('Y-m-d') }}"
                            data-fin="{{ $asignacion->fecha_fin?->format('Y-m-d') }}"
                            data-lugar="{{ $asignacion->lugar }}" data-actividad="{{ $asignacion->actividad }}"
                            data-observacion="{{ $asignacion->observacion }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 20h9" />
                                <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L9 17l-4 1 1-4Z" />
                            </svg>
                        </button>

                        <button type="button" class="campo-icon-btn campo-icon-btn--delete" title="Cancelar asignación" aria-label="Cancelar asignación" data-cancelar-asignacion
                            data-url="{{ route('asignaciones-campo.cancelar', $asignacion) }}"
                            data-trabajador="{{ $asignacion->trabajador?->nombres }} {{ $asignacion->trabajador?->apellidos }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 6h18" />
                                <path d="M8 6V4h8v2" />
                                <path d="M19 6l-1 14H6L5 6" />
                                <path d="M10 10v7" />
                                <path d="M14 10v7" />
                            </svg>
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
                <strong>{{ $soloAsignacionesHoy ? 'No tienes asignaciones para hoy' : 'No existen asignaciones de campo' }}</strong>
                <span>{{ $soloAsignacionesHoy ? 'Puedes crear una asignación o buscar otra fecha con los filtros.' : 'Prueba otros filtros o registra una nueva asignación.' }}</span>
            </div>
        @endforelse
    </section>

    <div class="pagination-container">
        {{ $asignaciones->links() }}
    </div>

    </section> {{-- /campo-assignments --}}

    {{-- MODAL CREAR: compacto, accesible y sin nuevos campos de base de datos --}}
    <x-modal id="modalCrearAsignacion" title="Nueva asignación de campo">
        <form method="POST" action="{{ route('asignaciones-campo.store') }}" class="form campo-create-form" id="campoCrearForm">
            @csrf

            @if ($esAdministrador)
                <div class="form-group campo-create-worker">
                    <label class="form-label" for="campoCrearTrabajador">Trabajador <span aria-hidden="true">*</span></label>
                    <select id="campoCrearTrabajador" name="trabajador_id" class="form-select" required>
                        <option value="">Seleccionar trabajador</option>
                        @foreach ($trabajadores as $trabajador)
                            <option value="{{ $trabajador->id }}" @selected(old('trabajador_id') == $trabajador->id)>
                                {{ $trabajador->apellidos }}, {{ $trabajador->nombres }} — {{ $trabajador->area?->nombre ?? 'Sin área' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="form-grid form-grid--2 campo-create-dates">
                <div class="form-group">
                    <label class="form-label" for="campoCrearInicio">Desde *</label>
                    <input type="date" id="campoCrearInicio" name="fecha_inicio"
                        value="{{ old('fecha_inicio', $esTrabajador ? today()->toDateString() : '') }}"
                        @if($esTrabajador) min="{{ today()->toDateString() }}" @endif
                        class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="campoCrearFin">Hasta *</label>
                    <input type="date" id="campoCrearFin" name="fecha_fin"
                        value="{{ old('fecha_fin', $esTrabajador ? today()->toDateString() : '') }}"
                        @if($esTrabajador) min="{{ today()->toDateString() }}" @endif
                        class="form-control" required>
                </div>
            </div>
            <div class="campo-create-date-tools">
                <button type="button" class="campo-create-today" id="campoCrearMismoDia">Usar solo un día</button>
                <span id="campoCrearPeriodoTexto" role="status" aria-live="polite"></span>
            </div>
            <div class="form-group">
                <label class="form-label" for="campoCrearLugar">Lugar o destino <span class="campo-create-optional">Opcional</span></label>
                <input type="text" id="campoCrearLugar" name="lugar" value="{{ old('lugar') }}"
                    class="form-control" maxlength="250" placeholder="Ej. Obra del cliente, almacén, proveedor">
            </div>
            <div class="form-group">
                <label class="form-label" for="campoCrearActividad">Actividad a realizar *</label>
                <textarea id="campoCrearActividad" name="actividad" class="form-textarea" rows="2" required
                    placeholder="Ej. Entrega de materiales y verificación con el cliente">{{ old('actividad') }}</textarea>
            </div>
            <details class="campo-create-extra" @if(old('observacion')) open @endif>
                <summary><x-ui-icon name="message-square" /> Agregar observaciones <span>(opcional)</span></summary>
                <div class="form-group">
                    <label class="form-label" for="campoCrearObservacion">Indicaciones adicionales</label>
                    <textarea id="campoCrearObservacion" name="observacion" class="form-textarea" rows="2" maxlength="500"
                        placeholder="Ej. Coordinar el ingreso con el encargado">{{ old('observacion') }}</textarea>
                </div>
            </details>
            <div class="modal__actions campo-create-actions">
                <button type="button" class="button button--secondary" data-modal-close>Volver</button>
                <button type="submit" class="button button--primary"><x-ui-icon name="check" /> Registrar asignación</button>
            </div>
        </form>
    </x-modal>

    @if ($esAdministrador)
    {{-- MODAL EDITAR: mismo estilo compacto que Nueva asignación; IDs originales intactos --}}
    <x-modal id="modalEditarAsignacion" title="Editar asignación de campo">
        <form method="POST" id="formEditarAsignacion" class="form campo-create-form campo-edit-form">
            @csrf
            @method('PUT')

            <div class="form-group campo-create-worker">
                <label class="form-label" for="editarAsignacionTrabajador">Trabajador *</label>
                <select name="trabajador_id" id="editarAsignacionTrabajador" class="form-select" required>
                    @foreach ($trabajadores as $trabajador)
                        <option value="{{ $trabajador->id }}">
                            {{ $trabajador->apellidos }}, {{ $trabajador->nombres }} — {{ $trabajador->area?->nombre ?? 'Sin área' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-grid form-grid--2 campo-create-dates">
                <div class="form-group">
                    <label class="form-label" for="editarAsignacionInicio">Desde *</label>
                    <input type="date" name="fecha_inicio" id="editarAsignacionInicio" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="editarAsignacionFin">Hasta *</label>
                    <input type="date" name="fecha_fin" id="editarAsignacionFin" class="form-control" required>
                </div>
            </div>
            <div class="campo-create-date-tools">
                <button type="button" class="campo-create-today" id="campoEditarMismoDia">Usar solo un día</button>
                <span id="campoEditarPeriodoTexto" role="status" aria-live="polite"></span>
            </div>
            <div class="form-group">
                <label class="form-label" for="editarAsignacionLugar">Lugar o destino <span class="campo-create-optional">Opcional</span></label>
                <input type="text" name="lugar" id="editarAsignacionLugar" class="form-control" maxlength="250" placeholder="Ej. Obra del cliente, almacén, proveedor">
            </div>
            <div class="form-group">
                <label class="form-label" for="editarAsignacionActividad">Actividad a realizar *</label>
                <textarea name="actividad" id="editarAsignacionActividad" class="form-textarea" rows="2" required placeholder="Describe la actividad"></textarea>
            </div>
            <details class="campo-create-extra" id="campoEditarObservaciones">
                <summary><x-ui-icon name="message-square" /> Observaciones <span>(opcional)</span></summary>
                <div class="form-group">
                    <label class="form-label" for="editarAsignacionObservacion">Indicaciones adicionales</label>
                    <textarea name="observacion" id="editarAsignacionObservacion" class="form-textarea" rows="2" maxlength="500"></textarea>
                </div>
            </details>
            <div class="modal__actions campo-create-actions">
                <button type="button" class="button button--secondary" data-modal-close>Volver</button>
                <button type="submit" class="button button--primary"><x-ui-icon name="check" /> Guardar cambios</button>
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


    {{-- Visor de detalles y fotografías: permanece en esta misma pantalla. --}}
    <dialog id="campoOverlay" class="campo-overlay" aria-labelledby="campoOverlayTitle">
        <div class="campo-overlay__head">
            <h3 id="campoOverlayTitle">Detalle</h3>
            <button type="button" class="campo-overlay__close" data-campo-close aria-label="Cerrar ventana">×</button>
        </div>
        <div id="campoOverlayBody" class="campo-overlay__body"></div>
    </dialog>
    </div> {{-- /campo-page --}}
@endsection

@push('scripts')
    @if($esTrabajador)
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.campo-mark-form, .campo-avance-form').forEach(form => {
            form.addEventListener('submit', event => {
                if (form.dataset.submitting === 'true') {
                    event.preventDefault();
                    return;
                }
                form.dataset.submitting = 'true';
                const button = form.querySelector('button[type="submit"]');
                if (button) {
                    button.disabled = true;
                    button.dataset.originalLabel = button.textContent;
                    button.textContent = 'Enviando…';
                }
            });
        });
    });
    window.addEventListener('pageshow', () => {
        document.querySelectorAll('.campo-mark-form[data-submitting="true"], .campo-avance-form[data-submitting="true"]').forEach(form => {
            delete form.dataset.submitting;
            const button = form.querySelector('button[type="submit"]');
            if (button) {
                button.disabled = false;
                button.textContent = button.dataset.originalLabel || 'Guardar';
            }
        });
    });
    </script>
    @endif
    <script src="{{ asset('js/pages/asignaciones-campo.js') }}"></script>
    <script>
    (() => {
        const setup = () => {
            const form = document.getElementById('campoCrearForm');
            if (!form || form.dataset.dateReady) return;
            form.dataset.dateReady = '1';
            const start = form.querySelector('#campoCrearInicio');
            const end = form.querySelector('#campoCrearFin');
            const sameDay = form.querySelector('#campoCrearMismoDia');
            const output = form.querySelector('#campoCrearPeriodoTexto');
            const sync = () => {
                end.min = start.value || end.dataset.initialMin || '';
                if (start.value && end.value && end.value < start.value) end.value = start.value;
                if (start.value && end.value) {
                    const d1 = new Date(start.value + 'T12:00:00');
                    const d2 = new Date(end.value + 'T12:00:00');
                    const days = Math.round((d2 - d1) / 86400000) + 1;
                    output.textContent = days === 1 ? 'Asignación de 1 día' : `Asignación de ${days} días`;
                } else output.textContent = 'Selecciona el periodo';
            };
            end.dataset.initialMin = end.min;
            start.addEventListener('change', sync);
            end.addEventListener('change', sync);
            sameDay.addEventListener('click', () => {
                if (!start.value) { const now = new Date(); start.value = [now.getFullYear(), String(now.getMonth() + 1).padStart(2, '0'), String(now.getDate()).padStart(2, '0')].join('-'); }
                end.value = start.value;
                sync();
            });
            form.addEventListener('submit', e => {
                if (start.value && end.value && end.value < start.value) {
                    e.preventDefault();
                    end.setCustomValidity('La fecha de fin no puede ser anterior al inicio.');
                    end.reportValidity();
                } else end.setCustomValidity('');
            });
            end.addEventListener('input', () => end.setCustomValidity(''));
            sync();
        };
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', setup, {once:true});
        else setup();
    })();
    </script>
    <script>
    (() => {
        const init = () => {
            const page = document.querySelector('.campo-page');
            if (!page || page.dataset.interactiveReady) return;
            page.dataset.interactiveReady = '1';

            const tabs = [...page.querySelectorAll('[data-campo-tab]')];
            const panels = [...page.querySelectorAll('[data-campo-panel]')];
            const valid = tabs.map(tab => tab.dataset.campoTab);
            const defaultTab = @json($esAdministrador ? 'pendientes' : 'asignaciones');
            const selectTab = (name, updateURL = true) => {
                const selected = valid.includes(name) && (tabs.length || name === 'asignaciones') ? name : defaultTab;
                panels.forEach(panel => { panel.hidden = panel.dataset.campoPanel !== selected; });
                tabs.forEach(tab => {
                    const active = tab.dataset.campoTab === selected;
                    tab.classList.toggle('campo-tabs__tab--active', active);
                    tab.setAttribute('aria-selected', String(active));
                    tab.tabIndex = active ? 0 : -1;
                });
                if (updateURL) {
                    const url = new URL(location.href);
                    url.searchParams.set('vista', selected);
                    if (selected !== 'avances') url.searchParams.delete('avances_page');
                    if (selected !== 'historial') url.searchParams.delete('historial_page');
                    history.replaceState(null, '', url);
                }
            };
            const params = new URL(location.href).searchParams;
            const initial = params.get('vista') || (params.has('avances_page') ? 'avances' : params.has('historial_page') ? 'historial' : null);
            selectTab(initial || (['buscar','area_id','fecha','estado'].some(key => new URL(location.href).searchParams.has(key)) ? 'asignaciones' : defaultTab), false);
            tabs.forEach(tab => tab.addEventListener('click', e => {
                e.preventDefault();
                selectTab(tab.dataset.campoTab);
            }));
            // Mantener la pestaña de asignaciones tras usar búsqueda y paginación.
            page.querySelectorAll('.campo-filtros').forEach(form => {
                form.addEventListener('submit', () => {
                    let input = form.querySelector('input[name="vista"]');
                    if (!input) { input = document.createElement('input'); input.type='hidden'; input.name='vista'; form.appendChild(input); }
                    input.value = 'asignaciones';
                });
            });
            page.querySelectorAll('.pagination-container a, .campo-filtros__actions a').forEach(a => {
                const url = new URL(a.href, location.href);
                url.searchParams.set('vista', 'asignaciones');
                a.href = url.toString();
            });

            const dialog = page.querySelector('#campoOverlay');
            const body = page.querySelector('#campoOverlayBody');
            const title = page.querySelector('#campoOverlayTitle');
            let originalParent = null;
            let movedContent = null;
            let lastTrigger = null;
            const cleanup = () => {
                if (movedContent && originalParent) {
                    while (movedContent.firstChild) originalParent.appendChild(movedContent.firstChild);
                    originalParent.hidden = true;
                }
                movedContent = null;
                originalParent = null;
                body.replaceChildren();
                lastTrigger?.focus();
            };
            dialog.addEventListener('close', cleanup);
            dialog.addEventListener('click', e => {
                if (e.target === dialog || e.target.closest('[data-campo-close]')) dialog.close();
            });
            page.addEventListener('click', e => {
                const trigger = e.target.closest('[data-campo-modal-target]');
                const photo = e.target.closest('a[data-campo-photo]');
                if (!trigger && !photo) return;
                e.preventDefault();
                lastTrigger = trigger || photo;
                if (dialog.open) cleanup();
                body.replaceChildren();
                if (photo) {
                    title.textContent = 'Evidencia fotográfica';
                    const img = document.createElement('img');
                    img.src = photo.href;
                    img.alt = photo.querySelector('img')?.alt || 'Evidencia de marcación';
                    img.className = 'campo-overlay__photo';
                    body.appendChild(img);
                } else {
                    const source = document.getElementById(trigger.dataset.campoModalTarget);
                    if (!source) return;
                    title.textContent = trigger.dataset.campoModalTitle || 'Detalle';
                    originalParent = source;
                    movedContent = source.firstElementChild;
                    // La sección puede tener múltiples nodos: agrupamos todos en un contenedor.
                    const wrapper = document.createElement('div');
                    wrapper.className = 'campo-overlay__content';
                    while (source.firstChild) wrapper.appendChild(source.firstChild);
                    movedContent = wrapper;
                    body.appendChild(wrapper);
                }
                if (!dialog.open) dialog.showModal();
            });
        };
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, {once:true});
        else init();
    })();
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.campo-review-form').forEach(form => {
            form.addEventListener('submit', event => {
                const submitter = event.submitter;
                const input = form.querySelector('[name="observacion_revision"]');
                if (submitter?.value === 'RECHAZAR' && input && !input.value.trim()) {
                    event.preventDefault();
                    input.setCustomValidity('Indica el motivo del rechazo.');
                    input.reportValidity();
                    input.focus();
                    return;
                }
                input?.setCustomValidity('');
            });
            const input = form.querySelector('[name="observacion_revision"]');
            input?.addEventListener('input', () => input.setCustomValidity(''));
        });
    });
    </script>
    <script>
    (() => {
        const setup = () => {
            const form = document.getElementById('formEditarAsignacion');
            if (!form || form.dataset.compactEditReady) return;
            form.dataset.compactEditReady = '1';
            const start = form.querySelector('#editarAsignacionInicio');
            const end = form.querySelector('#editarAsignacionFin');
            const sameDay = form.querySelector('#campoEditarMismoDia');
            const output = form.querySelector('#campoEditarPeriodoTexto');
            const notes = form.querySelector('#editarAsignacionObservacion');
            const notesPanel = form.querySelector('#campoEditarObservaciones');
            const sync = () => {
                end.min = start.value || '';
                if (start.value && end.value) {
                    const days = Math.round((new Date(end.value + 'T12:00:00') - new Date(start.value + 'T12:00:00')) / 86400000) + 1;
                    output.textContent = days > 0 ? (days === 1 ? 'Asignación de 1 día' : `Asignación de ${days} días`) : 'Revisa el periodo';
                } else output.textContent = 'Selecciona el periodo';
            };
            start.addEventListener('change', sync);
            end.addEventListener('change', sync);
            sameDay.addEventListener('click', () => {
                if (!start.value) return start.focus();
                end.value = start.value;
                end.setCustomValidity('');
                sync();
            });
            form.addEventListener('submit', e => {
                end.setCustomValidity('');
                if (start.value && end.value && end.value < start.value) {
                    e.preventDefault();
                    end.setCustomValidity('La fecha de fin no puede ser anterior al inicio.');
                    end.reportValidity();
                }
            });
            end.addEventListener('input', () => end.setCustomValidity(''));
            // El JS existente rellena los campos cuando se pulsa Editar.
            // Sincroniza las ayudas después de ese rellenado, sin cambiar su evento.
            document.addEventListener('click', e => {
                if (!e.target.closest('[data-editar-asignacion]')) return;
                requestAnimationFrame(() => {
                    sync();
                    notesPanel.open = Boolean(notes.value.trim());
                });
            });
            sync();
        };
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', setup, {once:true});
        else setup();
    })();
    </script>
@endpush

