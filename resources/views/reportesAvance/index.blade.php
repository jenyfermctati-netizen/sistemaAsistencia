@extends('layouts.app')

@section('title', 'Reportes de Avance')
@section('page-title', 'Reportes de Avance')
@section('page-subtitle', 'Registro diario de actividades y pendientes del personal')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/reportes-avance.css') }}">
@endpush

@section('content')
    @php
        $usuarioActual = auth()->user();
        $puedeRevisar = in_array($usuarioActual->rol?->nombre, ['ADMINISTRADOR', 'GERENTE'], true);
        $puedeCrear = $usuarioActual->trabajador_id !== null;
    @endphp
    {{-- CABECERA --}}
    <div class="page-header">
        <div>
            <h2 class="page-header__title">Reportes de avance</h2>
            <p class="page-header__description">
                @if ($puedeRevisar)
                    Consulta y revisa las actividades diarias registradas por el personal.
                @else
                    Registra las actividades realizadas durante el día y deja indicaciones sobre trabajos pendientes.
                @endif
            </p>
        </div>

        @if ($puedeCrear)
            <div class="page-header__actions">
                @if (!$reporteHoy)
                    <button type="button" class="button button--primary" data-modal-open="modalCrearReporte">
                        + Registrar reporte de hoy
                    </button>
                @else
                    <span class="reporte-hoy-ok">✓ Reporte de hoy registrado</span>
                @endif
            </div>
        @endif
    </div>

    {{-- PENDIENTES DEL ÁREA --}}
    @if ($usuarioActual->trabajador_id && $pendientesArea->count())
        <section class="pendientes-area">
            <div class="section-heading">
                <div>
                    <h3>Pendientes del área</h3>
                    <p>Trabajos que quedaron sin terminar y pueden ser continuados por el equipo.</p>
                </div>
                <span class="pendientes-count">{{ $pendientesArea->count() }}</span>
            </div>

            <div class="pendientes-grid">
                @foreach ($pendientesArea as $pendiente)
                    <article class="pendiente-card">
                        <div class="pendiente-card__header">
                            <span class="badge badge--warning">Pendiente</span>
                            <span>{{ $pendiente->created_at?->format('d/m/Y H:i') }}</span>
                        </div>

                        <h4>{{ $pendiente->descripcion }}</h4>

                        @if ($pendiente->detalle)
                            <div class="pendiente-card__detalle">
                                <span>Detalle</span>
                                <p>{{ $pendiente->detalle }}</p>
                            </div>
                        @endif

                        @if ($pendiente->ruta_archivos)
                            <div class="pendiente-card__ruta">
                                <span>Ubicación de archivos</span>
                                <code>{{ $pendiente->ruta_archivos }}</code>
                            </div>
                        @endif

                        <div class="pendiente-card__origen">
                            <span>Registrado por</span>
                            <strong>
                                {{ $pendiente->reporte?->trabajador?->nombres }}
                                {{ $pendiente->reporte?->trabajador?->apellidos }}
                            </strong>
                        </div>

                        <form method="POST" action="{{ route('reportesAvance.pendientes.resolver', $pendiente) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="button button--primary button--small">
                                Marcar como resuelto
                            </button>
                        </form>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    {{-- FILTROS --}}
    <section class="panel">
        <div class="panel__header">
            <form method="GET" action="{{ route('reportesAvance.index') }}" class="reportes-filtros">
                <input type="text" name="buscar" value="{{ request('buscar') }}" class="form-control"
                    placeholder="Trabajador, actividad o pendiente...">

                <select name="estado" class="form-select">
                    <option value="">Todos los estados</option>
                    <option value="PENDIENTE" @selected(request('estado') === 'PENDIENTE')>Pendiente</option>
                    <option value="REVISADO" @selected(request('estado') === 'REVISADO')>Revisado</option>
                    <option value="OBSERVADO" @selected(request('estado') === 'OBSERVADO')>Observado</option>
                </select>

                <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}" class="form-control">
                <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}" class="form-control">

                <button type="submit" class="button button--secondary">Filtrar</button>
                <a href="{{ route('reportesAvance.index') }}" class="button button--secondary">Limpiar</a>
            </form>
        </div>
    </section>

    {{-- REPORTES --}}
    <section class="reportes-diarios">
        @forelse($reportes as $reporte)
            <article class="reporte-card">
                <div class="reporte-card__header">
                    <div class="reporte-persona">
                        <div class="reporte-avatar">
                            {{ strtoupper(substr($reporte->trabajador?->nombres ?? '?', 0, 1)) }}
                        </div>

                        <div>
                            <h3>{{ $reporte->trabajador?->nombres }} {{ $reporte->trabajador?->apellidos }}</h3>
                            <span>{{ $reporte->trabajador?->area?->nombre ?? 'Sin área' }}</span>
                        </div>
                    </div>

                    <div class="reporte-card__estado">
                        <span class="reporte-fecha">{{ $reporte->fecha_presentacion?->format('d/m/Y') }}</span>

                        @if ($reporte->estado === 'REVISADO')
                            <span class="badge badge--success">Revisado</span>
                        @elseif($reporte->estado === 'OBSERVADO')
                            <span class="badge badge--danger">Observado</span>
                        @else
                            <span class="badge badge--info">Pendiente</span>
                        @endif
                    </div>
                </div>

                <div class="reporte-card__body">
                    {{-- ACTIVIDADES --}}
                    <div class="reporte-bloque">
                        <div class="reporte-bloque__titulo">
                            <h4>Actividades realizadas</h4>
                            <span>{{ $reporte->actividades->count() }}</span>
                        </div>

                        <div class="actividades-lista">
                            @forelse($reporte->actividades as $indice => $actividad)
                                <div class="actividad-item">
                                    <div class="actividad-numero">{{ $indice + 1 }}</div>

                                    <div class="actividad-contenido">
                                        <p>{{ $actividad->descripcion }}</p>

                                        @if ($actividad->archivo)
                                            <a href="{{ Storage::url($actividad->archivo) }}" target="_blank"
                                                class="archivo-link">
                                                Ver archivo adjunto
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <span class="sin-datos">Sin actividades registradas.</span>
                            @endforelse
                        </div>
                    </div>

                    {{-- PENDIENTES --}}
                    @if ($reporte->pendientes->count())
                        <div class="reporte-bloque reporte-bloque--pendientes">
                            <div class="reporte-bloque__titulo">
                                <h4>Pendientes registrados</h4>
                                <span>{{ $reporte->pendientes->count() }}</span>
                            </div>

                            <div class="reporte-pendientes-lista">
                                @foreach ($reporte->pendientes as $pendiente)
                                    <div class="reporte-pendiente">
                                        <div class="reporte-pendiente__cabecera">
                                            <strong>{{ $pendiente->descripcion }}</strong>

                                            @if ($pendiente->estado === 'RESUELTO')
                                                <span class="badge badge--success">Resuelto</span>
                                            @else
                                                <span class="badge badge--warning">Pendiente</span>
                                            @endif
                                        </div>

                                        @if ($pendiente->detalle)
                                            <p>{{ $pendiente->detalle }}</p>
                                        @endif

                                        @if ($pendiente->ruta_archivos)
                                            <div class="reporte-pendiente__ruta">
                                                <span>Archivos:</span>
                                                <code>{{ $pendiente->ruta_archivos }}</code>
                                            </div>
                                        @endif

                                        @if ($pendiente->estado === 'RESUELTO')
                                            <small>
                                                Resuelto por
                                                {{ $pendiente->resueltoPor?->nombres }}
                                                {{ $pendiente->resueltoPor?->apellidos }}
                                                @if ($pendiente->fecha_resolucion)
                                                    · {{ $pendiente->fecha_resolucion->format('d/m/Y H:i') }}
                                                @endif
                                            </small>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- OBSERVACIÓN --}}
                    @if ($reporte->comentario_revision)
                        <div class="reporte-observacion">
                            <span>Comentario de revisión</span>
                            <p>{{ $reporte->comentario_revision }}</p>
                        </div>
                    @endif
                </div>

                <div class="reporte-card__footer">
                    <button type="button" class="button button--secondary button--small" data-ver-reporte
                        data-reporte-id="{{ $reporte->id }}">
                        Ver detalle
                    </button>

                    @if ($usuarioActual->trabajador_id === $reporte->trabajador_id && $reporte->estado !== 'REVISADO')
                        <button type="button" class="button button--secondary button--small" data-editar-reporte
                            data-url="{{ route('reportesAvance.update', $reporte) }}"
                            data-reporte-id="{{ $reporte->id }}">
                            Editar
                        </button>
                    @endif

                    @if ($puedeRevisar && $reporte->estado === 'PENDIENTE')
                        <button type="button" class="button button--primary button--small" data-revisar-reporte
                            data-url="{{ route('reportesAvance.revisar', $reporte) }}"
                            data-trabajador="{{ $reporte->trabajador?->nombres }} {{ $reporte->trabajador?->apellidos }}">
                            Revisar
                        </button>
                    @endif
                </div>
            </article>
        @empty
            <div class="empty-state">
                <strong>No existen reportes registrados</strong>
                <span>No se encontraron resultados para los filtros seleccionados.</span>
            </div>
        @endforelse
    </section>

    <div class="pagination-container">
        {{ $reportes->links() }}
    </div>

    {{-- INFORMACIÓN DE REPORTES PARA JS --}}
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
                'archivo_url' => $actividad->archivo ? Storage::url($actividad->archivo) : null,
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
    @if ($puedeCrear && !$reporteHoy)
        <x-modal id="modalCrearReporte" title="Reporte de avance del día" size="large">
            <form method="POST" action="{{ route('reportesAvance.store') }}" enctype="multipart/form-data"
                id="formCrearReporte" class="form">
                @csrf

                <div class="reporte-modal-fecha">
                    <span>Reporte correspondiente a</span>
                    <strong>{{ now()->format('d/m/Y') }}</strong>
                </div>

                <div class="form-section">
                    <div class="form-section__header">
                        <div>
                            <h4>Actividades realizadas</h4>
                            <p>Agrega cada actividad que realizaste durante el día.</p>
                        </div>

                        <button type="button" class="button button--secondary button--small" id="agregarActividad">
                            + Agregar actividad
                        </button>
                    </div>

                    <div id="actividadesContainer" class="items-dinamicos">
                        <div class="item-dinamico actividad-form-item">
                            <div class="item-dinamico__header">
                                <strong>Actividad 1</strong>
                            </div>

                            <div class="form-group">
                                <label class="form-label">¿Qué realizaste? *</label>
                                <input type="text" name="actividades[0][descripcion]" class="form-control"
                                    maxlength="500" placeholder="Ej. Elaboré la cotización para el cliente ABC" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Archivo o imagen de sustento</label>
                                <input type="file" name="actividades[0][archivo]" class="form-control"
                                    accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                                <small class="form-help">Opcional · máximo 5 MB.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section form-section--pendientes">
                    <div class="form-section__header">
                        <div>
                            <h4>¿Dejaste algo pendiente?</h4>
                            <p>Registra únicamente lo que otra persona podría necesitar continuar.</p>
                        </div>

                        <button type="button" class="button button--secondary button--small" id="agregarPendiente">
                            + Agregar pendiente
                        </button>
                    </div>

                    <div id="pendientesContainer" class="items-dinamicos"></div>

                    <div class="sin-pendientes" id="sinPendientes">
                        No has agregado pendientes.
                    </div>
                </div>

                <div class="modal__actions">
                    <button type="button" class="button button--secondary" data-modal-close>Cancelar</button>
                    <button type="submit" class="button button--primary">Guardar reporte del día</button>
                </div>
            </form>
        </x-modal>
    @endif

    {{-- EDITAR REPORTE --}}
    <x-modal id="modalEditarReporte" title="Editar reporte diario" size="large">
        <form method="POST" id="formEditarReporte" enctype="multipart/form-data" class="form">
            @csrf
            @method('PUT')

            <div class="form-section">
                <div class="form-section__header">
                    <div>
                        <h4>Actividades realizadas</h4>
                        <p>Puedes corregir o agregar actividades mientras el reporte no haya sido revisado.</p>
                    </div>

                    <button type="button" class="button button--secondary button--small" id="agregarActividadEditar">
                        + Agregar actividad
                    </button>
                </div>

                <div id="editarActividadesContainer" class="items-dinamicos"></div>
            </div>

            <div class="form-section form-section--pendientes">
                <div class="form-section__header">
                    <div>
                        <h4>Pendientes</h4>
                        <p>Actualiza los pendientes que registraste en este reporte.</p>
                    </div>

                    <button type="button" class="button button--secondary button--small" id="agregarPendienteEditar">
                        + Agregar pendiente
                    </button>
                </div>

                <div id="editarPendientesContainer" class="items-dinamicos"></div>
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

    {{-- REVISAR --}}
    @if ($puedeRevisar)
        <x-modal id="modalRevisarReporte" title="Revisar reporte">
            <form method="POST" id="formRevisarReporte" class="form">
                @csrf
                @method('PATCH')

                <p>
                    Reporte presentado por
                    <strong id="revisarTrabajador"></strong>.
                </p>

                <div class="form-group">
                    <label class="form-label">Resultado *</label>
                    <select name="estado" class="form-select" required>
                        <option value="REVISADO">Revisado</option>
                        <option value="OBSERVADO">Observado</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Comentario</label>
                    <textarea name="comentario_revision" class="form-textarea" maxlength="500" rows="5"
                        placeholder="Obligatorio si el reporte será observado."></textarea>
                </div>

                <div class="modal__actions">
                    <button type="button" class="button button--secondary" data-modal-close>Cancelar</button>
                    <button type="submit" class="button button--primary">Guardar revisión</button>
                </div>
            </form>
        </x-modal>
    @endif

    {{-- PLANTILLA ACTIVIDAD --}}
    <template id="templateActividad">
        <div class="item-dinamico actividad-form-item">
            <div class="item-dinamico__header">
                <strong class="item-titulo">Actividad</strong>
                <button type="button" class="item-eliminar" data-eliminar-item>Eliminar</button>
            </div>

            <input type="hidden" data-campo="id">

            <div class="form-group">
                <label class="form-label">¿Qué realizaste? *</label>
                <input type="text" data-campo="descripcion" class="form-control" maxlength="500"
                    placeholder="Ej. Elaboré la cotización para el cliente ABC" required>
            </div>

            <div class="form-group">
                <label class="form-label">Archivo o imagen de sustento</label>
                <input type="file" data-campo="archivo" class="form-control"
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                <small class="form-help">Opcional · máximo 5 MB.</small>
                <div class="archivo-actual" data-archivo-actual></div>
            </div>
        </div>
    </template>

    {{-- PLANTILLA PENDIENTE --}}
    <template id="templatePendiente">
        <div class="item-dinamico pendiente-form-item">
            <div class="item-dinamico__header">
                <strong class="item-titulo">Pendiente</strong>
                <button type="button" class="item-eliminar" data-eliminar-item>Eliminar</button>
            </div>

            <input type="hidden" data-campo="id">

            <div class="form-group">
                <label class="form-label">¿Qué quedó pendiente? *</label>
                <input type="text" data-campo="descripcion" class="form-control" maxlength="500"
                    placeholder="Ej. Terminar cuadro comparativo">
            </div>

            <div class="form-group">
                <label class="form-label">Detalle</label>
                <textarea data-campo="detalle" class="form-textarea" rows="3" maxlength="2000"
                    placeholder="Indica dónde quedó el trabajo, qué falta o qué debe tener en cuenta quien lo continúe."></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Ruta o ubicación de archivos</label>
                <input type="text" data-campo="ruta_archivos" class="form-control" maxlength="500"
                    placeholder="Ej. \\Servidor\Cotizaciones\ClienteABC\ o carpeta compartida">
                <small class="form-help">Opcional. Indica dónde están los archivos necesarios para continuar.</small>
            </div>
        </div>
    </template>
@endsection

@push('scripts')
    <script src="{{ asset('js/pages/reportes-avance.js') }}"></script>

    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                if (document.getElementById('modalCrearReporte')) {
                    window.AppModal.open('modalCrearReporte');
                }
            });
        </script>
    @endif
@endpush
