@extends('layouts.app')

@section('title', 'Horarios')
@section('page-title', 'Horarios')

@push('styles')
    <link
        rel="stylesheet"
        href="{{ asset('css/pages/horarios.css') }}"
    >
@endpush


@section('content')

@php
    $diasSemana = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo',
    ];
@endphp


{{-- ============================================================
    MENSAJES
============================================================ --}}

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
        <strong>Revisa la información ingresada.</strong>

        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif



{{-- ============================================================
    ENCABEZADO
============================================================ --}}

<div class="page-header">

    <div>
        <h2 class="page-header__title">
            Gestión de horarios
        </h2>

        <p class="page-header__description">
            Configura los horarios y asígnalos al personal.
        </p>
    </div>


    <div class="page-header__actions">

        <a
            href="{{ route('trabajadores.index') }}"
            class="button button--secondary"
        >
            Volver
        </a>


        <button
            type="button"
            class="button button--secondary"
            data-modal-open="modalAsignarHorario"
        >
            Asignar individual
        </button>


        <button
            type="button"
            class="button button--primary"
            data-modal-open="modalAsignacionMasiva"
        >
            Asignación masiva
        </button>


        <button
            type="button"
            class="button button--primary"
            data-modal-open="modalCrearHorario"
        >
            + Nuevo horario
        </button>

    </div>

</div>



{{-- ============================================================
    FILTROS
============================================================ --}}

<section class="panel">

    <form
        method="GET"
        action="{{ route('trabajadores.horarios') }}"
        class="horarios-filtros"
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
                placeholder="Nombre del horario..."
            >

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
                    value="ACTIVO"
                    @selected(request('estado') === 'ACTIVO')
                >
                    Activos
                </option>

                <option
                    value="INACTIVO"
                    @selected(request('estado') === 'INACTIVO')
                >
                    Inactivos
                </option>

            </select>

        </div>


        <div class="horarios-filtros__actions">

            <button
                type="submit"
                class="button button--secondary"
            >
                Filtrar
            </button>

            <a
                href="{{ route('trabajadores.horarios') }}"
                class="button button--secondary"
            >
                Limpiar
            </a>

        </div>

    </form>

</section>



{{-- ============================================================
    HORARIOS
============================================================ --}}

<div class="horarios-grid">

    @forelse($horarios as $horario)

        @php
            $diasHorario = [];

            foreach ($horario->dias as $dia) {
                $diasHorario[] = [
                    'dia_semana' => (int) $dia->dia_semana,
                    'es_laborable' => (bool) $dia->es_laborable,
                    'hora_entrada' => $dia->hora_entrada
                        ? substr($dia->hora_entrada, 0, 5)
                        : '',
                    'hora_salida' => $dia->hora_salida
                        ? substr($dia->hora_salida, 0, 5)
                        : '',
                    'tolerancia_minutos' =>
                        (int) $dia->tolerancia_minutos,
                ];
            }

            $diasHorarioJson =
                json_encode($diasHorario);
        @endphp


        <article class="horario-card">

            <div class="horario-card__header">

                <div>

                    <div class="horario-card__titulo-linea">

                        <h3>
                            {{ $horario->nombre }}
                        </h3>

                        @if($horario->estado)

                            <span class="badge badge--success">
                                Activo
                            </span>

                        @else

                            <span class="badge badge--secondary">
                                Inactivo
                            </span>

                        @endif

                    </div>

                    <p>
                        {{ $horario->descripcion ?: 'Sin descripción' }}
                    </p>

                </div>

            </div>


            <div class="horario-card__dias">

                @foreach($diasSemana as $numero => $nombre)

                    @php
                        $dia = $horario
                            ->dias
                            ->firstWhere(
                                'dia_semana',
                                $numero
                            );
                    @endphp

                    <div class="horario-dia">

                        <div class="horario-dia__nombre">
                            {{ $nombre }}
                        </div>


                        @if($dia && $dia->es_laborable)

                            <div class="horario-dia__horas">

                                <strong>
                                    {{
                                        substr(
                                            $dia->hora_entrada,
                                            0,
                                            5
                                        )
                                    }}
                                </strong>

                                <span>—</span>

                                <strong>
                                    {{
                                        substr(
                                            $dia->hora_salida,
                                            0,
                                            5
                                        )
                                    }}
                                </strong>

                            </div>

                            <small>
                                Tolerancia:
                                {{
                                    $dia->tolerancia_minutos
                                }}
                                min
                            </small>

                        @else

                            <span class="horario-dia__libre">
                                No laborable
                            </span>

                        @endif

                    </div>

                @endforeach

            </div>


            <div class="horario-card__footer">

                <span class="horario-card__asignados">

                    {{
                        $horario->asignaciones_activas
                    }}

                    trabajador(es) activos

                </span>


                <div class="horario-card__acciones">

                    <button
                        type="button"
                        class="button button--secondary button--small"

                        data-editar-horario

                        data-url="{{
                            route(
                                'trabajadores.horarios.update',
                                $horario
                            )
                        }}"

                        data-nombre="{{ $horario->nombre }}"

                        data-descripcion="{{
                            $horario->descripcion
                        }}"

                        data-dias="{{ e($diasHorarioJson) }}"
                    >
                        Editar
                    </button>


                    <button
                        type="button"
                        class="button button--small
                            {{
                                $horario->estado
                                    ? 'button--danger'
                                    : 'button--secondary'
                            }}"

                        data-cambiar-estado-horario

                        data-url="{{
                            route(
                                'trabajadores.horarios.estado',
                                $horario
                            )
                        }}"

                        data-nombre="{{ $horario->nombre }}"

                        data-activo="{{
                            $horario->estado ? '1' : '0'
                        }}"
                    >

                        {{
                            $horario->estado
                                ? 'Desactivar'
                                : 'Activar'
                        }}

                    </button>

                </div>

            </div>

        </article>


    @empty

        <div class="empty-state">

            <strong>
                No hay horarios registrados
            </strong>

            <span>
                Crea el primer horario para comenzar.
            </span>

        </div>

    @endforelse

</div>


<div class="pagination-container">
    {{ $horarios->links() }}
</div>



{{-- ============================================================
    HISTORIAL
============================================================ --}}

<section class="panel historial-panel">

    <div class="panel__title">

        <div>
            <h3>
                Historial de asignaciones
            </h3>

            <p>
                Registro de los horarios asignados al personal.
            </p>
        </div>

    </div>


    <div class="table-container">

        <table class="table">

            <thead>
                <tr>
                    <th>Trabajador</th>
                    <th>Área</th>
                    <th>Vínculo</th>
                    <th>Horario</th>
                    <th>Control</th>
                    <th>Inicio</th>
                    <th>Fin</th>
                    <th>Estado</th>
                </tr>
            </thead>


            <tbody>

                @forelse($asignaciones as $asignacion)

                    <tr>

                        <td>

                            <strong>
                                {{
                                    $asignacion
                                        ->trabajador
                                        ?->nombres
                                }}

                                {{
                                    $asignacion
                                        ->trabajador
                                        ?->apellidos
                                }}
                            </strong>

                            <small class="table-secondary-text">
                                DNI:
                                {{
                                    $asignacion
                                        ->trabajador
                                        ?->dni
                                    ?? '-'
                                }}
                            </small>

                        </td>


                        <td>
                            {{
                                $asignacion
                                    ->trabajador
                                    ?->area
                                    ?->nombre
                                ?? '-'
                            }}
                        </td>


                        <td>
                            {{
                                $asignacion
                                    ->trabajador
                                    ?->tipo_vinculo
                                ?? '-'
                            }}
                        </td>


                        <td>
                            {{
                                $asignacion
                                    ->horario
                                    ?->nombre
                                ?? '-'
                            }}
                        </td>


                        <td>

                            @if(
                                $asignacion->tipo_control
                                === 'REFERENCIAL'
                            )

                                <span class="control-referencial">
                                    Referencial
                                </span>

                            @else

                                <span class="control-obligatorio">
                                    Obligatorio
                                </span>

                            @endif

                        </td>


                        <td>
                            {{
                                \Carbon\Carbon::parse(
                                    $asignacion->fecha_inicio
                                )->format('d/m/Y')
                            }}
                        </td>


                        <td>

                            @if($asignacion->fecha_fin)

                                {{
                                    \Carbon\Carbon::parse(
                                        $asignacion->fecha_fin
                                    )->format('d/m/Y')
                                }}

                            @else

                                —

                            @endif

                        </td>


                        <td>

                            @if($asignacion->estado)

                                <span class="badge badge--success">
                                    Actual
                                </span>

                            @else

                                <span class="badge badge--secondary">
                                    Histórico
                                </span>

                            @endif

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="8"
                            class="table-empty"
                        >
                            No existen asignaciones registradas.
                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</section>


<div class="pagination-container">
    {{ $asignaciones->links() }}
</div>



{{-- ============================================================
    MODAL CREAR HORARIO
============================================================ --}}

<x-modal
    id="modalCrearHorario"
    title="Nuevo horario"
>

    <form
        method="POST"
        action="{{ route('trabajadores.horarios.store') }}"
        class="form"
    >

        @csrf


        <div class="form-group">

            <label class="form-label">
                Nombre *
            </label>

            <input
                type="text"
                name="nombre"
                class="form-control"
                maxlength="100"
                required
            >

        </div>


        <div class="form-group">

            <label class="form-label">
                Descripción
            </label>

            <textarea
                name="descripcion"
                class="form-textarea"
                maxlength="300"
                rows="2"
            ></textarea>

        </div>


        <div class="dias-editor">

            @foreach($diasSemana as $numero => $nombre)

                <div class="dia-editor">

                    <input
                        type="hidden"
                        name="dias[{{ $loop->index }}][dia_semana]"
                        value="{{ $numero }}"
                    >

                    <input
                        type="hidden"
                        name="dias[{{ $loop->index }}][es_laborable]"
                        value="0"
                    >


                    <label class="dia-editor__check">

                        <input
                            type="checkbox"

                            name="dias[{{ $loop->index }}][es_laborable]"

                            value="1"

                            class="dia-laborable"

                            checked
                        >

                        <strong>
                            {{ $nombre }}
                        </strong>

                    </label>


                    <div class="dia-editor__campos">

                        <input
                            type="time"
                            name="dias[{{ $loop->index }}][hora_entrada]"
                            class="form-control dia-hora"
                            value="08:00"
                        >

                        <span>a</span>

                        <input
                            type="time"
                            name="dias[{{ $loop->index }}][hora_salida]"
                            class="form-control dia-hora"
                            value="17:00"
                        >

                        <input
                            type="number"
                            name="dias[{{ $loop->index }}][tolerancia_minutos]"
                            class="form-control dia-hora"
                            value="0"
                            min="0"
                            max="180"
                            placeholder="Tol."
                        >

                    </div>

                </div>

            @endforeach

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
                Guardar horario
            </button>

        </div>

    </form>

</x-modal>



{{-- ============================================================
    MODAL EDITAR
============================================================ --}}

<x-modal
    id="modalEditarHorario"
    title="Editar horario"
>

    <form
        method="POST"
        id="formEditarHorario"
        class="form"
    >

        @csrf
        @method('PUT')


        <div class="form-group">

            <label class="form-label">
                Nombre *
            </label>

            <input
                type="text"
                name="nombre"
                id="editarHorarioNombre"
                class="form-control"
                required
            >

        </div>


        <div class="form-group">

            <label class="form-label">
                Descripción
            </label>

            <textarea
                name="descripcion"
                id="editarHorarioDescripcion"
                class="form-textarea"
                rows="2"
            ></textarea>

        </div>


        <div
            class="dias-editor"
            id="editarDiasHorario"
        >

            @foreach($diasSemana as $numero => $nombre)

                <div
                    class="dia-editor"
                    data-dia="{{ $numero }}"
                >

                    <input
                        type="hidden"
                        name="dias[{{ $loop->index }}][dia_semana]"
                        value="{{ $numero }}"
                    >

                    <input
                        type="hidden"
                        name="dias[{{ $loop->index }}][es_laborable]"
                        value="0"
                    >


                    <label class="dia-editor__check">

                        <input
                            type="checkbox"

                            name="dias[{{ $loop->index }}][es_laborable]"

                            value="1"

                            class="dia-laborable"
                        >

                        <strong>
                            {{ $nombre }}
                        </strong>

                    </label>


                    <div class="dia-editor__campos">

                        <input
                            type="time"
                            name="dias[{{ $loop->index }}][hora_entrada]"
                            class="form-control hora-entrada dia-hora"
                        >

                        <span>a</span>

                        <input
                            type="time"
                            name="dias[{{ $loop->index }}][hora_salida]"
                            class="form-control hora-salida dia-hora"
                        >

                        <input
                            type="number"
                            name="dias[{{ $loop->index }}][tolerancia_minutos]"
                            class="form-control tolerancia dia-hora"
                            min="0"
                            max="180"
                        >

                    </div>

                </div>

            @endforeach

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



{{-- ============================================================
    ASIGNACIÓN INDIVIDUAL
============================================================ --}}

<x-modal
    id="modalAsignarHorario"
    title="Asignar horario"
>

    <form
        method="POST"
        action="{{ route('trabajadores.horarios.asignar') }}"
        class="form"
    >

        @csrf


        <div class="form-group">

            <label class="form-label">
                Trabajador *
            </label>

            <select
                name="trabajador_id"
                id="asignarTrabajador"
                class="form-select"
                required
            >

                <option value="">
                    Seleccione
                </option>

                @foreach($trabajadores as $trabajador)

                    <option
                        value="{{ $trabajador->id }}"
                        data-vinculo="{{ $trabajador->tipo_vinculo }}"
                    >
                        {{
                            $trabajador->apellidos
                        }},
                        {{
                            $trabajador->nombres
                        }}
                        -
                        {{
                            $trabajador->tipo_vinculo
                        }}
                    </option>

                @endforeach

            </select>

        </div>


        <div
            class="tipo-control-preview"
            id="tipoControlPreview"
            hidden
        ></div>


        <div class="form-group">

            <label class="form-label">
                Horario *
            </label>

            <select
                name="horario_id"
                class="form-select"
                required
            >

                <option value="">
                    Seleccione
                </option>

                @foreach($horariosActivos as $horario)

                    <option value="{{ $horario->id }}">
                        {{ $horario->nombre }}
                    </option>

                @endforeach

            </select>

        </div>


        <div class="form-group">

            <label class="form-label">
                Fecha de inicio *
            </label>

            <input
                type="date"
                name="fecha_inicio"
                value="{{ now()->toDateString() }}"
                class="form-control"
                required
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
                Asignar horario
            </button>

        </div>

    </form>

</x-modal>



{{-- ============================================================
    ASIGNACIÓN MASIVA
============================================================ --}}

<x-modal
    id="modalAsignacionMasiva"
    title="Asignación masiva de horarios"
>

    <form
        method="POST"
        action="{{ route('trabajadores.horarios.asignarMasivo') }}"
        id="formAsignacionMasiva"
        class="form"
    >

        @csrf


        <div class="form-grid form-grid--2">

            <div class="form-group">

                <label class="form-label">
                    Horario *
                </label>

                <select
                    name="horario_id"
                    class="form-select"
                    required
                >

                    <option value="">
                        Seleccione un horario
                    </option>

                    @foreach($horariosActivos as $horario)

                        <option value="{{ $horario->id }}">
                            {{ $horario->nombre }}
                        </option>

                    @endforeach

                </select>

            </div>


            <div class="form-group">

                <label class="form-label">
                    Fecha de inicio *
                </label>

                <input
                    type="date"
                    name="fecha_inicio"
                    value="{{ now()->toDateString() }}"
                    class="form-control"
                    required
                >

            </div>

        </div>


        <div class="asignacion-masiva__filtros">

            <div class="form-group">

                <label class="form-label">
                    Buscar
                </label>

                <input
                    type="text"
                    id="filtroMasivoBuscar"
                    class="form-control"
                    placeholder="Nombre o DNI..."
                >

            </div>


            <div class="form-group">

                <label class="form-label">
                    Área
                </label>

                <select
                    id="filtroMasivoArea"
                    class="form-select"
                >

                    <option value="">
                        Todas las áreas
                    </option>

                    @foreach($areas as $area)

                        <option value="{{ $area->id }}">
                            {{ $area->nombre }}
                        </option>

                    @endforeach

                </select>

            </div>


            <div class="form-group">

                <label class="form-label">
                    Vínculo
                </label>

                <select
                    id="filtroMasivoVinculo"
                    class="form-select"
                >

                    <option value="">
                        Todos
                    </option>

                    <option value="CONTRATADO">
                        Contratados
                    </option>

                    <option value="LOCADOR">
                        Locadores
                    </option>

                </select>

            </div>

        </div>


        <div class="asignacion-masiva__toolbar">

            <label class="check-all">

                <input
                    type="checkbox"
                    id="seleccionarTodosMasivo"
                >

                Seleccionar todos los visibles

            </label>


            <span
                id="contadorSeleccionadosMasivo"
                class="seleccionados-contador"
            >
                0 seleccionados
            </span>

        </div>


        <div class="trabajadores-masivo">

            @foreach($trabajadores as $trabajador)

                <label
                    class="trabajador-masivo"

                    data-trabajador-masivo

                    data-area="{{ $trabajador->area_id }}"

                    data-vinculo="{{ $trabajador->tipo_vinculo }}"

                    data-busqueda="{{
                        strtolower(
                            ($trabajador->nombres ?? '')
                            .' '.
                            ($trabajador->apellidos ?? '')
                            .' '.
                            ($trabajador->dni ?? '')
                        )
                    }}"
                >

                    <input
                        type="checkbox"
                        name="trabajadores[]"
                        value="{{ $trabajador->id }}"
                        class="trabajador-masivo-checkbox"
                    >


                    <div class="trabajador-masivo__avatar">

                        {{
                            strtoupper(
                                substr(
                                    $trabajador->nombres,
                                    0,
                                    1
                                )
                            )
                        }}

                    </div>


                    <div class="trabajador-masivo__info">

                        <strong>
                            {{ $trabajador->nombres }}
                            {{ $trabajador->apellidos }}
                        </strong>

                        <span>
                            DNI: {{ $trabajador->dni }}
                        </span>

                        <small>
                            {{
                                $trabajador
                                    ->area
                                    ?->nombre
                                ?? 'Sin área'
                            }}
                        </small>

                    </div>


                    <div>

                        @if(
                            $trabajador->tipo_vinculo
                            === 'LOCADOR'
                        )

                            <span class="control-referencial">
                                Referencial
                            </span>

                        @else

                            <span class="control-obligatorio">
                                Obligatorio
                            </span>

                        @endif

                    </div>

                </label>

            @endforeach

        </div>


        <div
            id="sinResultadosMasivo"
            class="masivo-sin-resultados"
            hidden
        >
            No hay trabajadores que coincidan con los filtros.
        </div>


        <div class="asignacion-masiva__nota">

            <strong>
                El tipo de control se asignará automáticamente
            </strong>

            <p>
                CONTRATADO = OBLIGATORIO.
                LOCADOR = REFERENCIAL.
            </p>

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
                id="btnAsignarMasivo"
                disabled
            >
                Asignar a seleccionados
            </button>

        </div>

    </form>

</x-modal>



{{-- ============================================================
    CAMBIAR ESTADO
============================================================ --}}

<x-modal
    id="modalEstadoHorario"
    title="Cambiar estado"
    size="small"
>

    <form
        method="POST"
        id="formEstadoHorario"
    >

        @csrf
        @method('PATCH')


        <p id="estadoHorarioMensaje"></p>


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
                id="btnEstadoHorario"
                class="button button--danger"
            >
                Confirmar
            </button>

        </div>

    </form>

</x-modal>

@endsection


@push('scripts')
    <script src="{{ asset('js/pages/horarios.js') }}"></script>
@endpush