@extends('layouts.app')

@section('title', 'Asistencias')

@section('page-title', 'Asistencias')

@section(
    'page-subtitle',
    'Control diario de asistencia del personal'
)

@push('styles')
    <link
        rel="stylesheet"
        href="{{ asset('css/pages/asistencias.css') }}"
    >
@endpush


@section('content')

    {{-- ENCABEZADO --}}
    <div class="page-header">

        <div>

            <h2 class="page-header__title">
                Asistencias
            </h2>

            <p class="page-header__description">
                Consulta y procesa la asistencia diaria utilizando
                horarios y marcaciones.
            </p>

        </div>


        <button
            type="button"
            class="button button--primary"
            data-modal-open="modalProcesarAsistencia"
        >
            Procesar asistencia
        </button>

    </div>



    {{-- FILTROS --}}
    <section class="panel">

        <div class="panel__header">

            <form
                method="GET"
                action="{{ route('asistencias.index') }}"
                class="asistencias-filtros"
            >

                <div class="form-group">

                    <label class="form-label">
                        Trabajador
                    </label>

                    <input
                        type="text"
                        name="buscar"
                        value="{{ request('buscar') }}"
                        class="form-control"
                        placeholder="DNI o nombre..."
                    >

                </div>


                <div class="form-group">

                    <label class="form-label">
                        Desde
                    </label>

                    <input
                        type="date"
                        name="fecha_desde"
                        value="{{ request('fecha_desde') }}"
                        class="form-control"
                    >

                </div>


                <div class="form-group">

                    <label class="form-label">
                        Hasta
                    </label>

                    <input
                        type="date"
                        name="fecha_hasta"
                        value="{{ request('fecha_hasta') }}"
                        class="form-control"
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

                        @foreach([
                            'PRESENTE' => 'Presente',
                            'TARDANZA' => 'Tardanza',
                            'FALTA' => 'Falta',
                            'JUSTIFICADO' => 'Justificado',
                            'CAMPO' => 'Campo',
                            'VACACIONES' => 'Vacaciones',
                            'PERMISO' => 'Permiso',
                            'FERIADO' => 'Feriado',
                            'NO_LABORABLE' => 'No laborable',
                            'SIN_REGISTRO' => 'Sin registro',
                        ] as $valor => $texto)

                            <option
                                value="{{ $valor }}"
                                @selected(
                                    request('estado')
                                    === $valor
                                )
                            >
                                {{ $texto }}
                            </option>

                        @endforeach

                    </select>

                </div>


                <div class="form-group">

                    <label class="form-label">
                        Control
                    </label>

                    <select
                        name="tipo_control"
                        class="form-select"
                    >

                        <option value="">
                            Todos
                        </option>

                        <option
                            value="OBLIGATORIO"
                            @selected(
                                request('tipo_control')
                                === 'OBLIGATORIO'
                            )
                        >
                            Obligatorio
                        </option>

                        <option
                            value="REFERENCIAL"
                            @selected(
                                request('tipo_control')
                                === 'REFERENCIAL'
                            )
                        >
                            Referencial
                        </option>

                    </select>

                </div>


                <div class="asistencias-filtros__actions">

                    <button
                        type="submit"
                        class="button button--secondary"
                    >
                        Filtrar
                    </button>

                    <a
                        href="{{ route('asistencias.index') }}"
                        class="button button--secondary"
                    >
                        Limpiar
                    </a>

                </div>

            </form>

        </div>



        {{-- TABLA --}}
        <div class="table-container">

            <table class="table">

                <thead>

                    <tr>
                        <th>Fecha</th>
                        <th>Trabajador</th>
                        <th>Horario</th>
                        <th>Control</th>
                        <th>Programado</th>
                        <th>Entrada</th>
                        <th>Salida</th>
                        <th>Trabajado</th>
                        <th>Tardanza</th>
                        <th>Estado</th>
                    </tr>

                </thead>


                <tbody>

                    @forelse($asistencias as $asistencia)

                        <tr>

                            <td>

                                <strong>
                                    {{
                                        $asistencia
                                            ->fecha
                                            ?->format('d/m/Y')
                                    }}
                                </strong>

                            </td>


                            <td>

                                <div class="asistencia-trabajador">

                                    <strong>
                                        {{
                                            $asistencia
                                                ->trabajador
                                                ?->nombres
                                        }}

                                        {{
                                            $asistencia
                                                ->trabajador
                                                ?->apellidos
                                        }}
                                    </strong>

                                    <span>
                                        DNI:
                                        {{
                                            $asistencia
                                                ->trabajador
                                                ?->dni
                                            ?? '-'
                                        }}
                                    </span>

                                    <small>
                                        {{
                                            $asistencia
                                                ->trabajador
                                                ?->area
                                                ?->nombre
                                            ?? '-'
                                        }}
                                    </small>

                                </div>

                            </td>


                            <td>
                                {{
                                    $asistencia
                                        ->horario
                                        ?->nombre
                                    ?? '-'
                                }}
                            </td>


                            <td>

                                @if(
                                    $asistencia
                                        ->tipo_control_aplicado
                                    === 'OBLIGATORIO'
                                )

                                    <span class="badge badge--primary">
                                        Obligatorio
                                    </span>

                                @elseif(
                                    $asistencia
                                        ->tipo_control_aplicado
                                    === 'REFERENCIAL'
                                )

                                    <span class="badge badge--info">
                                        Referencial
                                    </span>

                                @else

                                    <span class="table-secondary-text">
                                        -
                                    </span>

                                @endif

                            </td>


                            <td>

                                @if(
                                    $asistencia->hora_programada_entrada
                                )

                                    <div class="horario-programado">

                                        <span>
                                            {{
                                                substr(
                                                    $asistencia
                                                        ->hora_programada_entrada,
                                                    0,
                                                    5
                                                )
                                            }}
                                        </span>

                                        <span>
                                            -
                                        </span>

                                        <span>
                                            {{
                                                substr(
                                                    $asistencia
                                                        ->hora_programada_salida,
                                                    0,
                                                    5
                                                )
                                            }}
                                        </span>

                                    </div>

                                    <small class="table-secondary-text">
                                        Tol.
                                        {{
                                            $asistencia
                                                ->tolerancia_aplicada
                                        }}
                                        min
                                    </small>

                                @else

                                    -

                                @endif

                            </td>


                            <td>

                                @if($asistencia->hora_entrada)

                                    <strong class="hora-marcacion">
                                        {{
                                            substr(
                                                $asistencia->hora_entrada,
                                                0,
                                                5
                                            )
                                        }}
                                    </strong>

                                @else

                                    <span class="sin-marcacion">
                                        —
                                    </span>

                                @endif

                            </td>


                            <td>

                                @if($asistencia->hora_salida)

                                    <strong class="hora-marcacion">
                                        {{
                                            substr(
                                                $asistencia->hora_salida,
                                                0,
                                                5
                                            )
                                        }}
                                    </strong>

                                @else

                                    <span class="sin-marcacion">
                                        —
                                    </span>

                                @endif

                            </td>


                            <td>

                                @php
                                    $horas = intdiv(
                                        $asistencia->minutos_trabajados,
                                        60
                                    );

                                    $minutos =
                                        $asistencia
                                            ->minutos_trabajados
                                        % 60;
                                @endphp

                                @if(
                                    $asistencia
                                        ->minutos_trabajados
                                    > 0
                                )

                                    <strong>
                                        {{ $horas }}h
                                        {{ $minutos }}m
                                    </strong>

                                @else

                                    -

                                @endif

                            </td>


                            <td>

                                @if(
                                    $asistencia
                                        ->minutos_tardanza
                                    > 0
                                )

                                    <span class="tardanza-minutos">
                                        {{
                                            $asistencia
                                                ->minutos_tardanza
                                        }}
                                        min
                                    </span>

                                @else

                                    <span class="table-secondary-text">
                                        0 min
                                    </span>

                                @endif

                            </td>


                            <td>

                                @switch($asistencia->estado)

                                    @case('PRESENTE')

                                        <span class="badge badge--success">
                                            Presente
                                        </span>

                                        @break


                                    @case('TARDANZA')

                                        <span class="badge badge--warning">
                                            Tardanza
                                        </span>

                                        @break


                                    @case('FALTA')

                                        <span class="badge badge--danger">
                                            Falta
                                        </span>

                                        @break


                                    @case('SIN_REGISTRO')

                                        <span class="badge badge--secondary">
                                            Sin registro
                                        </span>

                                        @break


                                    @case('NO_LABORABLE')

                                        <span class="badge badge--secondary">
                                            No laborable
                                        </span>

                                        @break


                                    @case('CAMPO')

                                        <span class="badge badge--info">
                                            Campo
                                        </span>

                                        @break


                                    @case('VACACIONES')

                                        <span class="badge badge--info">
                                            Vacaciones
                                        </span>

                                        @break


                                    @case('PERMISO')

                                        <span class="badge badge--info">
                                            Permiso
                                        </span>

                                        @break


                                    @case('FERIADO')

                                        <span class="badge badge--info">
                                            Feriado
                                        </span>

                                        @break


                                    @case('JUSTIFICADO')

                                        <span class="badge badge--info">
                                            Justificado
                                        </span>

                                        @break


                                    @default

                                        <span class="badge">
                                            {{ $asistencia->estado }}
                                        </span>

                                @endswitch


                                @if($asistencia->observacion)

                                    <div class="asistencia-observacion">
                                        {{ $asistencia->observacion }}
                                    </div>

                                @endif

                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td
                                colspan="10"
                                class="table-empty"
                            >

                                <div class="empty-state">

                                    <strong>
                                        No existen asistencias procesadas
                                    </strong>

                                    <span>
                                        Selecciona una fecha y procesa
                                        la asistencia del personal.
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
        {{ $asistencias->links() }}
    </div>



    {{-- MODAL PROCESAR --}}
    <x-modal
        id="modalProcesarAsistencia"
        title="Procesar asistencia"
        size="small"
    >

        <form
            method="POST"
            action="{{ route('asistencias.procesar') }}"
            class="form"
        >

            @csrf


            <div class="form-group">

                <label class="form-label">
                    Fecha a procesar *
                </label>

                <input
                    type="date"
                    name="fecha"
                    value="{{ now()->toDateString() }}"
                    class="form-control"
                    required
                >

            </div>


            <div class="procesar-info">

                <strong>
                    ¿Qué hará este proceso?
                </strong>

                <p>
                    Revisará el horario y las marcaciones de
                    todos los trabajadores activos para generar
                    su asistencia correspondiente.
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
                >
                    Procesar
                </button>

            </div>

        </form>

    </x-modal>

@endsection