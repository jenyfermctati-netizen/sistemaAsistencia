@extends('layouts.app')

@section('title', 'Marcaciones')

@section('page-title', 'Marcaciones')

@section('page-subtitle', 'Registro y consulta de marcaciones del personal')

@push('styles')
    <link
        rel="stylesheet"
        href="{{ asset('css/pages/marcaciones.css') }}"
    >
@endpush


@section('content')

    {{-- ENCABEZADO --}}
    <div class="page-header">

        <div>
            <h2 class="page-header__title">
                Marcaciones
            </h2>

            <p class="page-header__description">
                Consulta las marcaciones del biométrico y registra correcciones manuales.
            </p>
        </div>


        <div class="page-header__actions">

            <button
                type="button"
                class="button button--primary"
                data-modal-open="modalCrearMarcacion"
            >
                + Marcación manual
            </button>

        </div>

    </div>


    {{-- FILTROS --}}
    <section class="panel">

        <div class="panel__header">

            <form
                method="GET"
                action="{{ route('marcaciones.index') }}"
                class="marcaciones-filtros"
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
                        placeholder="DNI, trabajador o biométrico..."
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
                            value="ENTRADA"
                            @selected(request('tipo') === 'ENTRADA')
                        >
                            Entrada
                        </option>

                        <option
                            value="SALIDA"
                            @selected(request('tipo') === 'SALIDA')
                        >
                            Salida
                        </option>
                    </select>
                </div>


                <div class="form-group">
                    <label class="form-label">
                        Origen
                    </label>

                    <select
                        name="origen"
                        class="form-select"
                    >
                        <option value="">
                            Todos
                        </option>

                        <option
                            value="BIOMETRICO"
                            @selected(request('origen') === 'BIOMETRICO')
                        >
                            Biométrico
                        </option>

                        <option
                            value="MANUAL"
                            @selected(request('origen') === 'MANUAL')
                        >
                            Manual
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
                            value="ACTIVA"
                            @selected(request('estado') === 'ACTIVA')
                        >
                            Activas
                        </option>

                        <option
                            value="ANULADA"
                            @selected(request('estado') === 'ANULADA')
                        >
                            Anuladas
                        </option>
                    </select>
                </div>


                <div class="marcaciones-filtros__actions">

                    <button
                        type="submit"
                        class="button button--secondary"
                    >
                        Filtrar
                    </button>

                    <a
                        href="{{ route('marcaciones.index') }}"
                        class="button button--secondary"
                    >
                        Limpiar
                    </a>

                </div>

            </form>

        </div>


        {{-- TABLA --}}
        <div class="table-container">

            <table class="table marcaciones-table">

                <thead>
                    <tr>
                        <th>Trabajador</th>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Tipo</th>
                        <th>Origen</th>
                        <th>Dispositivo / Motivo</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>


                <tbody>

                    @forelse($marcaciones as $marcacion)

                        <tr class="{{ $marcacion->anulada ? 'row-anulada' : '' }}">

                            {{-- TRABAJADOR --}}
                            <td>

                                <div class="trabajador-info">

                                    <div class="trabajador-info__avatar">
                                        {{
                                            strtoupper(
                                                substr(
                                                    $marcacion->trabajador?->nombres ?? 'T',
                                                    0,
                                                    1
                                                )
                                            )
                                        }}
                                    </div>


                                    <div>

                                        <strong>
                                            {{ $marcacion->trabajador?->nombres }}
                                            {{ $marcacion->trabajador?->apellidos }}
                                        </strong>

                                        <span>
                                            DNI:
                                            {{ $marcacion->trabajador?->dni ?? '-' }}
                                        </span>

                                        @if($marcacion->trabajador?->area)

                                            <small>
                                                {{ $marcacion->trabajador->area->nombre }}
                                            </small>

                                        @endif

                                    </div>

                                </div>

                            </td>


                            {{-- FECHA --}}
                            <td>
                                {{ $marcacion->fecha_hora?->format('d/m/Y') }}
                            </td>


                            {{-- HORA --}}
                            <td>

                                <strong class="marcacion-hora">
                                    {{ $marcacion->fecha_hora?->format('H:i:s') }}
                                </strong>

                            </td>


                            {{-- TIPO --}}
                            <td>

                                @if($marcacion->tipo === 'ENTRADA')

                                    <span class="badge badge--success">
                                        Entrada
                                    </span>

                                @else

                                    <span class="badge badge--info">
                                        Salida
                                    </span>

                                @endif

                            </td>


                            {{-- ORIGEN --}}
                            <td>

                                @if($marcacion->origen === 'BIOMETRICO')

                                    <span class="badge badge--primary">
                                        Biométrico
                                    </span>

                                @else

                                    <span class="badge badge--warning">
                                        Manual
                                    </span>

                                @endif

                            </td>


                            {{-- INFORMACIÓN --}}
                            <td>

                                @if($marcacion->origen === 'BIOMETRICO')

                                    <strong>
                                        {{ $marcacion->dispositivo ?? 'Biométrico' }}
                                    </strong>

                                    @if($marcacion->evento_externo_id)

                                        <div class="table-secondary-text">
                                            Evento:
                                            {{ $marcacion->evento_externo_id }}
                                        </div>

                                    @endif

                                @else

                                    <span>
                                        {{ $marcacion->motivo_manual ?? '-' }}
                                    </span>

                                    @if($marcacion->registradoPor)

                                        <div class="table-secondary-text">
                                            Registrado por:
                                            {{ $marcacion->registradoPor->name }}
                                        </div>

                                    @endif

                                @endif

                            </td>


                            {{-- ESTADO --}}
                            <td>

                                @if(!$marcacion->anulada)

                                    <span class="badge badge--success">
                                        Activa
                                    </span>

                                @else

                                    <span class="badge badge--danger">
                                        Anulada
                                    </span>

                                    @if($marcacion->motivo_anulacion)

                                        <div class="table-secondary-text">
                                            {{ $marcacion->motivo_anulacion }}
                                        </div>

                                    @endif

                                @endif

                            </td>


                            {{-- ACCIONES --}}
                            <td>

                                @if(!$marcacion->anulada)

                                    <button
                                        type="button"
                                        class="button button--danger button--small"

                                        data-anular-marcacion

                                        data-url="{{
                                            route(
                                                'marcaciones.anular',
                                                $marcacion
                                            )
                                        }}"

                                        data-trabajador="{{
                                            $marcacion->trabajador?->nombres
                                        }} {{
                                            $marcacion->trabajador?->apellidos
                                        }}"

                                        data-fecha="{{
                                            $marcacion->fecha_hora?->format(
                                                'd/m/Y H:i'
                                            )
                                        }}"
                                    >
                                        Anular
                                    </button>

                                @else

                                    <span class="table-secondary-text">
                                        Sin acciones
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
                                <div class="empty-state">

                                    <strong>
                                        No se encontraron marcaciones
                                    </strong>

                                    <span>
                                        Prueba modificando los filtros o registra una marcación manual.
                                    </span>

                                </div>
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </section>


    {{-- PAGINACIÓN --}}
    <div class="pagination-container">
        {{ $marcaciones->links() }}
    </div>



    {{-- ============================================================
        MODAL NUEVA MARCACIÓN
    ============================================================ --}}
    <x-modal
        id="modalCrearMarcacion"
        title="Nueva marcación manual"
    >

        <form
            method="POST"
            action="{{ route('marcaciones.store') }}"
            class="form"
        >

            @csrf


            <div class="form-group">

                <label class="form-label">
                    Trabajador *
                </label>

                <select
                    name="trabajador_id"
                    class="form-select"
                    required
                >

                    <option value="">
                        Seleccione un trabajador
                    </option>

                    @foreach($trabajadores as $trabajador)

                        <option
                            value="{{ $trabajador->id }}"
                            @selected(
                                old('trabajador_id') == $trabajador->id
                            )
                        >
                            {{ $trabajador->apellidos }},
                            {{ $trabajador->nombres }}
                            -
                            {{ $trabajador->dni }}
                        </option>

                    @endforeach

                </select>


                @error('trabajador_id')

                    <small class="form-error">
                        {{ $message }}
                    </small>

                @enderror

            </div>


            <div class="form-grid form-grid--2">

                <div class="form-group">

                    <label class="form-label">
                        Fecha y hora *
                    </label>

                    <input
                        type="datetime-local"
                        name="fecha_hora"
                        value="{{ old('fecha_hora') }}"
                        class="form-control"
                        required
                    >

                    @error('fecha_hora')

                        <small class="form-error">
                            {{ $message }}
                        </small>

                    @enderror

                </div>


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

                        <option
                            value="ENTRADA"
                            @selected(old('tipo') === 'ENTRADA')
                        >
                            Entrada
                        </option>

                        <option
                            value="SALIDA"
                            @selected(old('tipo') === 'SALIDA')
                        >
                            Salida
                        </option>

                    </select>


                    @error('tipo')

                        <small class="form-error">
                            {{ $message }}
                        </small>

                    @enderror

                </div>

            </div>


            <div class="form-group">

                <label class="form-label">
                    Motivo del registro manual *
                </label>

                <textarea
                    name="motivo_manual"
                    class="form-textarea"
                    rows="4"
                    maxlength="500"
                    placeholder="Ejemplo: El trabajador olvidó realizar la marcación..."
                    required
                >{{ old('motivo_manual') }}</textarea>


                @error('motivo_manual')

                    <small class="form-error">
                        {{ $message }}
                    </small>

                @enderror

            </div>


            <div class="manual-info">

                <strong>
                    Registro manual
                </strong>

                <p>
                    Esta marcación quedará identificada como MANUAL
                    y asociada al usuario administrador que la registre.
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
                    Registrar marcación
                </button>

            </div>

        </form>

    </x-modal>



    {{-- ============================================================
        MODAL ANULAR
    ============================================================ --}}
    <x-modal
        id="modalAnularMarcacion"
        title="Anular marcación"
        size="small"
    >

        <form
            method="POST"
            id="formAnularMarcacion"
            class="form"
        >

            @csrf
            @method('PATCH')


            <div
                id="anularMarcacionInfo"
                class="anulacion-info"
            ></div>


            <div class="form-group">

                <label class="form-label">
                    Motivo de anulación *
                </label>

                <textarea
                    name="motivo_anulacion"
                    class="form-textarea"
                    rows="4"
                    maxlength="500"
                    placeholder="Indica por qué se anula esta marcación..."
                    required
                ></textarea>

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
                    class="button button--danger"
                >
                    Anular marcación
                </button>

            </div>

        </form>

    </x-modal>

@endsection


@push('scripts')

    <script src="{{ asset('js/pages/marcaciones.js') }}"></script>

@endpush