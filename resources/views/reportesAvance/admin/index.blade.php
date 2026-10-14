@extends('layouts.app')

@section('title', 'Reportes de Avance')
@section('page-title', 'Reportes de Avance')
@section('page-subtitle', 'Revisión de actividades y pendientes del personal')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/reportes-avance.css') }}">
@endpush

@section('content')
<div class="ra-admin">
    {{-- CABECERA --}}
    <header class="ra-admin__header">
        <div>
            <h2>Reportes de avance</h2>
            <p>Consulta las actividades del personal y supervisa las tareas pendientes por área.</p>
        </div>
        @if ($puedeGestionar)
            <button type="button" class="ra-btn ra-btn--primary" data-modal-open="modalCrearPendienteArea">
                <span aria-hidden="true">＋</span> Asignar pendiente
            </button>
        @endif
    </header>

    {{-- MENSAJES --}}
    @if (session('success'))
        <div class="ra-alert ra-alert--success" role="status">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="ra-alert ra-alert--error" role="alert">{{ session('error') }}</div>
    @endif

    {{-- PESTAÑAS --}}
    <nav class="ra-nav" aria-label="Secciones de reportes">
        <a class="ra-nav__item {{ $pestanaActiva === 'avances' ? 'is-active' : '' }}"
           href="{{ route('reportesAvance.index', ['pestana' => 'avances']) }}"
           @if ($pestanaActiva === 'avances') aria-current="page" @endif>
            <span aria-hidden="true">▤</span> Reportes diarios
        </a>
        <a class="ra-nav__item {{ $pestanaActiva === 'pendientes' ? 'is-active' : '' }}"
           href="{{ route('reportesAvance.index', ['pestana' => 'pendientes']) }}"
           @if ($pestanaActiva === 'pendientes') aria-current="page" @endif>
            <span aria-hidden="true">◷</span> Pendientes
            <span class="ra-nav__count">{{ $pendientesArea->count() }}</span>
        </a>
    </nav>

    @if ($pestanaActiva === 'avances')
        {{-- FILTROS DE REPORTES --}}
        <section class="ra-panel" aria-label="Filtros de reportes diarios">
            <div class="ra-panel__heading">
                <div><h3>Filtrar reportes</h3><p>Consulta actividades por trabajador, área, fecha o estado.</p></div>
            </div>
            <form class="ra-filters" method="GET" action="{{ route('reportesAvance.index') }}">
                <input type="hidden" name="pestana" value="avances">
                <label class="ra-field ra-field--search">
                    <span>Buscar</span>
                    <input class="form-control" type="search" name="buscar" value="{{ request('buscar') }}" placeholder="Trabajador o actividad...">
                </label>
                <label class="ra-field">
                    <span>Área</span>
                    <select class="form-select" name="area_id">
                        <option value="">Todas las áreas</option>
                        @foreach ($areas as $area)
                            <option value="{{ $area->id }}" @selected(request('area_id') == $area->id)>{{ $area->nombre }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="ra-field">
                    <span>Estado</span>
                    <select class="form-select" name="estado">
                        <option value="">Todos los estados</option>
                        <option value="PENDIENTE" @selected(request('estado') === 'PENDIENTE')>Pendiente</option>
                        <option value="REVISADO" @selected(request('estado') === 'REVISADO')>Revisado</option>
                        <option value="OBSERVADO" @selected(request('estado') === 'OBSERVADO')>Observado</option>
                    </select>
                </label>
                <label class="ra-field"><span>Desde</span><input class="form-control" type="date" name="fecha_desde" value="{{ request('fecha_desde') }}"></label>
                <label class="ra-field"><span>Hasta</span><input class="form-control" type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}"></label>
                <div class="ra-filters__actions">
                    <button class="ra-btn ra-btn--primary" type="submit">Filtrar</button>
                    <a class="ra-btn ra-btn--outline" href="{{ route('reportesAvance.index', ['pestana' => 'avances']) }}">Limpiar</a>
                </div>
            </form>
        </section>

        {{-- REPORTES DIARIOS --}}
        <section class="ra-section" aria-label="Listado de reportes diarios">
            <div class="ra-section__header">
                <div><h3>Reportes diarios</h3><p>Actividades realizadas y registradas por el personal.</p></div>
                <span class="ra-counter">{{ $reportes->total() }} reportes</span>
            </div>
            <div class="ra-reports">
                @forelse ($reportes as $reporte)
                    <article class="ra-report">
                        <div class="ra-report__head">
                            <div class="ra-person">
                                <div class="ra-person__avatar">{{ mb_strtoupper(mb_substr($reporte->trabajador?->nombres ?? '?', 0, 1)) }}</div>
                                <div class="ra-person__info">
                                    <h4>{{ $reporte->trabajador?->nombres }} {{ $reporte->trabajador?->apellidos }}</h4>
                                    <span>{{ $reporte->trabajador?->area?->nombre ?? 'Sin área' }}</span>
                                </div>
                            </div>
                            <div class="ra-report__meta">
                                <time>{{ $reporte->fecha_presentacion?->format('d/m/Y') ?? 'Sin fecha' }}</time>
                                @if ($reporte->estado === 'REVISADO')
                                    <span class="ra-status ra-status--success">Revisado</span>
                                @elseif ($reporte->estado === 'OBSERVADO')
                                    <span class="ra-status ra-status--danger">Observado</span>
                                @else
                                    <span class="ra-status ra-status--warning">Pendiente</span>
                                @endif
                            </div>
                        </div>
                        <div class="ra-report__body">
                            <div class="ra-report__title"><h5>Actividades realizadas</h5><span>{{ $reporte->actividades->count() }}</span></div>
                            <div class="ra-activities">
                                @forelse ($reporte->actividades as $indice => $actividad)
                                    <div class="ra-activity">
                                        <span class="ra-activity__number">{{ $indice + 1 }}</span>
                                        <div>
                                            <p>{{ $actividad->descripcion }}</p>
                                            @if ($actividad->archivo)
                                                <a href="{{ Storage::url($actividad->archivo) }}" target="_blank" rel="noopener noreferrer">↗ Ver archivo adjunto</a>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <p class="ra-muted">Sin actividades registradas.</p>
                                @endforelse
                            </div>
                            @if ($reporte->comentario_revision)
                                <div class="ra-observation"><strong>Comentario de revisión</strong><p>{{ $reporte->comentario_revision }}</p></div>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="ra-empty"><strong>No se encontraron reportes</strong><p>Prueba con otros filtros o fechas.</p></div>
                @endforelse
            </div>
            <div class="ra-pagination">{{ $reportes->links() }}</div>
        </section>
    @else
        {{-- FILTROS DE PENDIENTES --}}
        <section class="ra-panel" aria-label="Filtros de pendientes">
            <div class="ra-panel__heading"><div><h3>Filtrar pendientes</h3><p>Visualiza los trabajos pendientes por área.</p></div></div>
            <form class="ra-filters ra-filters--short" method="GET" action="{{ route('reportesAvance.index') }}">
                <input type="hidden" name="pestana" value="pendientes">
                <label class="ra-field"><span>Área responsable</span>
                    <select class="form-select" name="area_id">
                        <option value="">Todas las áreas</option>
                        @foreach ($areas as $area)
                            <option value="{{ $area->id }}" @selected(request('area_id') == $area->id)>{{ $area->nombre }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="ra-filters__actions">
                    <button class="ra-btn ra-btn--primary" type="submit">Filtrar</button>
                    <a class="ra-btn ra-btn--outline" href="{{ route('reportesAvance.index', ['pestana' => 'pendientes']) }}">Limpiar</a>
                </div>
            </form>
        </section>

        {{-- TARJETAS DE PENDIENTES (ACCIONES SOLO SI POR_REVISAR) --}}
        <section class="ra-section" aria-label="Pendientes de las áreas">
            <div class="ra-section__header">
                <div><h3>Pendientes de las áreas</h3><p>Consulta tareas abiertas y revisa las finalizadas.</p></div>
                <span class="ra-counter">{{ $pendientesArea->count() }} pendientes</span>
            </div>
            <div class="ra-pending-grid">
                @forelse ($pendientesArea as $pendiente)
                    <article class="ra-pending">
                        <div class="ra-pending__head">
                            @if ($pendiente->estado_revision === 'POR_REVISAR')
                                <span class="ra-status ra-status--info">Por revisar</span>
                            @elseif ($pendiente->estado_revision === 'OBSERVADO')
                                <span class="ra-status ra-status--warning">Observado</span>
                            @elseif ($pendiente->estado_revision === 'RECHAZADO')
                                <span class="ra-status ra-status--danger">No completado</span>
                            @else
                                <span class="ra-status ra-status--warning">Pendiente</span>
                            @endif
                            <time>{{ $pendiente->created_at?->format('d/m/Y H:i') }}</time>
                        </div>
                        <span class="ra-area">{{ $pendiente->area?->nombre ?? 'Sin área' }}</span>
                        <h4>{{ $pendiente->descripcion }}</h4>
                        @if ($pendiente->detalle)
                            <div class="ra-detail"><strong>Indicaciones</strong><p>{{ $pendiente->detalle }}</p></div>
                        @endif
                        @if ($pendiente->ruta_archivos)
                            <div class="ra-detail"><strong>Ubicación de archivos</strong><code>{{ $pendiente->ruta_archivos }}</code></div>
                        @endif
                        @if ($pendiente->observacion_revision)
                            <div class="ra-observation"><strong>Observación</strong><p>{{ $pendiente->observacion_revision }}</p></div>
                        @endif
                        <div class="ra-pending__origin"><span>Registrado por</span><strong>{{ $pendiente->creadoPor?->name ?? 'Administración' }}</strong></div>

                        @if ($puedeGestionar && $pendiente->estado_revision === 'POR_REVISAR')
                            <div class="ra-pending__actions">
                                @include('reportesAvance.partials.acciones-revision-pendiente', ['pendiente' => $pendiente])
                            </div>
                        @endif
                    </article>
                @empty
                    <div class="ra-empty"><strong>No hay pendientes abiertos</strong><p>No existen tareas para el área seleccionada.</p></div>
                @endforelse
            </div>
        </section>
    @endif

    {{-- MODALES EXISTENTES: SE CONSERVAN LOS IDS UTILIZADOS POR EL JS --}}
    @if ($puedeGestionar)
        <x-modal id="modalCrearPendienteArea" title="Asignar tarea pendiente" size="large">
            <form method="POST" action="{{ route('reportesAvance.pendientes.store') }}" class="form ra-modal-form">
                @csrf
                @if ($errors->pendienteArea->any())
                    <div class="ra-alert ra-alert--error" role="alert">
                        @foreach ($errors->pendienteArea->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif
                <div class="form-group"><label class="form-label" for="raAreaDestino">Área responsable *</label>
                    <select id="raAreaDestino" class="form-select" name="area_id" required>
                        <option value="">Selecciona un área</option>
                        @foreach ($areas as $area)
                            <option value="{{ $area->id }}" @selected(old('area_id') == $area->id)>{{ $area->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group"><label class="form-label" for="raDescripcion">Tarea pendiente *</label>
                    <input id="raDescripcion" class="form-control" type="text" name="descripcion" value="{{ old('descripcion') }}" maxlength="500" required>
                </div>
                <div class="form-group"><label class="form-label" for="raDetalle">Detalle o indicaciones</label>
                    <textarea id="raDetalle" class="form-textarea" name="detalle" rows="4" maxlength="2000">{{ old('detalle') }}</textarea>
                </div>
                <div class="form-group"><label class="form-label" for="raRuta">Ruta o ubicación de archivos</label>
                    <input id="raRuta" class="form-control" name="ruta_archivos" type="text" value="{{ old('ruta_archivos') }}" maxlength="500">
                </div>
                <div class="modal__actions">
                    <button type="button" class="button button--secondary" data-modal-close>Cancelar</button>
                    <button type="submit" class="button button--primary">Asignar pendiente</button>
                </div>
            </form>
        </x-modal>

        <x-modal id="modalObservarPendiente" title="Observar pendiente">
            <form method="POST" id="formObservarPendiente" class="form ra-modal-form">
                @csrf
                @method('PATCH')
                <input type="hidden" name="decision" value="OBSERVADO">
                <input type="hidden" name="pendiente_revision_url" id="pendienteRevisionUrl" value="{{ old('pendiente_revision_url') }}">
                @if ($errors->revisionPendiente->any())
                    <div class="ra-alert ra-alert--error" role="alert">
                        @foreach ($errors->revisionPendiente->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif
                <p>Escribe qué debe corregirse en <strong id="pendienteObservadoNombre">este pendiente</strong>.</p>
                <div class="form-group"><label class="form-label" for="raComentario">Observación *</label>
                    <textarea id="raComentario" class="form-textarea" name="observacion_revision" rows="5" maxlength="2000" required>{{ old('observacion_revision') }}</textarea>
                </div>
                <div class="modal__actions">
                    <button type="button" class="button button--secondary" data-modal-close>Cancelar</button>
                    <button type="submit" class="button button--primary">Guardar observación</button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/pages/reportes-avance.js') }}"></script>
    @if ($puedeGestionar && $errors->pendienteArea->any())
        <script>
            document.addEventListener('DOMContentLoaded', () => window.AppModal?.open('modalCrearPendienteArea'));
        </script>
    @elseif ($puedeGestionar && $errors->revisionPendiente->any())
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const form = document.getElementById('formObservarPendiente');
                const url = document.getElementById('pendienteRevisionUrl')?.value;
                if (form && url) { form.action = url; window.AppModal?.open('modalObservarPendiente'); }
            });
        </script>
    @endif
@endpush