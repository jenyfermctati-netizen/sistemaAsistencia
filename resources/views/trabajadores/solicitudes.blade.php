@extends('layouts.app')

@section('title', 'Solicitudes')
@section('page-title', 'Solicitudes')
@section('page-subtitle', $esAdministrador ? 'Gestión de solicitudes del personal' : 'Mis solicitudes y justificaciones')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/solicitudes.css') }}">
@endpush

@section('content')

<div class="solicitudes-page">

    {{-- CABECERA --}}
    <header class="solicitudes-header">
        <div>
            <h2>
                {{ $esAdministrador ? 'Solicitudes del personal' : 'Mis solicitudes' }}
            </h2>

            <p>
                @if($esAdministrador)
                    Revisa permisos, justificaciones, tardanzas y omisiones de marcación.
                @else
                    Consulta el estado de tus solicitudes o registra una nueva.
                @endif
            </p>
        </div>

        @unless($esAdministrador)
            <button type="button"
                class="button button--primary"
                data-modal-open="modalCrearSolicitud">
                + Nueva solicitud
            </button>
        @endunless
    </header>

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

            <label class="solicitudes-field solicitudes-field--search">
                <span>Buscar</span>

                <input type="text"
                    name="buscar"
                    value="{{ request('buscar') }}"
                    class="form-control"
                    placeholder="{{ $esAdministrador ? 'Trabajador, DNI o motivo...' : 'Tipo o motivo...' }}">
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
                    <option value="">Todos</option>
                    <option value="PENDIENTE" @selected(request('estado') === 'PENDIENTE')>Pendiente</option>
                    <option value="APROBADA" @selected(request('estado') === 'APROBADA')>Aprobada</option>
                    <option value="RECHAZADA" @selected(request('estado') === 'RECHAZADA')>Rechazada</option>
                    <option value="CANCELADA" @selected(request('estado') === 'CANCELADA')>Cancelada</option>
                </select>
            </label>

            <label class="solicitudes-field">
                <span>Fecha</span>

                <input type="date"
                    name="fecha"
                    value="{{ request('fecha') }}"
                    class="form-control">
            </label>

            <div class="solicitudes-filtros__actions">
                <button type="submit" class="button button--primary">
                    Filtrar
                </button>

                <a href="{{ route('solicitudes.index') }}"
                    class="button button--secondary">
                    Limpiar
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
                        {{ $esAdministrador ? 'Solicitudes recibidas' : 'Historial de solicitudes' }}
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

                                data-revisar-url="{{ $esAdministrador && $solicitud->estado === 'PENDIENTE'
                                    ? route('solicitudes.revisar', $solicitud)
                                    : '' }}"

                                data-editar-url="{{ !$esAdministrador && $solicitud->estado === 'PENDIENTE'
                                    ? route('solicitudes.update', $solicitud)
                                    : '' }}"

                                data-cancelar-url="{{ !$esAdministrador && $solicitud->estado === 'PENDIENTE'
                                    ? route('solicitudes.cancelar', $solicitud)
                                    : '' }}">

                                {{-- TRABAJADOR --}}
                                <td data-label="Nombre">

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
                                <td class="solicitud-table-action" data-label="Detalle">

                                    <button type="button"
                                        class="solicitud-open"
                                        data-ver-solicitud-button
                                        aria-label="Ver detalle">
                                        ›
                                    </button>

                                </td>

                            </tr>

                        @empty

                            <tr class="solicitudes-empty-row">
                                <td colspan="5">

                                    <div class="solicitudes-empty">

                                        <strong>No existen solicitudes</strong>

                                        <span>
                                            @if($esAdministrador)
                                                No hay solicitudes registradas.
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
    @unless($esAdministrador)

        <x-modal id="modalCrearSolicitud"
            title="Nueva solicitud"
            size="large">

            <form method="POST"
                action="{{ route('solicitudes.store') }}"
                enctype="multipart/form-data"
                class="form"
                data-live-form
                data-live-target="solicitudes"
                data-reset-on-success>

                @csrf

                <div class="form-group">

                    <label class="form-label">Tipo *</label>

                    <select name="tipo"
                        class="form-select"
                        required>

                        <option value="">Seleccione</option>
                        <option value="TARDANZA">Justificación de tardanza</option>
                        <option value="FALTA">Justificación de falta</option>
                        <option value="PERMISO">Permiso</option>
                        <option value="OMISION_MARCACION">Omisión de marcación</option>
                        <option value="OTRO">Otro</option>

                    </select>

                </div>

                <div class="form-grid form-grid--2">

                    <div class="form-group">

                        <label class="form-label">
                            Fecha de inicio *
                        </label>

                        <input type="date"
                            name="fecha_inicio"
                            class="form-control"
                            required>

                    </div>

                    <div class="form-group">

                        <label class="form-label">
                            Fecha de fin
                        </label>

                        <input type="date"
                            name="fecha_fin"
                            class="form-control">

                    </div>

                </div>

                <div class="form-grid form-grid--2">

                    <div class="form-group">

                        <label class="form-label">
                            Hora de inicio
                        </label>

                        <input type="time"
                            name="hora_inicio"
                            class="form-control">

                    </div>

                    <div class="form-group">

                        <label class="form-label">
                            Hora de fin
                        </label>

                        <input type="time"
                            name="hora_fin"
                            class="form-control">

                    </div>

                </div>

                <div class="form-group">

                    <label class="form-label">
                        Motivo *
                    </label>

                    <textarea name="motivo"
                        class="form-textarea"
                        rows="5"
                        maxlength="2000"
                        required
                        placeholder="Describe el motivo de tu solicitud..."></textarea>

                </div>

                <div class="form-group">

                    <label class="form-label">
                        Sustento
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
                        class="form-select"
                        required>

                        <option value="TARDANZA">Justificación de tardanza</option>
                        <option value="FALTA">Justificación de falta</option>
                        <option value="PERMISO">Permiso</option>
                        <option value="OMISION_MARCACION">Omisión de marcación</option>
                        <option value="OTRO">Otro</option>

                    </select>

                </div>

                <div class="form-grid form-grid--2">

                    <div class="form-group">

                        <label class="form-label">
                            Fecha de inicio *
                        </label>

                        <input type="date"
                            name="fecha_inicio"
                            id="editarSolicitudInicio"
                            class="form-control"
                            required>

                    </div>

                    <div class="form-group">

                        <label class="form-label">
                            Fecha de fin
                        </label>

                        <input type="date"
                            name="fecha_fin"
                            id="editarSolicitudFin"
                            class="form-control">

                    </div>

                </div>

                <div class="form-grid form-grid--2">

                    <div class="form-group">

                        <label class="form-label">
                            Hora de inicio
                        </label>

                        <input type="time"
                            name="hora_inicio"
                            id="editarSolicitudHoraInicio"
                            class="form-control">

                    </div>

                    <div class="form-group">

                        <label class="form-label">
                            Hora de fin
                        </label>

                        <input type="time"
                            name="hora_fin"
                            id="editarSolicitudHoraFin"
                            class="form-control">

                    </div>

                </div>

                <div class="form-group">

                    <label class="form-label">
                        Motivo *
                    </label>

                    <textarea name="motivo"
                        id="editarSolicitudMotivo"
                        class="form-textarea"
                        rows="5"
                        maxlength="2000"
                        required></textarea>

                </div>

                <div class="form-group">

                    <label class="form-label">
                        Reemplazar sustento
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

    @endunless

    {{-- REVISAR ADMIN --}}
    @if($esAdministrador)

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
