@extends('layouts.app')

@section('title', 'Solicitudes')
@section('page-title', 'Solicitudes')

@push('styles')
    <link
        rel="stylesheet"
        href="{{ asset('css/pages/solicitudes.css') }}"
    >
@endpush


@section('content')

    {{-- ==========================================================
        MENSAJES
    ========================================================== --}}

    @if(session('success'))
        <div class="alert alert--success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert--danger">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())

        <div class="alert alert--danger">

            <strong>
                Revisa la información ingresada.
            </strong>

            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>

        </div>

    @endif



    {{-- ==========================================================
        CABECERA
    ========================================================== --}}

    <div class="page-header">

        <div>

            <h2 class="page-header__title">
                Solicitudes y justificaciones
            </h2>

            <p class="page-header__description">

                @if($esAdministrador)

                    Revisa y gestiona las solicitudes
                    presentadas por los trabajadores.

                @else

                    Registra permisos, justificaciones,
                    tardanzas u omisiones de marcación.

                @endif

            </p>

        </div>


        @unless($esAdministrador)

            <div class="page-header__actions">

                <button
                    type="button"
                    class="button button--primary"
                    data-modal-open="modalCrearSolicitud"
                >
                    + Nueva solicitud
                </button>

            </div>

        @endunless

    </div>



    {{-- ==========================================================
        FILTROS
    ========================================================== --}}

    <section class="panel">

        <form
            method="GET"
            action="{{ route('solicitudes.index') }}"
            class="solicitudes-filtros"
        >

            <div class="form-group">

                <label class="form-label">
                    Buscar
                </label>

                <input
                    type="text"
                    name="buscar"
                    value="{{ request('buscar') }}"
                    class="form-control"
                    placeholder="{{
                        $esAdministrador
                            ? 'Trabajador, DNI o motivo...'
                            : 'Buscar por motivo...'
                    }}"
                >

            </div>


            <div class="form-group">

                <label class="form-label">
                    Tipo
                </label>

                <select
                    name="tipo"
                    class="form-select"
                >

                    <option value="">
                        Todos
                    </option>

                    <option
                        value="TARDANZA"
                        @selected(request('tipo') === 'TARDANZA')
                    >
                        Tardanza
                    </option>

                    <option
                        value="FALTA"
                        @selected(request('tipo') === 'FALTA')
                    >
                        Falta
                    </option>

                    <option
                        value="PERMISO"
                        @selected(request('tipo') === 'PERMISO')
                    >
                        Permiso
                    </option>

                    <option
                        value="OMISION_MARCACION"
                        @selected(request('tipo') === 'OMISION_MARCACION')
                    >
                        Omisión de marcación
                    </option>

                    <option
                        value="OTRO"
                        @selected(request('tipo') === 'OTRO')
                    >
                        Otro
                    </option>

                </select>

            </div>


            <div class="form-group">

                <label class="form-label">
                    Estado
                </label>

                <select
                    name="estado"
                    class="form-select"
                >

                    <option value="">
                        Todos
                    </option>

                    <option
                        value="PENDIENTE"
                        @selected(request('estado') === 'PENDIENTE')
                    >
                        Pendiente
                    </option>

                    <option
                        value="APROBADA"
                        @selected(request('estado') === 'APROBADA')
                    >
                        Aprobada
                    </option>

                    <option
                        value="RECHAZADA"
                        @selected(request('estado') === 'RECHAZADA')
                    >
                        Rechazada
                    </option>

                    <option
                        value="CANCELADA"
                        @selected(request('estado') === 'CANCELADA')
                    >
                        Cancelada
                    </option>

                </select>

            </div>


            <div class="form-group">

                <label class="form-label">
                    Fecha
                </label>

                <input
                    type="date"
                    name="fecha"
                    value="{{ request('fecha') }}"
                    class="form-control"
                >

            </div>


            <div class="solicitudes-filtros__actions">

                <button
                    type="submit"
                    class="button button--secondary"
                >
                    Filtrar
                </button>

                <a
                    href="{{ route('solicitudes.index') }}"
                    class="button button--secondary"
                >
                    Limpiar
                </a>

            </div>

        </form>

    </section>



    {{-- ==========================================================
        LISTADO
    ========================================================== --}}

    <section class="solicitudes-list">

        @forelse($solicitudes as $solicitud)

            <article class="solicitud-card">

                <div class="solicitud-card__header">

                    <div>

                        <div class="solicitud-card__tipo">

                            <span
                                class="solicitud-tipo solicitud-tipo--{{
                                    strtolower(
                                        str_replace(
                                            '_',
                                            '-',
                                            $solicitud->tipo
                                        )
                                    )
                                }}"
                            >
                                {{
                                    match($solicitud->tipo) {
                                        'TARDANZA' => 'Tardanza',
                                        'FALTA' => 'Falta',
                                        'PERMISO' => 'Permiso',
                                        'OMISION_MARCACION' => 'Omisión de marcación',
                                        default => 'Otro',
                                    }
                                }}
                            </span>


                            <span
                                class="solicitud-estado solicitud-estado--{{
                                    strtolower($solicitud->estado)
                                }}"
                            >
                                {{
                                    ucfirst(
                                        strtolower(
                                            $solicitud->estado
                                        )
                                    )
                                }}
                            </span>

                        </div>


                        @if($esAdministrador)

                            <h3 class="solicitud-card__trabajador">

                                {{
                                    $solicitud
                                        ->trabajador
                                        ?->nombres
                                }}

                                {{
                                    $solicitud
                                        ->trabajador
                                        ?->apellidos
                                }}

                            </h3>

                            <span class="solicitud-card__dni">
                                DNI:
                                {{
                                    $solicitud
                                        ->trabajador
                                        ?->dni
                                    ?? '-'
                                }}
                                ·
                                {{
                                    $solicitud
                                        ->trabajador
                                        ?->area
                                        ?->nombre
                                    ?? 'Sin área'
                                }}
                            </span>

                        @endif

                    </div>


                    <div class="solicitud-card__fecha-registro">

                        <span>
                            Registrada
                        </span>

                        <strong>
                            {{
                                $solicitud
                                    ->created_at
                                    ?->format('d/m/Y H:i')
                            }}
                        </strong>

                    </div>

                </div>



                <div class="solicitud-card__body">

                    <div class="solicitud-info">

                        <span class="solicitud-info__label">
                            Fecha
                        </span>

                        <strong>

                            {{
                                $solicitud
                                    ->fecha_inicio
                                    ?->format('d/m/Y')
                            }}

                            @if(
                                $solicitud->fecha_fin
                                &&
                                !$solicitud
                                    ->fecha_fin
                                    ->equalTo(
                                        $solicitud->fecha_inicio
                                    )
                            )

                                al

                                {{
                                    $solicitud
                                        ->fecha_fin
                                        ->format('d/m/Y')
                                }}

                            @endif

                        </strong>

                    </div>


                    @if(
                        $solicitud->hora_inicio
                        ||
                        $solicitud->hora_fin
                    )

                        <div class="solicitud-info">

                            <span class="solicitud-info__label">
                                Horario solicitado
                            </span>

                            <strong>
                                {{
                                    $solicitud->hora_inicio
                                    ? substr(
                                        $solicitud->hora_inicio,
                                        0,
                                        5
                                    )
                                    : '—'
                                }}

                                -

                                {{
                                    $solicitud->hora_fin
                                    ? substr(
                                        $solicitud->hora_fin,
                                        0,
                                        5
                                    )
                                    : '—'
                                }}
                            </strong>

                        </div>

                    @endif


                    <div class="solicitud-info solicitud-info--full">

                        <span class="solicitud-info__label">
                            Motivo
                        </span>

                        <p>
                            {{ $solicitud->motivo }}
                        </p>

                    </div>


                    @if($solicitud->archivo)

                        <div class="solicitud-info solicitud-info--full">

                            <span class="solicitud-info__label">
                                Sustento
                            </span>

                            <a
                                href="{{ Storage::url($solicitud->archivo) }}"
                                target="_blank"
                                rel="noopener"
                                class="solicitud-archivo"
                            >
                                Ver archivo adjunto
                            </a>

                        </div>

                    @endif


                    @if($solicitud->asistencia_id)

                        <div class="solicitud-info">

                            <span class="solicitud-info__label">
                                Asistencia relacionada
                            </span>

                            <strong>
                                #{{ $solicitud->asistencia_id }}
                            </strong>

                        </div>

                    @endif

                </div>



                @if(
                    $solicitud->estado === 'APROBADA'
                    ||
                    $solicitud->estado === 'RECHAZADA'
                )

                    <div class="solicitud-card__revision">

                        <div>

                            <span>
                                Revisada por
                            </span>

                            <strong>
                                {{
                                    $solicitud
                                        ->revisadoPor
                                        ?->name
                                    ??
                                    $solicitud
                                        ->revisadoPor
                                        ?->email
                                    ??
                                    'Administrador'
                                }}
                            </strong>

                        </div>


                        <div>

                            <span>
                                Fecha de revisión
                            </span>

                            <strong>
                                {{
                                    $solicitud
                                        ->fecha_revision
                                        ?->format('d/m/Y H:i')
                                    ?? '-'
                                }}
                            </strong>

                        </div>


                        @if($solicitud->comentario_revision)

                            <div class="solicitud-card__comentario">

                                <span>
                                    Comentario
                                </span>

                                <p>
                                    {{
                                        $solicitud
                                            ->comentario_revision
                                    }}
                                </p>

                            </div>

                        @endif

                    </div>

                @endif



                <div class="solicitud-card__footer">

                    @if(
                        $esAdministrador
                        &&
                        $solicitud->estado === 'PENDIENTE'
                    )

                        <button
                            type="button"
                            class="button button--primary button--small"

                            data-revisar-solicitud

                            data-url="{{
                                route(
                                    'solicitudes.revisar',
                                    $solicitud
                                )
                            }}"

                            data-trabajador="{{
                                $solicitud
                                    ->trabajador
                                    ?->nombres
                            }}
                            {{
                                $solicitud
                                    ->trabajador
                                    ?->apellidos
                            }}"

                            data-tipo="{{ $solicitud->tipo }}"
                        >
                            Revisar
                        </button>

                    @elseif(
                        !$esAdministrador
                        &&
                        $solicitud->estado === 'PENDIENTE'
                    )

                        <button
                            type="button"
                            class="button button--secondary button--small"

                            data-editar-solicitud

                            data-url="{{
                                route(
                                    'solicitudes.update',
                                    $solicitud
                                )
                            }}"

                            data-tipo="{{
                                $solicitud->tipo
                            }}"

                            data-inicio="{{
                                $solicitud
                                    ->fecha_inicio
                                    ?->format('Y-m-d')
                            }}"

                            data-fin="{{
                                $solicitud
                                    ->fecha_fin
                                    ?->format('Y-m-d')
                            }}"

                            data-hora-inicio="{{
                                $solicitud->hora_inicio
                                ? substr(
                                    $solicitud->hora_inicio,
                                    0,
                                    5
                                )
                                : ''
                            }}"

                            data-hora-fin="{{
                                $solicitud->hora_fin
                                ? substr(
                                    $solicitud->hora_fin,
                                    0,
                                    5
                                )
                                : ''
                            }}"

                            data-motivo="{{
                                $solicitud->motivo
                            }}"
                        >
                            Editar
                        </button>


                        <button
                            type="button"
                            class="button button--danger button--small"

                            data-cancelar-solicitud

                            data-url="{{
                                route(
                                    'solicitudes.cancelar',
                                    $solicitud
                                )
                            }}"
                        >
                            Cancelar
                        </button>

                    @endif

                </div>

            </article>

        @empty

            <div class="empty-state">

                <strong>
                    No existen solicitudes
                </strong>

                <span>

                    @if($esAdministrador)

                        No hay solicitudes para revisar.

                    @else

                        Todavía no has registrado solicitudes.

                    @endif

                </span>

            </div>

        @endforelse

    </section>


    <div class="pagination-container">
        {{ $solicitudes->links() }}
    </div>



    {{-- ==========================================================
        CREAR SOLICITUD
    ========================================================== --}}

    @unless($esAdministrador)

        <x-modal
            id="modalCrearSolicitud"
            title="Nueva solicitud"
        >

            <form
                method="POST"
                action="{{ route('solicitudes.store') }}"
                enctype="multipart/form-data"
                class="form"
            >

                @csrf


                <div class="form-group">

                    <label class="form-label">
                        Tipo *
                    </label>

                    <select
                        name="tipo"
                        class="form-select"
                        required
                    >

                        <option value="">
                            Seleccione
                        </option>

                        <option value="TARDANZA">
                            Justificación de tardanza
                        </option>

                        <option value="FALTA">
                            Justificación de falta
                        </option>

                        <option value="PERMISO">
                            Permiso
                        </option>

                        <option value="OMISION_MARCACION">
                            Omisión de marcación
                        </option>

                        <option value="OTRO">
                            Otro
                        </option>

                    </select>

                </div>


                <div class="form-grid form-grid--2">

                    <div class="form-group">

                        <label class="form-label">
                            Fecha de inicio *
                        </label>

                        <input
                            type="date"
                            name="fecha_inicio"
                            class="form-control"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label class="form-label">
                            Fecha de fin
                        </label>

                        <input
                            type="date"
                            name="fecha_fin"
                            class="form-control"
                        >

                    </div>

                </div>


                <div class="form-grid form-grid--2">

                    <div class="form-group">

                        <label class="form-label">
                            Hora de inicio
                        </label>

                        <input
                            type="time"
                            name="hora_inicio"
                            class="form-control"
                        >

                    </div>


                    <div class="form-group">

                        <label class="form-label">
                            Hora de fin
                        </label>

                        <input
                            type="time"
                            name="hora_fin"
                            class="form-control"
                        >

                    </div>

                </div>


                <div class="form-group">

                    <label class="form-label">
                        Motivo *
                    </label>

                    <textarea
                        name="motivo"
                        class="form-textarea"
                        rows="4"
                        maxlength="2000"
                        required
                        placeholder="Describe el motivo de tu solicitud..."
                    ></textarea>

                </div>


                <div class="form-group">

                    <label class="form-label">
                        Sustento
                    </label>

                    <input
                        type="file"
                        name="archivo"
                        class="form-control"
                        accept=".pdf,.jpg,.jpeg,.png"
                    >

                    <small class="form-help">
                        PDF, JPG o PNG. Máximo 5 MB.
                    </small>

                </div>


                <div class="modal__actions">

                    <button
                        type="button"
                        class="button button--secondary"
                        data-modal-close
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="button button--primary"
                    >
                        Enviar solicitud
                    </button>

                </div>

            </form>

        </x-modal>



        {{-- EDITAR --}}
        <x-modal
            id="modalEditarSolicitud"
            title="Editar solicitud"
        >

            <form
                method="POST"
                id="formEditarSolicitud"
                enctype="multipart/form-data"
                class="form"
            >

                @csrf
                @method('PUT')


                <div class="form-group">

                    <label class="form-label">
                        Tipo *
                    </label>

                    <select
                        name="tipo"
                        id="editarSolicitudTipo"
                        class="form-select"
                        required
                    >

                        <option value="TARDANZA">
                            Justificación de tardanza
                        </option>

                        <option value="FALTA">
                            Justificación de falta
                        </option>

                        <option value="PERMISO">
                            Permiso
                        </option>

                        <option value="OMISION_MARCACION">
                            Omisión de marcación
                        </option>

                        <option value="OTRO">
                            Otro
                        </option>

                    </select>

                </div>


                <div class="form-grid form-grid--2">

                    <div class="form-group">

                        <label class="form-label">
                            Fecha de inicio *
                        </label>

                        <input
                            type="date"
                            name="fecha_inicio"
                            id="editarSolicitudInicio"
                            class="form-control"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label class="form-label">
                            Fecha de fin
                        </label>

                        <input
                            type="date"
                            name="fecha_fin"
                            id="editarSolicitudFin"
                            class="form-control"
                        >

                    </div>

                </div>


                <div class="form-grid form-grid--2">

                    <div class="form-group">

                        <label class="form-label">
                            Hora de inicio
                        </label>

                        <input
                            type="time"
                            name="hora_inicio"
                            id="editarSolicitudHoraInicio"
                            class="form-control"
                        >

                    </div>


                    <div class="form-group">

                        <label class="form-label">
                            Hora de fin
                        </label>

                        <input
                            type="time"
                            name="hora_fin"
                            id="editarSolicitudHoraFin"
                            class="form-control"
                        >

                    </div>

                </div>


                <div class="form-group">

                    <label class="form-label">
                        Motivo *
                    </label>

                    <textarea
                        name="motivo"
                        id="editarSolicitudMotivo"
                        class="form-textarea"
                        rows="4"
                        maxlength="2000"
                        required
                    ></textarea>

                </div>


                <div class="form-group">

                    <label class="form-label">
                        Reemplazar sustento
                    </label>

                    <input
                        type="file"
                        name="archivo"
                        class="form-control"
                        accept=".pdf,.jpg,.jpeg,.png"
                    >

                </div>


                <div class="modal__actions">

                    <button
                        type="button"
                        class="button button--secondary"
                        data-modal-close
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="button button--primary"
                    >
                        Guardar cambios
                    </button>

                </div>

            </form>

        </x-modal>



        {{-- CANCELAR --}}
        <x-modal
            id="modalCancelarSolicitud"
            title="Cancelar solicitud"
            size="small"
        >

            <form
                method="POST"
                id="formCancelarSolicitud"
            >

                @csrf
                @method('PATCH')


                <p>
                    ¿Seguro que deseas cancelar esta solicitud?
                </p>


                <div class="modal__actions">

                    <button
                        type="button"
                        class="button button--secondary"
                        data-modal-close
                    >
                        Volver
                    </button>

                    <button
                        type="submit"
                        class="button button--danger"
                    >
                        Cancelar solicitud
                    </button>

                </div>

            </form>

        </x-modal>

    @endunless



    {{-- ==========================================================
        REVISAR - ADMIN
    ========================================================== --}}

    @if($esAdministrador)

        <x-modal
            id="modalRevisarSolicitud"
            title="Revisar solicitud"
        >

            <form
                method="POST"
                id="formRevisarSolicitud"
                class="form"
            >

                @csrf
                @method('PATCH')


                <div class="solicitud-revision-resumen">

                    <span>
                        Solicitud de
                    </span>

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

                    <select
                        name="decision"
                        id="revisionDecision"
                        class="form-select"
                        required
                    >

                        <option value="">
                            Seleccione
                        </option>

                        <option value="APROBADA">
                            Aprobar
                        </option>

                        <option value="RECHAZADA">
                            Rechazar
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label class="form-label">
                        Comentario de revisión
                    </label>

                    <textarea
                        name="comentario_revision"
                        class="form-textarea"
                        rows="4"
                        maxlength="500"
                        placeholder="Indique observaciones o motivo del rechazo..."
                    ></textarea>

                    <small class="form-help">
                        El comentario es obligatorio al rechazar.
                    </small>

                </div>


                <div class="modal__actions">

                    <button
                        type="button"
                        class="button button--secondary"
                        data-modal-close
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="button button--primary"
                    >
                        Guardar decisión
                    </button>

                </div>

            </form>

        </x-modal>

    @endif

@endsection


@push('scripts')
    <script
        src="{{ asset('js/pages/solicitudes.js') }}"
    ></script>
@endpush