@extends('layouts.app')

@section('title', 'Reportes de Avance')

@section('page-title', 'Reportes de Avance')

@section('page-subtitle', 'Seguimiento de actividades y avances del personal')

@push('styles')
    <link
        rel="stylesheet"
        href="{{ asset('css/pages/reportes-avance.css') }}"
    >
@endpush


@section('content')

    @php
        $usuarioActual = auth()->user();

        $puedeRevisar = in_array(
            $usuarioActual->rol?->nombre,
            ['ADMINISTRADOR', 'GERENTE']
        );

        $puedeCrear = $usuarioActual->trabajador_id !== null;
    @endphp


    <div class="page-header">

        <div>
            <h2 class="page-header__title">
                Reportes de Avance
            </h2>

            <p class="page-header__description">
                Registra, consulta y revisa los avances realizados.
            </p>
        </div>


        @if($puedeCrear)

            <button
                type="button"
                class="button button--primary"
                data-modal-open="modalCrearReporte"
            >
                + Nuevo reporte
            </button>

        @endif

    </div>


    <section class="panel">

        <div class="panel__header">

            <form
                method="GET"
                action="{{ route('reportesAvance.index') }}"
                class="reportes-filtros"
            >

                <input
                    type="text"
                    name="buscar"
                    value="{{ request('buscar') }}"
                    class="form-control"
                    placeholder="Trabajador, DNI o descripción..."
                >


                <select
                    name="estado"
                    class="form-select"
                >

                    <option value="">
                        Todos los estados
                    </option>

                    <option
                        value="PENDIENTE"
                        @selected(
                            request('estado') === 'PENDIENTE'
                        )
                    >
                        Pendiente
                    </option>

                    <option
                        value="REVISADO"
                        @selected(
                            request('estado') === 'REVISADO'
                        )
                    >
                        Revisado
                    </option>

                    <option
                        value="OBSERVADO"
                        @selected(
                            request('estado') === 'OBSERVADO'
                        )
                    >
                        Observado
                    </option>

                </select>


                <input
                    type="date"
                    name="fecha_desde"
                    value="{{ request('fecha_desde') }}"
                    class="form-control"
                >


                <input
                    type="date"
                    name="fecha_hasta"
                    value="{{ request('fecha_hasta') }}"
                    class="form-control"
                >


                <button
                    type="submit"
                    class="button button--secondary"
                >
                    Filtrar
                </button>


                <a
                    href="{{ route('reportesAvance.index') }}"
                    class="button button--secondary"
                >
                    Limpiar
                </a>

            </form>

        </div>


        <div class="table-container">

            <table class="table">

                <thead>
                    <tr>
                        <th>Trabajador</th>
                        <th>Período</th>
                        <th>Presentación</th>
                        <th>Avance</th>
                        <th>Estado</th>
                        <th>Archivo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>


                <tbody>

                    @forelse($reportes as $reporte)

                        <tr>

                            <td>

                                <strong>
                                    {{ $reporte->trabajador?->nombres }}
                                    {{ $reporte->trabajador?->apellidos }}
                                </strong>

                                <div class="table-secondary-text">
                                    {{ $reporte->trabajador?->area?->nombre }}
                                </div>

                            </td>


                            <td>
                                {{ $reporte->periodo_inicio->format('d/m/Y') }}

                                <br>

                                <span class="table-secondary-text">
                                    hasta
                                    {{ $reporte->periodo_fin->format('d/m/Y') }}
                                </span>
                            </td>


                            <td>
                                {{ $reporte->fecha_presentacion->format('d/m/Y') }}
                            </td>


                            <td>

                                @if($reporte->porcentaje_avance !== null)

                                    <div class="progress-wrapper">

                                        <div class="progress">

                                            <div
                                                class="progress__bar"
                                                style="width: {{ $reporte->porcentaje_avance }}%"
                                            ></div>

                                        </div>

                                        <span>
                                            {{ number_format($reporte->porcentaje_avance, 0) }}%
                                        </span>

                                    </div>

                                @else

                                    -

                                @endif

                            </td>


                            <td>

                                @if($reporte->estado === 'PENDIENTE')

                                    <span class="badge badge--info">
                                        Pendiente
                                    </span>

                                @elseif($reporte->estado === 'REVISADO')

                                    <span class="badge badge--success">
                                        Revisado
                                    </span>

                                @else

                                    <span class="badge badge--danger">
                                        Observado
                                    </span>

                                @endif

                            </td>


                            <td>

                                @if($reporte->archivo)

                                    <a
                                        href="{{ Storage::url($reporte->archivo) }}"
                                        target="_blank"
                                        class="archivo-link"
                                    >
                                        Ver archivo
                                    </a>

                                @else

                                    -

                                @endif

                            </td>


                            <td>

                                <div class="table-actions">

                                    <button
                                        type="button"
                                        class="button button--secondary button--small"

                                        data-ver-reporte

                                        data-trabajador="{{
                                            $reporte->trabajador?->nombres
                                            . ' '
                                            . $reporte->trabajador?->apellidos
                                        }}"

                                        data-periodo="{{
                                            $reporte->periodo_inicio->format('d/m/Y')
                                            . ' - '
                                            . $reporte->periodo_fin->format('d/m/Y')
                                        }}"

                                        data-descripcion="{{ $reporte->descripcion }}"

                                        data-porcentaje="{{
                                            $reporte->porcentaje_avance
                                        }}"

                                        data-estado="{{
                                            $reporte->estado
                                        }}"

                                        data-comentario="{{
                                            $reporte->comentario_revision
                                        }}"
                                    >
                                        Ver
                                    </button>


                                    @if(
                                        $usuarioActual->trabajador_id === $reporte->trabajador_id
                                        && $reporte->estado !== 'REVISADO'
                                    )

                                        <button
                                            type="button"
                                            class="button button--secondary button--small"

                                            data-editar-reporte

                                            data-url="{{
                                                route(
                                                    'reportesAvance.update',
                                                    $reporte
                                                )
                                            }}"

                                            data-periodo-inicio="{{
                                                $reporte->periodo_inicio->format('Y-m-d')
                                            }}"

                                            data-periodo-fin="{{
                                                $reporte->periodo_fin->format('Y-m-d')
                                            }}"

                                            data-descripcion="{{ $reporte->descripcion }}"

                                            data-porcentaje="{{
                                                $reporte->porcentaje_avance
                                            }}"
                                        >
                                            Editar
                                        </button>

                                    @endif


                                    @if(
                                        $puedeRevisar
                                        && $reporte->estado === 'PENDIENTE'
                                    )

                                        <button
                                            type="button"
                                            class="button button--primary button--small"

                                            data-revisar-reporte

                                            data-url="{{
                                                route(
                                                    'reportesAvance.revisar',
                                                    $reporte
                                                )
                                            }}"

                                            data-trabajador="{{
                                                $reporte->trabajador?->nombres
                                                . ' '
                                                . $reporte->trabajador?->apellidos
                                            }}"
                                        >
                                            Revisar
                                        </button>

                                    @endif

                                </div>

                            </td>

                        </tr>


                    @empty

                        <tr>
                            <td
                                colspan="7"
                                class="table-empty"
                            >
                                No se encontraron reportes de avance.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </section>


    <div class="pagination-container">
        {{ $reportes->links() }}
    </div>



    {{-- NUEVO REPORTE --}}

    @if($puedeCrear)

        <x-modal
            id="modalCrearReporte"
            title="Nuevo reporte de avance"
            size="large"
        >

            <form
                method="POST"
                action="{{ route('reportesAvance.store') }}"
                enctype="multipart/form-data"
                class="form"
            >

                @csrf


                <div class="form-grid form-grid--2">

                    <div class="form-group">

                        <label class="form-label">
                            Inicio del período *
                        </label>

                        <input
                            type="date"
                            name="periodo_inicio"
                            value="{{ old('periodo_inicio') }}"
                            class="form-control"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label class="form-label">
                            Fin del período *
                        </label>

                        <input
                            type="date"
                            name="periodo_fin"
                            value="{{ old('periodo_fin') }}"
                            class="form-control"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label class="form-label">
                            Porcentaje de avance
                        </label>

                        <input
                            type="number"
                            name="porcentaje_avance"
                            value="{{ old('porcentaje_avance') }}"
                            class="form-control"
                            min="0"
                            max="100"
                            step="0.01"
                        >

                    </div>


                    <div class="form-group">

                        <label class="form-label">
                            Archivo
                        </label>

                        <input
                            type="file"
                            name="archivo"
                            class="form-control"
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                        >

                        <small class="form-help">
                            Máximo 5 MB.
                        </small>

                    </div>


                    <div class="form-group form-group--full">

                        <label class="form-label">
                            Descripción del avance *
                        </label>

                        <textarea
                            name="descripcion"
                            class="form-textarea"
                            rows="7"
                            required
                        >{{ old('descripcion') }}</textarea>

                    </div>

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
                        Guardar reporte
                    </button>

                </div>

            </form>

        </x-modal>

    @endif



    {{-- EDITAR --}}

    <x-modal
        id="modalEditarReporte"
        title="Editar reporte"
        size="large"
    >

        <form
            method="POST"
            id="formEditarReporte"
            enctype="multipart/form-data"
            class="form"
        >

            @csrf
            @method('PUT')


            <div class="form-grid form-grid--2">

                <div class="form-group">

                    <label class="form-label">
                        Inicio del período *
                    </label>

                    <input
                        id="editarPeriodoInicio"
                        type="date"
                        name="periodo_inicio"
                        class="form-control"
                        required
                    >

                </div>


                <div class="form-group">

                    <label class="form-label">
                        Fin del período *
                    </label>

                    <input
                        id="editarPeriodoFin"
                        type="date"
                        name="periodo_fin"
                        class="form-control"
                        required
                    >

                </div>


                <div class="form-group">

                    <label class="form-label">
                        Porcentaje
                    </label>

                    <input
                        id="editarPorcentaje"
                        type="number"
                        name="porcentaje_avance"
                        class="form-control"
                        min="0"
                        max="100"
                        step="0.01"
                    >

                </div>


                <div class="form-group">

                    <label class="form-label">
                        Reemplazar archivo
                    </label>

                    <input
                        type="file"
                        name="archivo"
                        class="form-control"
                    >

                </div>


                <div class="form-group form-group--full">

                    <label class="form-label">
                        Descripción *
                    </label>

                    <textarea
                        id="editarDescripcion"
                        name="descripcion"
                        class="form-textarea"
                        rows="7"
                        required
                    ></textarea>

                </div>

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



    {{-- VER DETALLE --}}

    <x-modal
        id="modalVerReporte"
        title="Detalle del reporte"
        size="large"
    >

        <div class="detalle-reporte">

            <div class="detalle-item">
                <span>Trabajador</span>
                <strong id="detalleTrabajador"></strong>
            </div>

            <div class="detalle-item">
                <span>Período</span>
                <strong id="detallePeriodo"></strong>
            </div>

            <div class="detalle-item">
                <span>Avance</span>
                <strong id="detallePorcentaje"></strong>
            </div>

            <div class="detalle-item">
                <span>Estado</span>
                <strong id="detalleEstado"></strong>
            </div>

            <div class="detalle-item detalle-item--full">
                <span>Descripción</span>
                <p id="detalleDescripcion"></p>
            </div>

            <div class="detalle-item detalle-item--full">
                <span>Comentario de revisión</span>
                <p id="detalleComentario"></p>
            </div>

        </div>

    </x-modal>



    {{-- REVISAR --}}

    @if($puedeRevisar)

        <x-modal
            id="modalRevisarReporte"
            title="Revisar reporte"
        >

            <form
                method="POST"
                id="formRevisarReporte"
                class="form"
            >

                @csrf
                @method('PATCH')


                <p>
                    Reporte presentado por
                    <strong id="revisarTrabajador"></strong>.
                </p>


                <div class="form-group">

                    <label class="form-label">
                        Resultado *
                    </label>

                    <select
                        name="estado"
                        class="form-select"
                        required
                    >

                        <option value="REVISADO">
                            Revisado
                        </option>

                        <option value="OBSERVADO">
                            Observado
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label class="form-label">
                        Comentario
                    </label>

                    <textarea
                        name="comentario_revision"
                        class="form-textarea"
                        maxlength="500"
                        rows="5"
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
                        class="button button--primary"
                    >
                        Guardar revisión
                    </button>

                </div>

            </form>

        </x-modal>

    @endif

@endsection


@push('scripts')

    <script src="{{ asset('js/pages/reportes-avance.js') }}"></script>

    @if($errors->any() && old('periodo_inicio'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                window.AppModal.open('modalCrearReporte');
            });
        </script>
    @endif

@endpush