@extends('layouts.app')

@section('title', 'Solicitudes')
@section('page-title', 'Solicitudes')
@section('page-subtitle', $puedeGestionar ? 'Gestión de solicitudes del personal' : 'Mis solicitudes y justificaciones')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/solicitudes.css') }}">
@endpush

@section('content')

<div class="solicitudes-page">

    <header class="solicitudes-header">
        <div class="solicitudes-header__copy">
            <h2>
                {{ $puedeGestionar ? ($seccion === 'mis' ? 'Mis solicitudes' : 'Bandeja de entrada') : 'Mis solicitudes' }}
            </h2>

            <p>
                @if($puedeGestionar)
                    {{ $seccion === 'mis' ? 'Consulta y administra las solicitudes que registraste.' : 'Revisa y responde las solicitudes pendientes del personal.' }}
                @else
                    Consulta el estado de tus solicitudes o registra una nueva.
                @endif
            </p>
        </div>

        <div class="solicitudes-header__actions">
        @if($puedeSolicitar && (!$puedeGestionar || $seccion === 'mis'))
            <button type="button"
                class="button button--primary"
                data-modal-open="modalCrearSolicitud">
                <x-ui-icon name="plus" /> Nueva solicitud
            </button>
        @endif
        @php
            $filtrosDescarga = request()->only(['seccion', 'buscar', 'tipo', 'estado', 'fecha', 'fecha_desde', 'fecha_hasta']);
        @endphp
        <x-download-menu
            :excel="route('solicitudes.index', $filtrosDescarga + ['descargar' => 'xlsx'])"
            :pdf="route('solicitudes.index', $filtrosDescarga + ['descargar' => 'pdf'])" />
        </div>
    </header>

    @unless(strtoupper(trim((string) auth()->user()?->rol?->nombre)) === 'TRABAJADOR')
    {{-- MENSAJES --}}
    @if(session('success'))
        <div class="solicitudes-alert solicitudes-alert--success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="solicitudes-alert solicitudes-alert--error">
            {{ session('error') }}
        </div>
    @endif

@endunless

        @if($puedeGestionar)
        <nav class="solicitudes-tabs" aria-label="Secciones de solicitudes">
            <a href="{{ route('solicitudes.index', ['seccion' => 'mis']) }}"
                class="solicitudes-tabs__link {{ $seccion === 'mis' ? 'is-active' : '' }}"
                @if($seccion === 'mis') aria-current="page" @endif>
                Mis Solicitudes
                <span>{{ $misSolicitudesCount }}</span>
            </a>
            <a href="{{ route('solicitudes.index', ['seccion' => 'bandeja']) }}"
                class="solicitudes-tabs__link {{ $seccion === 'bandeja' ? 'is-active' : '' }}"
                @if($seccion === 'bandeja') aria-current="page" @endif>
                Bandeja de entrada
                <span>{{ $bandejaCount }}</span>
            </a>
        </nav>
    @endif

    {{-- FILTROS --}}
    <section class="solicitudes-filter-panel">

        <div class="solicitudes-filter-panel__heading">
            <div>
                <h3>Buscar solicitudes</h3>
                <p>Utiliza los filtros para encontrar rápidamente un registro.</p>
            </div>
        </div>

        <form method="GET"
            action="{{ route('solicitudes.index') }}"
            class="solicitudes-filtros">

            @if($puedeGestionar)
                <input type="hidden" name="seccion" value="{{ $seccion }}">
            @endif

            <label class="solicitudes-field solicitudes-field--search">
                <span>Buscar</span>

                <input type="text"
                    name="buscar"
                    value="{{ request('buscar') }}"
                    class="form-control"
                    placeholder="{{ $puedeGestionar ? 'Trabajador, DNI o motivo...' : 'Tipo o motivo...' }}">
            </label>

            <label class="solicitudes-field">
                <span>Tipo</span>

                <select name="tipo" class="form-select">
                    <option value="">Todos</option>
                    <option value="TARDANZA" @selected(request('tipo') === 'TARDANZA')>Tardanza</option>
                    <option value="FALTA" @selected(request('tipo') === 'FALTA')>Falta</option>
                    <option value="PERMISO" @selected(request('tipo') === 'PERMISO')>Permiso</option>
                    <option value="OMISION_MARCACION" @selected(request('tipo') === 'OMISION_MARCACION')>Omisión de marcación</option>
                    <option value="OTRO" @selected(request('tipo') === 'OTRO')>Otro</option>
                </select>
            </label>

            <label class="solicitudes-field">
                <span>Estado</span>

                <select name="estado" class="form-select">
                    <option value="" @selected($estadoFiltro === '')>Todos</option>
                    <option value="PENDIENTE" @selected($estadoFiltro === 'PENDIENTE')>Pendiente</option>
                    <option value="APROBADA" @selected($estadoFiltro === 'APROBADA')>Aprobada</option>
                    <option value="RECHAZADA" @selected($estadoFiltro === 'RECHAZADA')>Rechazada</option>
                    <option value="CANCELADA" @selected($estadoFiltro === 'CANCELADA')>Cancelada</option>
                </select>
            </label>

            <label class="solicitudes-field">
                <span>Desde</span>
                <input type="date" name="fecha_desde" value="{{ request('fecha_desde', request('fecha')) }}" class="form-control">
            </label>

            <label class="solicitudes-field">
                <span>Hasta</span>
                <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta', request('fecha')) }}" class="form-control">
            </label>

            <div class="solicitudes-filtros__actions">
                <button type="submit" class="button button--primary">
                    <x-ui-icon name="filter" /> Filtrar
                </button>

                <a href="{{ route('solicitudes.index', $puedeGestionar ? ['seccion' => $seccion] : []) }}"
                    class="button button--secondary">
                    <x-ui-icon name="reset" /> Restablecer
                </a>
            </div>

        </form>

    </section>

    {{-- TABLA --}}
    <div data-live-region="solicitudes" data-live-poll="5000">

        <section class="solicitudes-table-section">

            <div class="solicitudes-table-heading">
                <div>
                    <h3>
                        {{ $puedeGestionar ? ($seccion === 'mis' ? 'Solicitudes registradas por ti' : 'Solicitudes por revisar') : 'Historial de solicitudes' }}
                    </h3>

                    <p>
                        Haz clic sobre una solicitud para consultar todos sus detalles.
                    </p>
                </div>

                <span class="solicitudes-count">
                    {{ $solicitudes->total() }}
                </span>
            </div>

            <div class="solicitudes-table-wrap">

                <table class="solicitudes-table">

                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Solicitud</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($solicitudes as $solicitud)

                            @php
                                $tipoTexto = match($solicitud->tipo) {
                                    'TARDANZA' => 'Tardanza',
                                    'FALTA' => 'Falta',
                                    'PERMISO' => 'Permiso',
                                    'OMISION_MARCACION' => 'Omisión de marcación',
                                    default => 'Otro',
                                };

                                $estadoTexto = match($solicitud->estado) {
                                    'PENDIENTE' => 'Pendiente',
                                    'APROBADA' => 'Aprobada',
                                    'RECHAZADA' => 'Rechazada',
                                    'CANCELADA' => 'Cancelada',
                                    default => $solicitud->estado,
                                };

                                $trabajadorNombre = trim(
                                    ($solicitud->trabajador?->nombres ?? '') . ' ' .
                                    ($solicitud->trabajador?->apellidos ?? '')
                                );

                                $archivoUrl = $solicitud->archivo
                                    ? Storage::url($solicitud->archivo)
                                    : '';
                            @endphp

                            <tr class="solicitud-table-row"
                                tabindex="0"
                                role="button"

                                data-ver-solicitud

                                data-id="{{ $solicitud->id }}"

                                data-trabajador="{{ $trabajadorNombre }}"

                                data-dni="{{ $solicitud->trabajador?->dni ?? '-' }}"

                                data-area="{{ $solicitud->trabajador?->area?->nombre ?? 'Sin área' }}"

                                data-tipo="{{ $tipoTexto }}"

                                data-tipo-codigo="{{ $solicitud->tipo }}"

                                data-estado="{{ $estadoTexto }}"

                                data-estado-codigo="{{ $solicitud->estado }}"

                                data-fecha-inicio="{{ $solicitud->fecha_inicio?->format('d/m/Y') }}"

                                data-fecha-fin="{{ $solicitud->fecha_fin?->format('d/m/Y') }}"

                                data-fecha-inicio-edit="{{ $solicitud->fecha_inicio?->format('Y-m-d') }}"

                                data-fecha-fin-edit="{{ $solicitud->fecha_fin?->format('Y-m-d') }}"

                                data-hora-inicio="{{ $solicitud->hora_inicio ? substr($solicitud->hora_inicio, 0, 5) : '' }}"

                                data-hora-fin="{{ $solicitud->hora_fin ? substr($solicitud->hora_fin, 0, 5) : '' }}"

                                data-motivo="{{ $solicitud->motivo }}"

                                data-archivo="{{ $archivoUrl }}"

                                data-archivo-nombre="{{ $solicitud->archivo ? basename($solicitud->archivo) : '' }}"

                                data-revisado-por="{{ $solicitud->revisadoPor?->name ?? ($solicitud->revisadoPor?->email ?? '') }}"

                                data-fecha-revision="{{ $solicitud->fecha_revision?->format('d/m/Y H:i') ?? '' }}"

                                data-comentario="{{ $solicitud->comentario_revision ?? '' }}"

                                data-revisar-url="{{ $puedeGestionar
                                    && $solicitud->trabajador_id !== auth()->user()->trabajador_id
                                    && $solicitud->estado === 'PENDIENTE'
                                    ? route('solicitudes.revisar', $solicitud)
                                    : '' }}"

                                data-editar-url="{{ $solicitud->trabajador_id === auth()->user()->trabajador_id
                                    && $solicitud->estado === 'PENDIENTE'
                                    ? route('solicitudes.update', $solicitud)
                                    : '' }}"

                                data-cancelar-url="{{ $solicitud->trabajador_id === auth()->user()->trabajador_id
                                    && $solicitud->estado === 'PENDIENTE'
                                    ? route('solicitudes.cancelar', $solicitud)
                                    : '' }}">

                                {{-- TRABAJADOR --}}
                                <td data-label="Trabajador">

                                    <div class="solicitud-persona">

                                        <div class="solicitud-persona__avatar">
                                            {{ mb_strtoupper(mb_substr($solicitud->trabajador?->nombres ?? '?', 0, 1)) }}
                                        </div>

                                        <div>
                                            <strong>
                                                {{ $trabajadorNombre ?: 'Sin trabajador' }}
                                            </strong>

                                            <span>
                                                {{ $solicitud->trabajador?->area?->nombre ?? 'Sin área' }}
                                            </span>
                                        </div>

                                    </div>

                                </td>

                                {{-- TIPO --}}
                                <td data-label="Solicitud">

                                    <span class="solicitud-tipo solicitud-tipo--{{ strtolower(str_replace('_', '-', $solicitud->tipo)) }}">
                                        {{ $tipoTexto }}
                                    </span>

                                </td>

                                {{-- FECHA --}}
                                <td data-label="Fecha">

                                    <div class="solicitud-table-date">

                                        <strong>
                                            {{ $solicitud->fecha_inicio?->format('d/m/Y') ?? '-' }}
                                        </strong>

                                        @if(
                                            $solicitud->fecha_fin &&
                                            !$solicitud->fecha_fin->equalTo($solicitud->fecha_inicio)
                                        )

                                            <span>
                                                al {{ $solicitud->fecha_fin->format('d/m/Y') }}
                                            </span>

                                        @endif

                                    </div>

                                </td>

                                {{-- ESTADO --}}
                                <td data-label="Estado">

                                    <span class="solicitud-estado solicitud-estado--{{ strtolower($solicitud->estado) }}">
                                        {{ $estadoTexto }}
                                    </span>

                                </td>

                                {{-- ABRIR --}}
                                <td class="solicitud-table-action" data-label="Acción">

                                    <button type="button"
                                        class="solicitud-open"
                                        data-ver-solicitud-button
                                        aria-label="Ver solicitud de {{ $solicitud->trabajador?->nombres }} {{ $solicitud->trabajador?->apellidos }}"
                                        data-tooltip="Ver solicitud">
                                        <x-ui-icon name="chevron-right" />
                                    </button>

                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="5">

                                    <div class="solicitudes-empty">

                                        <strong>No existen solicitudes</strong>

                                        <span>
                                            @if($puedeGestionar && $seccion === 'bandeja')
                                                No hay solicitudes pendientes de revisión.
                                            @elseif($puedeGestionar)
                                                Aún no has registrado solicitudes.
                                            @else
                                                Todavía no has registrado solicitudes.
                                            @endif
                                        </span>

                                    </div>

                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </section>

        <div class="pagination-container">
            {{ $solicitudes->links() }}
        </div>

    </div>

    {{-- DETALLE COMPARTIDO --}}
    @include('trabajadores.partials.detalle')

    {{-- CREAR SOLICITUD --}}
    @if($puedeSolicitar)

        <x-modal id="modalCrearSolicitud"
            title="Nueva solicitud"
            size="large">

            <form method="POST"
                action="{{ route('solicitudes.store') }}"
                enctype="multipart/form-data"
                class="form"
                data-solicitud-form
                data-live-form
                data-live-target="solicitudes"
                data-reset-on-success>

                @csrf

                <div class="form-group">

                    <label class="form-label">Tipo *</label>

                    <select name="tipo"
                        data-solicitud-tipo
                        class="form-select"
                        required>

                        <option value="">Seleccione</option>
                        <option value="TARDANZA">Justificación de tardanza</option>
                        <option value="FALTA">Justificación de falta</option>
                        <option value="PERMISO">Permiso</option>
                        <option value="OMISION_MARCACION">Omisión de marcación</option>
                        <option value="OTRO">Otro</option>

                    </select>

                    <small class="form-help" data-solicitud-ayuda-tipo aria-live="polite">
                        Selecciona el tipo para ver los datos que necesitas completar.
                    </small>

                </div>

                <div class="form-grid form-grid--2" data-solicitud-fechas hidden>

                    <div class="form-group">

                        <label class="form-label" data-solicitud-fecha-inicio-label>Fecha *</label>

                        <input type="date"
                            name="fecha_inicio"
                            data-solicitud-fecha-inicio
                            value="{{ old('fecha_inicio', now()->toDateString()) }}"
                            class="form-control"
                            required>

                    </div>

                    <div class="form-group" data-solicitud-fecha-fin-campo>

                        <label class="form-label" data-solicitud-fecha-fin-label>Fecha de fin</label>

                        <input type="date"
                            name="fecha_fin"
                            data-solicitud-fecha-fin
                            value="{{ old('fecha_fin', now()->toDateString()) }}"
                            class="form-control">

                        <small class="form-help" data-solicitud-ayuda-fechas></small>

                    </div>

                </div>

                <div class="form-group" data-solicitud-campos-comunes hidden>

                    <label class="form-label">
                        Motivo *
                    </label>

                    <textarea name="motivo"
                        data-solicitud-motivo
                        class="form-textarea"
                        rows="5"
                        maxlength="2000"
                        required
                        placeholder="Describe el motivo de tu solicitud..."></textarea>

                </div>

                <div class="form-group" data-solicitud-campos-comunes hidden>

                    <label class="form-label">
                        Sustento (opcional)
                    </label>

                    <input type="file"
                        name="archivo"
                        class="form-control"
                        accept=".pdf,.jpg,.jpeg,.png">

                    <small class="form-help">
                        PDF, JPG o PNG. Máximo 5 MB.
                    </small>

                </div>

                <div class="modal__actions">

                    <button type="button"
                        class="button button--secondary"
                        data-modal-close>
                        Cancelar
                    </button>

                    <button type="submit"
                        class="button button--primary">
                        Enviar solicitud
                    </button>

                </div>

            </form>

        </x-modal>

        {{-- EDITAR SOLICITUD --}}
        <x-modal id="modalEditarSolicitud"
            title="Editar solicitud"
            size="large">

            <form method="POST"
                id="formEditarSolicitud"
                enctype="multipart/form-data"
                class="form"
                data-solicitud-form
                data-live-form
                data-live-target="solicitudes">

                @csrf
                @method('PUT')

                <div class="form-group">

                    <label class="form-label">
                        Tipo *
                    </label>

                    <select name="tipo"
                        id="editarSolicitudTipo"
                        data-solicitud-tipo
                        class="form-select"
                        required>

                        <option value="TARDANZA">Justificación de tardanza</option>
                        <option value="FALTA">Justificación de falta</option>
                        <option value="PERMISO">Permiso</option>
                        <option value="OMISION_MARCACION">Omisión de marcación</option>
                        <option value="OTRO">Otro</option>

                    </select>

                    <small class="form-help" data-solicitud-ayuda-tipo aria-live="polite">
                        Los campos se ajustan al tipo de solicitud.
                    </small>

                </div>

                <div class="form-grid form-grid--2" data-solicitud-fechas hidden>

                    <div class="form-group">

                        <label class="form-label" data-solicitud-fecha-inicio-label>Fecha *</label>

                        <input type="date"
                            name="fecha_inicio"
                            id="editarSolicitudInicio"
                            data-solicitud-fecha-inicio
                            class="form-control"
                            required>

                    </div>

                    <div class="form-group" data-solicitud-fecha-fin-campo>

                        <label class="form-label" data-solicitud-fecha-fin-label>Fecha de fin</label>

                        <input type="date"
                            name="fecha_fin"
                            id="editarSolicitudFin"
                            data-solicitud-fecha-fin
                            class="form-control">

                        <small class="form-help" data-solicitud-ayuda-fechas></small>

                    </div>

                </div>

                <div class="form-group" data-solicitud-campos-comunes hidden>

                    <label class="form-label">
                        Motivo *
                    </label>

                    <textarea name="motivo"
                        id="editarSolicitudMotivo"
                        data-solicitud-motivo
                        class="form-textarea"
                        rows="5"
                        maxlength="2000"
                        required></textarea>

                </div>

                <div class="form-group" data-solicitud-campos-comunes hidden>

                    <label class="form-label">
                        Reemplazar sustento (opcional)
                    </label>

                    <input type="file"
                        name="archivo"
                        class="form-control"
                        accept=".pdf,.jpg,.jpeg,.png">

                    <small class="form-help">
                        Déjalo vacío para conservar el archivo actual.
                    </small>

                </div>

                <div class="modal__actions">

                    <button type="button"
                        class="button button--secondary"
                        data-modal-close>
                        Cancelar
                    </button>

                    <button type="submit"
                        class="button button--primary">
                        Guardar cambios
                    </button>

                </div>

            </form>

        </x-modal>

        {{-- CANCELAR --}}
        <x-modal id="modalCancelarSolicitud"
            title="Cancelar solicitud"
            size="small">

            <form method="POST"
                id="formCancelarSolicitud"
                data-live-form
                data-live-target="solicitudes">

                @csrf
                @method('PATCH')

                <p>
                    ¿Seguro que deseas cancelar esta solicitud?
                </p>

                <div class="modal__actions">

                    <button type="button"
                        class="button button--secondary"
                        data-modal-close>
                        Volver
                    </button>

                    <button type="submit"
                        class="button button--danger">
                        Cancelar solicitud
                    </button>

                </div>

            </form>

        </x-modal>

    @endif

    {{-- REVISAR ADMIN --}}
    @if($puedeGestionar)

        <x-modal id="modalRevisarSolicitud"
            title="Revisar solicitud">

            <form method="POST"
                id="formRevisarSolicitud"
                class="form"
                data-live-form
                data-live-target="solicitudes">

                @csrf
                @method('PATCH')

                <div class="solicitud-revision-resumen">

                    <span>Solicitud de</span>

                    <strong id="revisionTrabajador">
                        -
                    </strong>

                    <small id="revisionTipo">
                        -
                    </small>

                </div>

                <div class="form-group">

                    <label class="form-label">
                        Decisión *
                    </label>

                    <select name="decision"
                        id="revisionDecision"
                        class="form-select"
                        required>

                        <option value="">Seleccione</option>
                        <option value="APROBADA">Aprobar</option>
                        <option value="RECHAZADA">Rechazar</option>

                    </select>

                </div>

                <div class="form-group">

                    <label class="form-label">
                        Comentario de revisión
                    </label>

                    <textarea name="comentario_revision"
                        class="form-textarea"
                        rows="4"
                        maxlength="500"
                        placeholder="Indique observaciones o motivo del rechazo..."></textarea>

                    <small class="form-help">
                        El comentario es obligatorio al rechazar.
                    </small>

                </div>

                <div class="modal__actions">

                    <button type="button"
                        class="button button--secondary"
                        data-modal-close>
                        Cancelar
                    </button>

                    <button type="submit"
                        class="button button--primary">
                        Guardar decisión
                    </button>

                </div>

            </form>

        </x-modal>

    @endif

</div>

@endsection

@push('scripts')
<script src="{{ asset('js/pages/solicitudes.js') }}"></script>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(location.search);
    const fecha = params.get('regularizar');
    const tipo = params.get('tipo_regularizacion');
    const modal = document.getElementById('modalCrearSolicitud');
    if (!modal || !/^\d{4}-\d{2}-\d{2}$/.test(fecha || '') || !['FALTA', 'OMISION_MARCACION'].includes(tipo)) return;
    const select = modal.querySelector('[name="tipo"]');
    if (select) { select.value = tipo; select.dispatchEvent(new Event('change', {bubbles:true})); }
    modal.querySelectorAll('[name="fecha_inicio"], [name="fecha_fin"]').forEach(input => { input.value = fecha; input.dispatchEvent(new Event('change', {bubbles:true})); });
    window.AppModal?.open('modalCrearSolicitud');
});
</script>
@endpush
