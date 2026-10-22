@extends('layouts.app')

@section('title', 'Reportes de Avance')
@section('page-title', 'Reportes de Avance')
@section('page-subtitle', 'Seguimiento de actividades y pendientes del personal')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/reportes-avance.css') }}">
@endpush

@section('content')
<div class="ra-admin">

    {{-- CABECERA --}}
    <header class="ra-admin__header">
        <div>
            <h2>{{ $vistaEquipo ? 'Avances de mi área' : 'Reportes de avance' }}</h2>
            <p>
                {{ $vistaEquipo
                    ? 'Consulta las actividades registradas por los trabajadores de tu área.'
                    : 'Consulta las actividades realizadas por el personal y supervisa los pendientes de cada área.' }}
            </p>
        </div>

        @if($puedeGestionar)
            <button type="button" class="ra-btn ra-btn--primary" data-modal-open="modalCrearPendienteArea">
                <span aria-hidden="true">＋</span>
                Asignar pendiente
            </button>
        @endif
    </header>

    {{-- MENSAJES --}}
    @if(session('success'))
        <div class="ra-alert ra-alert--success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="ra-alert ra-alert--error">{{ session('error') }}</div>
    @endif

    {{-- PESTAÑAS --}}
    <nav class="ra-nav" aria-label="Secciones de reportes">
        <a href="{{ route('reportesAvance.index', array_merge($parametrosVista, ['pestana' => 'avances'])) }}"
            class="ra-nav__item {{ $pestanaActiva === 'avances' ? 'is-active' : '' }}">
            <span aria-hidden="true">▤</span>
            Reportes por área
        </a>

        <a href="{{ route('reportesAvance.index', array_merge($parametrosVista, ['pestana' => 'pendientes'])) }}"
            class="ra-nav__item {{ $pestanaActiva === 'pendientes' ? 'is-active' : '' }}">
            <span aria-hidden="true">◷</span>
            Pendientes

            @if($pendientesArea->count())
                <span class="ra-nav__count">{{ $pendientesArea->count() }}</span>
            @endif
        </a>
    </nav>

    @if($pestanaActiva === 'avances')

        {{-- FILTROS --}}
        <section class="ra-panel">
            <div class="ra-panel__heading">
                <h3>Consultar actividades</h3>

                <p>
                    @if(request('fecha_desde') || request('fecha_hasta'))
                        Mostrando las actividades correspondientes al periodo seleccionado.
                    @elseif(request('estado'))
                        Mostrando todos los reportes que coinciden con el estado seleccionado.
                    @else
                        Mostrando únicamente las actividades registradas hoy.
                    @endif
                </p>
            </div>

            <form method="GET" action="{{ route('reportesAvance.index') }}" class="ra-filters">
                <input type="hidden" name="pestana" value="avances">
                @if($vistaEquipo)
                    <input type="hidden" name="vista" value="equipo">
                @endif

                <label class="ra-field ra-field--search">
                    <span>Buscar</span>

                    <input type="search"
                        name="buscar"
                        value="{{ request('buscar') }}"
                        class="form-control"
                        placeholder="Trabajador o actividad...">
                </label>

                <label class="ra-field">
                    <span>Estado del reporte</span>

                    <select name="estado" class="form-select">
                        <option value="">Todos</option>
                        <option value="PENDIENTE" @selected(request('estado') === 'PENDIENTE')>Por revisar</option>
                        <option value="REVISADO" @selected(request('estado') === 'REVISADO')>Revisado</option>
                        <option value="OBSERVADO" @selected(request('estado') === 'OBSERVADO')>Observado</option>
                    </select>
                </label>

                <label class="ra-field">
                    <span>Desde</span>

                    <input type="date"
                        name="fecha_desde"
                        value="{{ request('fecha_desde') }}"
                        class="form-control">
                </label>

                <label class="ra-field">
                    <span>Hasta</span>

                    <input type="date"
                        name="fecha_hasta"
                        value="{{ request('fecha_hasta') }}"
                        class="form-control">
                </label>

                <div class="ra-filters__actions">
                    <button type="submit" class="ra-btn ra-btn--primary">
                        Filtrar
                    </button>

                    <a href="{{ route('reportesAvance.index', array_merge($parametrosVista, ['pestana' => 'avances'])) }}"
                        class="ra-btn ra-btn--outline">
                        Hoy
                    </a>
                </div>
            </form>
        </section>

        {{-- ÁREAS --}}
        <section class="ra-section">

            <div class="ra-section__header">
                <div>
                    <h3>Áreas de la empresa</h3>
                    <p>Selecciona un área y luego un trabajador para consultar sus actividades.</p>
                </div>

                <span class="ra-counter">{{ $areas->count() }} áreas</span>
            </div>

            <div class="ra-area-grid">

                @forelse($areas as $area)

                    @php
                        $trabajadoresArea = $area->trabajadores ?? collect();

                        $reportesArea = $reportes->filter(
                            fn($reporte) => $reporte->trabajador?->area_id === $area->id
                        );

                        $totalActividadesArea = $reportesArea->sum(
                            fn($reporte) => $reporte->actividades->count()
                        );

                        $trabajadoresConReporte = $reportesArea
                            ->pluck('trabajador_id')
                            ->unique()
                            ->count();
                    @endphp

                    <details class="ra-area-card">

                        <summary class="ra-area-card__summary">

                            <div class="ra-area-card__icon">
                                {{ mb_strtoupper(mb_substr($area->nombre, 0, 2)) }}
                            </div>

                            <div class="ra-area-card__info">
                                <h4>{{ $area->nombre }}</h4>

                                <span>
                                    {{ $trabajadoresArea->count() }}
                                    {{ $trabajadoresArea->count() === 1 ? 'trabajador' : 'trabajadores' }}
                                </span>
                            </div>

                            <div class="ra-area-card__stats">

                                <div>
                                    <strong>{{ $trabajadoresConReporte }}</strong>
                                    <span>Con reporte</span>
                                </div>

                                <div>
                                    <strong>{{ $totalActividadesArea }}</strong>
                                    <span>Actividades</span>
                                </div>

                            </div>

                            <span class="ra-area-card__arrow">⌄</span>

                        </summary>

                        <div class="ra-area-card__body">

                            @forelse($trabajadoresArea as $trabajador)

                                @php
                                    $reportesTrabajador = $reportes
                                        ->where('trabajador_id', $trabajador->id)
                                        ->sortByDesc('fecha_presentacion');

                                    $totalActividades = $reportesTrabajador->sum(
                                        fn($reporte) => $reporte->actividades->count()
                                    );
                                @endphp

                                <details class="ra-worker-row">

                                    <summary class="ra-worker-row__summary">

                                        <div class="ra-worker-avatar">
                                            {{ mb_strtoupper(mb_substr($trabajador->nombres ?? '?', 0, 1)) }}
                                        </div>

                                        <div class="ra-worker-info">
                                            <strong>
                                                {{ $trabajador->nombres }} {{ $trabajador->apellidos }}
                                            </strong>

                                            <span>
                                                DNI {{ $trabajador->dni ?? 'Sin registrar' }}
                                            </span>
                                        </div>

                                        <div class="ra-worker-resume">

                                            <span>
                                                <strong>{{ $reportesTrabajador->count() }}</strong>
                                                {{ $reportesTrabajador->count() === 1 ? 'reporte' : 'reportes' }}
                                            </span>

                                            <span>
                                                <strong>{{ $totalActividades }}</strong>
                                                {{ $totalActividades === 1 ? 'actividad' : 'actividades' }}
                                            </span>

                                        </div>

                                        <span class="ra-worker-row__arrow">⌄</span>

                                    </summary>

                                    <div class="ra-worker-row__body">

                                        @if($reportesTrabajador->isEmpty())

                                            <div class="ra-worker-empty">
                                                Este trabajador no tiene actividades registradas para la fecha o periodo seleccionado.
                                            </div>

                                        @else

                                            <div class="ra-worker-days">

                                                @foreach($reportesTrabajador as $reporte)

                                                    <article class="ra-worker-day">

                                                        <div class="ra-worker-day__header">

                                                            <div class="ra-worker-day__date">
                                                                <strong>
                                                                    {{ $reporte->fecha_presentacion?->translatedFormat('d M Y') ?? 'Sin fecha' }}
                                                                </strong>

                                                                <span>
                                                                    {{ $reporte->actividades->count() }}
                                                                    {{ $reporte->actividades->count() === 1 ? 'actividad' : 'actividades' }}
                                                                </span>
                                                            </div>

                                                            <div class="ra-worker-day__right">

                                                                @if($reporte->estado === 'REVISADO')

                                                                    <span class="ra-status ra-status--success">
                                                                        Revisado
                                                                    </span>

                                                                @elseif($reporte->estado === 'OBSERVADO')

                                                                    <span class="ra-status ra-status--danger">
                                                                        Observado
                                                                    </span>

                                                                @else

                                                                    <span class="ra-status ra-status--warning">
                                                                        Por revisar
                                                                    </span>

                                                                @endif

                                                                @if($puedeRevisar && $reporte->estado !== 'REVISADO')

                                                                    <div class="ra-report-review">

                                                                        <form method="POST"
                                                                            action="{{ route('reportesAvance.revisar', $reporte) }}">

                                                                            @csrf
                                                                            @method('PATCH')

                                                                            <input type="hidden"
                                                                                name="estado"
                                                                                value="REVISADO">

                                                                            <button type="submit"
                                                                                class="ra-review-btn ra-review-btn--approve"
                                                                                title="Marcar como revisado"
                                                                                aria-label="Marcar como revisado">
                                                                                ✓
                                                                            </button>

                                                                        </form>

                                                                        <button type="button"
                                                                            class="ra-review-btn ra-review-btn--observe"
                                                                            data-observar-reporte
                                                                            data-url="{{ route('reportesAvance.revisar', $reporte) }}"
                                                                            data-trabajador="{{ $reporte->trabajador?->nombres }} {{ $reporte->trabajador?->apellidos }}"
                                                                            data-fecha="{{ $reporte->fecha_presentacion?->format('d/m/Y') }}"
                                                                            title="Observar reporte"
                                                                            aria-label="Observar reporte">
                                                                            !
                                                                        </button>

                                                                    </div>

                                                                @endif

                                                            </div>

                                                        </div>

                                                        <div class="ra-worker-activities">

                                                            @forelse($reporte->actividades as $indice => $actividad)

                                                                <div class="ra-worker-activity">

                                                                    <span class="ra-worker-activity__number">
                                                                        {{ $indice + 1 }}
                                                                    </span>

                                                                    <div class="ra-worker-activity__content">

                                                                        <p>
                                                                            {{ $actividad->descripcion }}
                                                                        </p>

                                                                        @if($actividad->archivo)

                                                                            <div class="ra-activity-files">

                                                                                <a href="{{ route('reportesAvance.actividades.archivo', $actividad) }}"
                                                                                    target="_blank"
                                                                                    rel="noopener noreferrer"
                                                                                    class="ra-file-action">
                                                                                    Ver archivo
                                                                                </a>

                                                                                <a href="{{ route('reportesAvance.actividades.descargar', $actividad) }}"
                                                                                    class="ra-file-action ra-file-action--download">
                                                                                    Descargar
                                                                                </a>

                                                                            </div>

                                                                        @endif

                                                                    </div>

                                                                </div>

                                                            @empty

                                                                <div class="ra-worker-empty">
                                                                    No hay actividades registradas en este reporte.
                                                                </div>

                                                            @endforelse

                                                        </div>

                                                        @if($reporte->comentario_revision)

                                                            <div class="ra-worker-observation">
                                                                <strong>Comentario de revisión</strong>
                                                                <p>{{ $reporte->comentario_revision }}</p>
                                                            </div>

                                                        @endif

                                                    </article>

                                                @endforeach

                                            </div>

                                        @endif

                                    </div>

                                </details>

                            @empty

                                <div class="ra-area-empty">
                                    No existen trabajadores activos registrados en esta área.
                                </div>

                            @endforelse

                        </div>

                    </details>

                @empty

                    <div class="ra-empty">
                        <strong>No existen áreas registradas</strong>
                        <p>Primero debes registrar y activar las áreas de la empresa.</p>
                    </div>

                @endforelse

            </div>

        </section>

    @else

        {{-- FILTRO DE PENDIENTES --}}
        <section class="ra-panel">

            <div class="ra-panel__heading">
                <h3>Filtrar pendientes</h3>
                <p>Consulta los trabajos abiertos y los que fueron enviados para revisión.</p>
            </div>

            <form class="ra-filters ra-filters--short"
                method="GET"
                action="{{ route('reportesAvance.index') }}">

                <input type="hidden" name="pestana" value="pendientes">
                @if($vistaEquipo)
                    <input type="hidden" name="vista" value="equipo">
                @endif

                <label class="ra-field">
                    <span>Área responsable</span>

                    <select name="area_id" class="form-select">
                        <option value="">Todas las áreas</option>

                        @foreach($areas as $area)

                            <option value="{{ $area->id }}"
                                @selected(request('area_id') == $area->id)>
                                {{ $area->nombre }}
                            </option>

                        @endforeach
                    </select>
                </label>

                <div class="ra-filters__actions">

                    <button type="submit" class="ra-btn ra-btn--primary">
                        Filtrar
                    </button>

                    <a href="{{ route('reportesAvance.index', array_merge($parametrosVista, ['pestana' => 'pendientes'])) }}"
                        class="ra-btn ra-btn--outline">
                        Limpiar
                    </a>

                </div>

            </form>

        </section>

        {{-- LISTADO DE PENDIENTES --}}
        <section class="ra-section">

            <div class="ra-section__header">

                <div>
                    <h3>Seguimiento de pendientes</h3>
                    <p>Trabajos pendientes y finalizados enviados al administrador para revisión.</p>
                </div>

                <span class="ra-counter">
                    {{ $pendientesArea->count() }} registros
                </span>

            </div>

            @php
                $pendientesAgrupados = $pendientesArea->groupBy(function($pendiente) {
                    return $pendiente->area?->nombre
                        ?? $pendiente->reporte?->trabajador?->area?->nombre
                        ?? 'Sin área';
                });
            @endphp

            <div class="ra-pending-groups">

                @forelse($pendientesAgrupados as $nombreArea => $pendientes)

                    <details class="ra-pending-group">

                        <summary class="ra-pending-group__header">
                            <h4>{{ $nombreArea }}</h4>
                            <span class="ra-pending-group__arrow">⌄</span>
                        </summary>

                        <div class="ra-pending-table">

                            <div class="ra-pending-table__head">
                                <span>Estado</span>
                                <span>Pendiente</span>
                                <span>Registrado por</span>
                                <span>Finalizado por</span>
                                <span>Fecha</span>
                                <span>Acciones</span>
                            </div>

                            @foreach($pendientes as $pendiente)

                                <div class="ra-pending-row">

                                    <div>

                                        @if($pendiente->estado_revision === 'POR_REVISAR')

                                            <span class="ra-status ra-status--info">
                                                Finalizado · por revisar
                                            </span>

                                        @elseif($pendiente->estado_revision === 'OBSERVADO')

                                            <span class="ra-status ra-status--warning">
                                                Observado
                                            </span>

                                        @elseif($pendiente->estado_revision === 'RECHAZADO')

                                            <span class="ra-status ra-status--danger">
                                                No completado
                                            </span>

                                        @elseif($pendiente->estado === 'RESUELTO')

                                            <span class="ra-status ra-status--success">
                                                Resuelto
                                            </span>

                                        @else

                                            <span class="ra-status ra-status--warning">
                                                Pendiente
                                            </span>

                                        @endif

                                    </div>

                                    <div class="ra-pending-row__task">

                                        <strong>
                                            {{ $pendiente->descripcion }}
                                        </strong>

                                        @if($pendiente->detalle)
                                            <p>{{ $pendiente->detalle }}</p>
                                        @endif

                                        @if($pendiente->ruta_archivos)
                                            <code>{{ $pendiente->ruta_archivos }}</code>
                                        @endif

                                        @if($pendiente->observacion_revision)

                                            <div class="ra-row-observation">
                                                <strong>Observación:</strong>
                                                {{ $pendiente->observacion_revision }}
                                            </div>

                                        @endif

                                    </div>

                                    <div>

                                        <span class="ra-pending-row__label">
                                            Registrado por
                                        </span>

                                        <strong class="ra-pending-row__person">

                                            @if($pendiente->reporte?->trabajador)

                                                {{ $pendiente->reporte->trabajador->nombres }}
                                                {{ $pendiente->reporte->trabajador->apellidos }}

                                            @else

                                                {{ $pendiente->creadoPor?->name ?? 'Administración' }}

                                            @endif

                                        </strong>

                                    </div>

                                    <div>

                                        <span class="ra-pending-row__label">
                                            Finalizado por
                                        </span>

                                        @if($pendiente->resueltoPor)

                                            <strong class="ra-pending-row__person">
                                                {{ $pendiente->resueltoPor->nombres }}
                                                {{ $pendiente->resueltoPor->apellidos }}
                                            </strong>

                                        @else

                                            <span class="ra-muted-text">
                                                Aún no finalizado
                                            </span>

                                        @endif

                                    </div>

                                    <div>

                                        <span class="ra-pending-row__label">
                                            Fecha
                                        </span>

                                        <span class="ra-pending-row__date">
                                            {{ $pendiente->created_at?->format('d/m/Y') }}
                                        </span>

                                        @if($pendiente->fecha_resolucion)

                                            <small>
                                                Finalizado:
                                                {{ $pendiente->fecha_resolucion->format('d/m/Y H:i') }}
                                            </small>

                                        @endif

                                    </div>

                                    <div class="ra-pending-row__actions">

                                        @if($puedeGestionar && $pendiente->estado_revision === 'POR_REVISAR')

                                            @include('reportesAvance.partials.acciones-revision-pendiente', [
                                                'pendiente' => $pendiente
                                            ])

                                        @else

                                            <span class="ra-muted-text">—</span>

                                        @endif

                                    </div>

                                </div>

                            @endforeach

                        </div>

                    </details>

                @empty

                    <div class="ra-empty">
                        <strong>No existen pendientes</strong>
                        <p>No hay registros para el área seleccionada.</p>
                    </div>

                @endforelse

            </div>

        </section>

    @endif

    {{-- MODAL CREAR PENDIENTE --}}
    @if($puedeGestionar)

        <x-modal id="modalCrearPendienteArea"
            title="Asignar tarea pendiente"
            size="large">

            <form method="POST"
                action="{{ route('reportesAvance.pendientes.store') }}"
                class="form ra-modal-form">

                @csrf

                @if($errors->pendienteArea->any())

                    <div class="ra-alert ra-alert--error">

                        @foreach($errors->pendienteArea->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach

                    </div>

                @endif

                <div class="form-group">

                    <label class="form-label" for="raAreaDestino">
                        Área responsable *
                    </label>

                    <select id="raAreaDestino"
                        name="area_id"
                        class="form-select"
                        required>

                        <option value="">Selecciona un área</option>

                        @foreach($areas as $area)

                            <option value="{{ $area->id }}"
                                @selected(old('area_id') == $area->id)>
                                {{ $area->nombre }}
                            </option>

                        @endforeach

                    </select>

                </div>

                <div class="form-group">

                    <label class="form-label" for="raDescripcion">
                        Tarea pendiente *
                    </label>

                    <input id="raDescripcion"
                        type="text"
                        name="descripcion"
                        value="{{ old('descripcion') }}"
                        class="form-control"
                        maxlength="500"
                        required>

                </div>

                <div class="form-group">

                    <label class="form-label" for="raDetalle">
                        Detalle o indicaciones
                    </label>

                    <textarea id="raDetalle"
                        name="detalle"
                        class="form-textarea"
                        rows="4"
                        maxlength="2000">{{ old('detalle') }}</textarea>

                </div>

                <div class="form-group">

                    <label class="form-label" for="raRuta">
                        Ruta o ubicación de archivos
                    </label>

                    <input id="raRuta"
                        type="text"
                        name="ruta_archivos"
                        value="{{ old('ruta_archivos') }}"
                        class="form-control"
                        maxlength="500">

                </div>

                <div class="modal__actions">

                    <button type="button"
                        class="button button--secondary"
                        data-modal-close>
                        Cancelar
                    </button>

                    <button type="submit"
                        class="button button--primary">
                        Asignar pendiente
                    </button>

                </div>

            </form>

        </x-modal>

        {{-- MODAL OBSERVAR PENDIENTE --}}
        <x-modal id="modalObservarPendiente" title="Observar pendiente">

            <form method="POST"
                id="formObservarPendiente"
                class="form ra-modal-form">

                @csrf
                @method('PATCH')

                <input type="hidden"
                    name="decision"
                    value="OBSERVADO">

                <input type="hidden"
                    name="pendiente_revision_url"
                    id="pendienteRevisionUrl"
                    value="{{ old('pendiente_revision_url') }}">

                @if($errors->revisionPendiente->any())

                    <div class="ra-alert ra-alert--error">

                        @foreach($errors->revisionPendiente->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach

                    </div>

                @endif

                <p>
                    Escribe qué debe corregirse en
                    <strong id="pendienteObservadoNombre">este pendiente</strong>.
                </p>

                <div class="form-group">

                    <label class="form-label" for="raComentario">
                        Observación *
                    </label>

                    <textarea id="raComentario"
                        name="observacion_revision"
                        class="form-textarea"
                        rows="5"
                        maxlength="2000"
                        required>{{ old('observacion_revision') }}</textarea>

                </div>

                <div class="modal__actions">

                    <button type="button"
                        class="button button--secondary"
                        data-modal-close>
                        Cancelar
                    </button>

                    <button type="submit"
                        class="button button--primary">
                        Guardar observación
                    </button>

                </div>

            </form>

        </x-modal>

    @endif

    {{-- MODAL OBSERVAR REPORTE --}}
    @if($puedeRevisar)

        <x-modal id="modalObservarReporte"
            title="Observar reporte">

            <form method="POST"
                id="formObservarReporte"
                class="form ra-modal-form">

                @csrf
                @method('PATCH')

                <input type="hidden"
                    name="estado"
                    value="OBSERVADO">

                <p class="ra-modal-description">
                    Registrar observación para el reporte de
                    <strong id="reporteObservadoTrabajador"></strong>
                    correspondiente al
                    <strong id="reporteObservadoFecha"></strong>.
                </p>

                <div class="form-group">

                    <label class="form-label"
                        for="comentarioReporte">
                        Observación *
                    </label>

                    <textarea id="comentarioReporte"
                        name="comentario_revision"
                        class="form-textarea"
                        rows="5"
                        maxlength="500"
                        required
                        placeholder="Indica qué debe revisar o corregir el trabajador."></textarea>

                </div>

                <div class="modal__actions">

                    <button type="button"
                        class="button button--secondary"
                        data-modal-close>
                        Cancelar
                    </button>

                    <button type="submit"
                        class="button button--primary">
                        Guardar observación
                    </button>

                </div>

            </form>

        </x-modal>

    @endif

</div>
@endsection

@push('scripts')

    <script src="{{ asset('js/pages/reportes-avance.js') }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-observar-reporte]').forEach(button => {
                button.addEventListener('click', () => {
                    const form = document.getElementById('formObservarReporte');
                    const trabajador = document.getElementById('reporteObservadoTrabajador');
                    const fecha = document.getElementById('reporteObservadoFecha');

                    if (!form) return;

                    form.action = button.dataset.url;

                    if (trabajador) {
                        trabajador.textContent = button.dataset.trabajador || '';
                    }

                    if (fecha) {
                        fecha.textContent = button.dataset.fecha || '';
                    }

                    window.AppModal?.open('modalObservarReporte');
                });
            });
        });
    </script>

    @if($puedeGestionar && $errors->pendienteArea->any())

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                window.AppModal?.open('modalCrearPendienteArea');
            });
        </script>

    @elseif($puedeGestionar && $errors->revisionPendiente->any())

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const form = document.getElementById('formObservarPendiente');
                const url = document.getElementById('pendienteRevisionUrl')?.value;

                if (form && url) {
                    form.action = url;
                    window.AppModal?.open('modalObservarPendiente');
                }
            });
        </script>

    @endif

@endpush
