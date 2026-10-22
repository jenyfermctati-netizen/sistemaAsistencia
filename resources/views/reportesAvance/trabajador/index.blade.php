@extends('layouts.app')

@section('title', 'Reportes de Avance')
@section('page-title', 'Reportes de Avance')
@section('page-subtitle', 'Bitácora diaria de actividades y pendientes')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/reporte-avance-trabajador.css') }}">
@endpush

@section('content')
@php
    $usuarioActual = auth()->user();
    $puedeCrear = $usuarioActual->trabajador_id !== null;
    $esPestanaAvances = $pestanaActiva === 'avances';
@endphp

<div class="ra-worker">

    {{-- CABECERA --}}
    <header class="ra-worker__hero">
        <div class="ra-worker__hero-copy">
            <span class="ra-worker__eyebrow">Bitácora de trabajo</span>
            <h2>Mi actividad diaria</h2>
            <p>Registra tu jornada, aunque no tengas actividades, y consulta el historial de tus avances.</p>
        </div>

        @if($puedeCrear)
            <div class="ra-worker__hero-actions">
                @if(!$reporteHoy)
                    <button type="button"
                        class="button button--primary"
                        data-modal-open="modalCrearReporte">
                        <span class="button__icon">+</span>
                        Registrar reporte de hoy
                    </button>
                @else
                    <button type="button"
                        class="button button--primary"
                        data-modal-open="modalAgregarAvance">
                        <span class="button__icon">+</span>
                        Agregar avance
                    </button>
                @endif
            </div>
        @endif
    </header>

    {{-- MENSAJES --}}
    @if(session('success'))
        <div class="ra-alert ra-alert--success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="ra-alert ra-alert--error">{{ session('error') }}</div>
    @endif

    {{-- NAVEGACIÓN --}}
    <nav class="ra-tabs">
        <a href="{{ route('reportesAvance.index', ['pestana' => 'avances']) }}"
            class="ra-tabs__item {{ $esPestanaAvances ? 'is-active' : '' }}">
            <span>▤</span>
            Mi actividad
        </a>

        <a href="{{ route('reportesAvance.index', ['pestana' => 'pendientes']) }}"
            class="ra-tabs__item {{ !$esPestanaAvances ? 'is-active' : '' }}">
            <span>◷</span>
            Pendientes del área

            @if($pendientesArea->count())
                <span class="ra-tabs__count">{{ $pendientesArea->count() }}</span>
            @endif
        </a>
    </nav>

    @if($esPestanaAvances)

        {{-- RESUMEN DE HOY --}}
        <section class="ra-today">
            <div class="ra-today__head">
                <div>
                    <span class="ra-today__label">Hoy</span>
                    <h3>{{ now()->translatedFormat('d \d\e F \d\e Y') }}</h3>
                </div>

                @if($reporteHoy)
                    @if($reporteHoy->estado === 'REVISADO')
                        <span class="ra-status ra-status--success">Revisado</span>
                    @elseif($reporteHoy->estado === 'OBSERVADO')
                        <span class="ra-status ra-status--danger">Observado</span>
                    @else
                        <span class="ra-status ra-status--warning">Por revisar</span>
                    @endif
                @else
                    <span class="ra-status ra-status--neutral">Sin registrar</span>
                @endif
            </div>

            @if($reporteHoy)
                <div class="ra-today__stats">
                    <div class="ra-stat">
                        <strong>{{ $reporteHoy->actividades->count() }}</strong>
                        <span>Actividades</span>
                    </div>

                    <div class="ra-stat">
                        <strong>{{ $reporteHoy->pendientes->where('estado', 'PENDIENTE')->count() }}</strong>
                        <span>Pendientes</span>
                    </div>

                    <div class="ra-stat">
                        <strong>
                            {{ $reporteHoy->actividades->whereNotNull('archivo')->count() }}
                        </strong>
                        <span>Archivos</span>
                    </div>
                </div>

                @if($reporteHoy->estado === 'OBSERVADO' && $reporteHoy->comentario_revision)
                    <div class="ra-review-note">
                        <strong>Observación del administrador</strong>
                        <p>{{ $reporteHoy->comentario_revision }}</p>
                    </div>
                @endif

                <div class="ra-today__actions">
                    <button type="button"
                        class="button button--secondary"
                        data-ver-reporte
                        data-reporte-id="{{ $reporteHoy->id }}">
                        Ver detalle
                    </button>

                    @if($reporteHoy->estado !== 'REVISADO')
                        <button type="button"
                            class="button button--secondary"
                            data-editar-reporte
                            data-url="{{ route('reportesAvance.update', $reporteHoy) }}"
                            data-reporte-id="{{ $reporteHoy->id }}">
                            Editar reporte
                        </button>
                    @endif

                    <button type="button"
                        class="button button--primary"
                        data-modal-open="modalAgregarAvance">
                        + Agregar avance
                    </button>
                </div>
            @else
                <div class="ra-today__empty">
                    <div>
                        <strong>Aún no registraste tu reporte de hoy</strong>
                        <span>Puedes registrar el día aunque no tengas actividades para agregar.</span>
                    </div>

                    @if($puedeCrear)
                        <button type="button"
                            class="button button--primary"
                            data-modal-open="modalCrearReporte">
                            Registrar ahora
                        </button>
                    @endif
                </div>
            @endif
        </section>

        {{-- FILTROS --}}
        <section class="ra-filter-panel">
            <div class="ra-section-heading">
                <div>
                    <h3>Mi historial</h3>
                    <p>Consulta tus reportes anteriores por fecha o estado.</p>
                </div>
            </div>

            <form method="GET"
                action="{{ route('reportesAvance.index') }}"
                class="ra-history-filters">

                <input type="hidden" name="pestana" value="avances">

                <label class="ra-field ra-field--search">
                    <span>Buscar actividad</span>
                    <input type="search"
                        name="buscar"
                        value="{{ request('buscar') }}"
                        class="form-control"
                        placeholder="Ej. cotización, entrega, revisión...">
                </label>

                <label class="ra-field">
                    <span>Estado</span>
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

                <div class="ra-history-filters__actions">
                    <button type="submit" class="button button--primary">Filtrar</button>
                    <a href="{{ route('reportesAvance.index', ['pestana' => 'avances']) }}"
                        class="button button--secondary">
                        Limpiar
                    </a>
                </div>
            </form>
        </section>

        {{-- HISTORIAL --}}
        <section class="ra-history">
            @forelse($reportes as $reporte)

                <details class="ra-history-card"
                    @if($reporteHoy && $reporte->id === $reporteHoy->id) open @endif>

                    <summary class="ra-history-card__summary">

                        <div class="ra-history-card__date">
                            <span class="ra-history-card__day">
                                {{ $reporte->fecha_presentacion?->format('d') }}
                            </span>

                            <div>
                                <strong>
                                    {{ $reporte->fecha_presentacion?->translatedFormat('F Y') ?? 'Sin fecha' }}
                                </strong>

                                <span>
                                    {{ $reporte->actividades->count() }}
                                    {{ $reporte->actividades->count() === 1 ? 'actividad' : 'actividades' }}
                                </span>
                            </div>
                        </div>

                        <div class="ra-history-card__meta">
                            @if($reporte->estado === 'REVISADO')
                                <span class="ra-status ra-status--success">Revisado</span>
                            @elseif($reporte->estado === 'OBSERVADO')
                                <span class="ra-status ra-status--danger">Observado</span>
                            @else
                                <span class="ra-status ra-status--warning">Por revisar</span>
                            @endif

                            <span class="ra-history-card__arrow">⌄</span>
                        </div>

                    </summary>

                    <div class="ra-history-card__body">

                        <div class="ra-history-activities">

                            @forelse($reporte->actividades as $indice => $actividad)

                                <article class="ra-history-activity">
                                    <span class="ra-history-activity__number">
                                        {{ $indice + 1 }}
                                    </span>

                                    <div class="ra-history-activity__content">
                                        <p>{{ $actividad->descripcion }}</p>

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
                                </article>

                            @empty
                                <div class="ra-empty-state">
                                    No existen actividades en este reporte.
                                </div>
                            @endforelse

                        </div>

                        @if($reporte->comentario_revision)
                            <div class="ra-review-note">
                                <strong>Comentario de revisión</strong>
                                <p>{{ $reporte->comentario_revision }}</p>
                            </div>
                        @endif

                        <div class="ra-history-card__actions">
                            <button type="button"
                                class="button button--secondary button--small"
                                data-ver-reporte
                                data-reporte-id="{{ $reporte->id }}">
                                Ver detalle
                            </button>

                            @if(
                                $usuarioActual->trabajador_id === $reporte->trabajador_id &&
                                $reporte->estado !== 'REVISADO'
                            )
                                <button type="button"
                                    class="button button--secondary button--small"
                                    data-editar-reporte
                                    data-url="{{ route('reportesAvance.update', $reporte) }}"
                                    data-reporte-id="{{ $reporte->id }}">
                                    Editar
                                </button>
                            @endif
                        </div>

                    </div>

                </details>

            @empty
                <div class="ra-empty-state ra-empty-state--large">
                    <strong>No existen reportes</strong>
                    <span>No se encontraron registros para los filtros seleccionados.</span>
                </div>
            @endforelse
        </section>

        <div class="ra-pagination">
            {{ $reportes->links() }}
        </div>

    @else

        {{-- PENDIENTES DEL ÁREA --}}
        <section class="ra-worker-pending-section">

            <div class="ra-section-heading">
                <div>
                    <h3>Pendientes del área</h3>
                    <p>Consulta trabajos compartidos con tu equipo y marca los que hayas finalizado.</p>
                </div>

                <span class="ra-section-count">{{ $pendientesArea->count() }}</span>
            </div>

            <div class="ra-worker-pending-list">

                @forelse($pendientesArea as $pendiente)

                    <article class="ra-worker-pending">

                        <div class="ra-worker-pending__status">

                            @if($pendiente->estado_revision === 'POR_REVISAR')
                                <span class="ra-status ra-status--info">
                                    Finalizado · esperando revisión
                                </span>
                            @elseif($pendiente->estado_revision === 'OBSERVADO')
                                <span class="ra-status ra-status--danger">
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

                            <time>{{ $pendiente->created_at?->format('d/m/Y H:i') }}</time>
                        </div>

                        <div class="ra-worker-pending__content">

                            <h4>{{ $pendiente->descripcion }}</h4>

                            @if($pendiente->detalle)
                                <div class="ra-worker-pending__detail">
                                    <span>Detalle</span>
                                    <p>{{ $pendiente->detalle }}</p>
                                </div>
                            @endif

                            @if($pendiente->ruta_archivos)
                                <div class="ra-worker-pending__detail">
                                    <span>Ubicación de archivos</span>
                                    <code>{{ $pendiente->ruta_archivos }}</code>
                                </div>
                            @endif

                            @if($pendiente->observacion_revision)
                                <div class="ra-review-note">
                                    <strong>Observación del administrador</strong>
                                    <p>{{ $pendiente->observacion_revision }}</p>
                                </div>
                            @endif

                        </div>

                        <div class="ra-worker-pending__footer">

                            <div class="ra-worker-pending__origin">
                                <span>Registrado por</span>

                                <strong>
                                    @if($pendiente->reporte?->trabajador)
                                        {{ $pendiente->reporte->trabajador->nombres }}
                                        {{ $pendiente->reporte->trabajador->apellidos }}
                                    @else
                                        {{ $pendiente->creadoPor?->trabajador?->nombres
                                            ?? $pendiente->creadoPor?->name
                                            ?? 'Administración' }}

                                        {{ $pendiente->creadoPor?->trabajador?->apellidos ?? '' }}
                                    @endif
                                </strong>
                            </div>

                            @if($pendiente->estado_revision === 'POR_REVISAR')

                                <div class="ra-worker-pending__finished">
                                    <span>Finalizado</span>

                                    <strong>
                                        @if($pendiente->resueltoPor?->id === $usuarioActual->trabajador_id)
                                            Por ti
                                        @elseif($pendiente->resueltoPor)
                                            {{ $pendiente->resueltoPor->nombres }}
                                            {{ $pendiente->resueltoPor->apellidos }}
                                        @endif
                                    </strong>

                                    @if($pendiente->fecha_resolucion)
                                        <small>{{ $pendiente->fecha_resolucion->format('d/m/Y H:i') }}</small>
                                    @endif
                                </div>

                            @elseif($pendiente->estado !== 'RESUELTO')

                                <div class="ra-worker-pending__actions">
                                    <button type="button"
                                        class="button button--secondary button--small"
                                        data-editar-pendiente
                                        data-url="{{ route('reportesAvance.pendientes.update', $pendiente) }}"
                                        data-descripcion="{{ $pendiente->descripcion }}"
                                        data-detalle="{{ $pendiente->detalle }}"
                                        data-ruta-archivos="{{ $pendiente->ruta_archivos }}">
                                        Editar pendiente
                                    </button>

                                    <form method="POST"
                                        action="{{ route('reportesAvance.pendientes.resolver', $pendiente) }}">
                                        @csrf
                                        @method('PATCH')

                                        <button type="submit" class="button button--primary button--small">
                                            Marcar como finalizado
                                        </button>
                                    </form>
                                </div>

                            @endif

                        </div>

                    </article>

                @empty
                    <div class="ra-empty-state ra-empty-state--large">
                        <strong>No hay pendientes abiertos</strong>
                        <span>Tu área no tiene trabajos pendientes en este momento.</span>
                    </div>
                @endforelse

            </div>

        </section>

    @endif

    {{-- JSON PARA JS --}}
    <script type="application/json" id="reportesData">
{!! $reportes->getCollection()->mapWithKeys(function ($reporte) {
    return [
        $reporte->id => [
            'id' => $reporte->id,
            'fecha' => $reporte->fecha_presentacion?->format('d/m/Y'),
            'trabajador' => trim(($reporte->trabajador?->nombres ?? '') . ' ' . ($reporte->trabajador?->apellidos ?? '')),
            'estado' => $reporte->estado,
            'comentario' => $reporte->comentario_revision,
            'actividades' => $reporte->actividades->map(fn($actividad) => [
                'id' => $actividad->id,
                'descripcion' => $actividad->descripcion,
                'archivo' => $actividad->archivo,
                'archivo_url' => $actividad->archivo
                    ? route('reportesAvance.actividades.archivo', $actividad)
                    : null,
            ])->values(),
            'pendientes' => $reporte->pendientes->map(fn($pendiente) => [
                'id' => $pendiente->id,
                'descripcion' => $pendiente->descripcion,
                'detalle' => $pendiente->detalle,
                'ruta_archivos' => $pendiente->ruta_archivos,
                'estado' => $pendiente->estado,
            ])->values(),
        ]
    ];
})->toJson(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>

    {{-- CREAR REPORTE --}}
    @if($puedeCrear && !$reporteHoy)

        <x-modal id="modalCrearReporte" title="Reporte de avance del día" size="large">

            <form method="POST"
                action="{{ route('reportesAvance.store') }}"
                enctype="multipart/form-data"
                id="formCrearReporte"
                class="form">

                @csrf

                <div class="reporte-modal-fecha">
                    <span>Reporte correspondiente a</span>
                    <strong>{{ now()->format('d/m/Y') }}</strong>
                </div>

                <div class="form-section">

                    <div class="form-section__header">
                        <div>
                            <h4>Actividades realizadas</h4>
                            <p>Es opcional. Puedes guardar el reporte aunque hoy no tengas actividades para registrar.</p>
                        </div>

                        <button type="button"
                            class="button button--secondary button--small"
                            id="agregarActividad">
                            + Agregar actividad
                        </button>
                    </div>

                    <div id="actividadesContainer" class="items-dinamicos">
                        @foreach(old('actividades', []) as $indice => $actividadAnterior)
                            <div class="item-dinamico actividad-form-item">
                                <div class="item-dinamico__header">
                                    <strong class="item-titulo">Actividad {{ $indice + 1 }}</strong>
                                    <button type="button" class="item-eliminar" data-eliminar-item>Eliminar</button>
                                </div>

                                <input type="hidden" data-campo="id">

                                <div class="form-group">
                                    <label class="form-label">¿Qué realizaste? *</label>
                                    <input type="text" name="actividades[{{ $indice }}][descripcion]"
                                        data-campo="descripcion"
                                        value="{{ $actividadAnterior['descripcion'] ?? '' }}"
                                        class="form-control" maxlength="500"
                                        placeholder="Ej. Elaboré la cotización para el cliente ABC" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Archivo o imagen de sustento</label>
                                    <input type="file" name="actividades[{{ $indice }}][archivo]"
                                        data-campo="archivo" class="form-control"
                                        accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                                    <small class="form-help">Opcional · máximo 5 MB.</small>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="sin-pendientes" id="sinActividades">
                        Sin actividades agregadas. Puedes guardar el reporte así o agregar una actividad.
                    </div>

                </div>

                <div class="form-section form-section--pendientes">

                    <div class="form-section__header">
                        <div>
                            <h4>¿Dejaste algo pendiente?</h4>
                            <p>Registra lo que deba continuar posteriormente tu equipo.</p>
                        </div>

                        <button type="button"
                            class="button button--secondary button--small"
                            id="agregarPendiente">
                            + Agregar pendiente
                        </button>
                    </div>

                    <div id="pendientesContainer" class="items-dinamicos"></div>

                    <div class="sin-pendientes" id="sinPendientes">
                        No has agregado pendientes.
                    </div>

                </div>

                <div class="modal__actions">
                    <button type="button" class="button button--secondary" data-modal-close>
                        Cancelar
                    </button>

                    <button type="submit" class="button button--primary">
                        Guardar reporte del día
                    </button>
                </div>

            </form>

        </x-modal>

    @endif

    {{-- AGREGAR AVANCE --}}
    @if($reporteHoy)
        @include('reportesAvance.partials.modal-agregar-avance', [
            'reporteHoy' => $reporteHoy
        ])
    @endif

    {{-- EDITAR REPORTE --}}
    <x-modal id="modalEditarReporte" title="Editar reporte diario" size="large">

        <form method="POST"
            id="formEditarReporte"
            enctype="multipart/form-data"
            class="form">

            @csrf
            @method('PUT')

            <div class="form-section">

                <div class="form-section__header">
                    <div>
                        <h4>Actividades realizadas</h4>
                        <p>Puedes corregir o agregar actividades mientras el reporte no haya sido revisado.</p>
                    </div>

                    <button type="button"
                        class="button button--secondary button--small"
                        id="agregarActividadEditar">
                        + Agregar actividad
                    </button>
                </div>

                <div id="editarActividadesContainer" class="items-dinamicos"></div>

                <div class="sin-pendientes" id="sinActividadesEditar">
                    Este reporte no tiene actividades. Puedes dejarlo así o agregar una.
                </div>

            </div>

            <div class="form-section form-section--pendientes">

                <div class="form-section__header">
                    <div>
                        <h4>Pendientes</h4>
                        <p>Actualiza los pendientes registrados en este reporte.</p>
                    </div>

                    <button type="button"
                        class="button button--secondary button--small"
                        id="agregarPendienteEditar">
                        + Agregar pendiente
                    </button>
                </div>

                <div id="editarPendientesContainer" class="items-dinamicos"></div>

            </div>

            <div class="modal__actions">
                <button type="button" class="button button--secondary" data-modal-close>
                    Cancelar
                </button>

                <button type="submit" class="button button--primary">
                    Guardar cambios
                </button>
            </div>

        </form>

    </x-modal>

    {{-- EDITAR PENDIENTE DEL ÁREA --}}
    <x-modal id="modalEditarPendiente" title="Editar actividad pendiente" size="medium">

        <form method="POST" id="formEditarPendiente" class="form">
            @csrf
            @method('PUT')

            @if($errors->editarPendiente->any())
                <div class="live-form-errors" role="alert">
                    <strong>Revisa los datos del pendiente:</strong>
                    <ul>
                        @foreach($errors->editarPendiente->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <p class="form-help">
                Cualquier trabajador de esta área puede completar la información antes de enviarla a revisión.
            </p>

            <div class="form-group">
                <label for="editarPendienteDescripcion" class="form-label">Actividad pendiente *</label>
                <input type="text" id="editarPendienteDescripcion" name="descripcion"
                    class="form-control" maxlength="500" required>
            </div>

            <div class="form-group">
                <label for="editarPendienteDetalle" class="form-label">Detalle</label>
                <textarea id="editarPendienteDetalle" name="detalle" class="form-textarea"
                    rows="4" maxlength="2000"></textarea>
            </div>

            <div class="form-group">
                <label for="editarPendienteRuta" class="form-label">Ruta o ubicación de archivos</label>
                <input type="text" id="editarPendienteRuta" name="ruta_archivos"
                    class="form-control" maxlength="500">
            </div>

            <div class="modal__actions">
                <button type="button" class="button button--secondary" data-modal-close>Cancelar</button>
                <button type="submit" class="button button--primary">Guardar cambios</button>
            </div>
        </form>

    </x-modal>

    {{-- VER DETALLE --}}
    <x-modal id="modalVerReporte" title="Detalle del reporte diario" size="large">

        <div class="detalle-reporte">

            <div class="detalle-reporte__cabecera">
                <div>
                    <span>Trabajador</span>
                    <strong id="detalleTrabajador">-</strong>
                </div>

                <div>
                    <span>Fecha</span>
                    <strong id="detalleFecha">-</strong>
                </div>

                <div>
                    <span>Estado</span>
                    <strong id="detalleEstado">-</strong>
                </div>
            </div>

            <div class="detalle-seccion">
                <h4>Actividades realizadas</h4>
                <div id="detalleActividades"></div>
            </div>

            <div class="detalle-seccion">
                <h4>Pendientes</h4>
                <div id="detallePendientes"></div>
            </div>

            <div class="detalle-seccion detalle-seccion--comentario">
                <h4>Comentario de revisión</h4>
                <p id="detalleComentario">Sin comentario.</p>
            </div>

        </div>

    </x-modal>

    {{-- TEMPLATE ACTIVIDAD --}}
    <template id="templateActividad">

        <div class="item-dinamico actividad-form-item">

            <div class="item-dinamico__header">
                <strong class="item-titulo">Actividad</strong>
                <button type="button" class="item-eliminar" data-eliminar-item>
                    Eliminar
                </button>
            </div>

            <input type="hidden" data-campo="id">

            <div class="form-group">
                <label class="form-label">¿Qué realizaste? *</label>

                <input type="text"
                    data-campo="descripcion"
                    class="form-control"
                    maxlength="500"
                    placeholder="Ej. Elaboré la cotización para el cliente ABC"
                    required>
            </div>

            <div class="form-group">
                <label class="form-label">Archivo o imagen de sustento</label>

                <input type="file"
                    data-campo="archivo"
                    class="form-control"
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">

                <small class="form-help">Opcional · máximo 5 MB.</small>

                <div class="archivo-actual" data-archivo-actual></div>
            </div>

        </div>

    </template>

    {{-- TEMPLATE PENDIENTE --}}
    <template id="templatePendiente">

        <div class="item-dinamico pendiente-form-item">

            <div class="item-dinamico__header">
                <strong class="item-titulo">Pendiente</strong>
                <button type="button" class="item-eliminar" data-eliminar-item>
                    Eliminar
                </button>
            </div>

            <input type="hidden" data-campo="id">

            <div class="form-group">
                <label class="form-label">¿Qué quedó pendiente? *</label>

                <input type="text"
                    data-campo="descripcion"
                    class="form-control"
                    maxlength="500"
                    placeholder="Ej. Terminar cuadro comparativo">
            </div>

            <div class="form-group">
                <label class="form-label">Detalle</label>

                <textarea data-campo="detalle"
                    class="form-textarea"
                    rows="3"
                    maxlength="2000"
                    placeholder="Indica qué falta o qué debe considerar quien continúe el trabajo."></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Ruta o ubicación de archivos</label>

                <input type="text"
                    data-campo="ruta_archivos"
                    class="form-control"
                    maxlength="500"
                    placeholder="Ej. \\Servidor\Cotizaciones\ClienteABC">

                <small class="form-help">
                    Opcional. Indica dónde están los archivos necesarios.
                </small>
            </div>

        </div>

    </template>

</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/pages/reportes-avance.js') }}"></script>

    @if($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                if (document.getElementById('modalCrearReporte')) {
                    window.AppModal?.open('modalCrearReporte');
                }
            });
        </script>
    @endif

    @if($errors->agregarAvance->any())
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                if (document.getElementById('modalAgregarAvance')) {
                    window.AppModal?.open('modalAgregarAvance');
                }
            });
        </script>
    @endif
@endpush
