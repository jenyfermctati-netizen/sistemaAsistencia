@extends('layouts.app')

@section('title', 'Reportes de Avance')

@section('content')

<div class="container-fluid py-4">

    {{-- ENCABEZADO --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Reportes de Avance</h2>
            <p class="text-muted mb-0">
                Registro y seguimiento de avances de los locadores.
            </p>
        </div>

        @if(auth()->user()->rol->nombre === 'TRABAJADOR')
            <a href="{{ route('reportes-avance.create') }}"
               class="btn btn-primary">
                + Nuevo reporte
            </a>
        @endif
    </div>


    {{-- MENSAJES --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}

            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert">
            </button>
        </div>
    @endif


    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif


    {{-- FILTROS --}}
    <div class="card shadow-sm mb-4">

        <div class="card-body">

            <form method="GET"
                  action="{{ route('reportes-avance.index') }}">

                <div class="row g-3">

                    {{-- BUSCAR --}}
                    <div class="col-md-4">

                        <label class="form-label">
                            Buscar trabajador
                        </label>

                        <input
                            type="text"
                            name="buscar"
                            value="{{ request('buscar') }}"
                            class="form-control"
                            placeholder="Nombre o DNI">

                    </div>


                    {{-- ESTADO --}}
                    <div class="col-md-3">

                        <label class="form-label">
                            Estado
                        </label>

                        <select name="estado"
                                class="form-select">

                            <option value="">
                                Todos
                            </option>

                            <option value="PENDIENTE"
                                {{ request('estado') === 'PENDIENTE' ? 'selected' : '' }}>
                                Pendiente
                            </option>

                            <option value="REVISADO"
                                {{ request('estado') === 'REVISADO' ? 'selected' : '' }}>
                                Revisado
                            </option>

                            <option value="OBSERVADO"
                                {{ request('estado') === 'OBSERVADO' ? 'selected' : '' }}>
                                Observado
                            </option>

                        </select>

                    </div>


                    {{-- FECHA INICIO --}}
                    <div class="col-md-2">

                        <label class="form-label">
                            Desde
                        </label>

                        <input
                            type="date"
                            name="desde"
                            value="{{ request('desde') }}"
                            class="form-control">

                    </div>


                    {{-- FECHA FIN --}}
                    <div class="col-md-2">

                        <label class="form-label">
                            Hasta
                        </label>

                        <input
                            type="date"
                            name="hasta"
                            value="{{ request('hasta') }}"
                            class="form-control">

                    </div>


                    <div class="col-md-1 d-flex align-items-end">

                        <button type="submit"
                                class="btn btn-secondary w-100">
                            Buscar
                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>


    {{-- TABLA --}}
    <div class="card shadow-sm">

        <div class="card-header bg-white">

            <strong>
                Listado de reportes
            </strong>

        </div>

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">

                        <tr>
                            <th>#</th>
                            <th>Trabajador</th>
                            <th>Periodo</th>
                            <th>Presentación</th>
                            <th>Avance</th>
                            <th>Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>

                    </thead>

                    <tbody>

                    @forelse($reportes as $reporte)

                        <tr>

                            <td>
                                {{ $reporte->id }}
                            </td>


                            <td>

                                <strong>
                                    {{ $reporte->trabajador->nombres }}
                                    {{ $reporte->trabajador->apellidos }}
                                </strong>

                                <br>

                                <small class="text-muted">
                                    DNI:
                                    {{ $reporte->trabajador->dni }}
                                </small>

                            </td>


                            <td>

                                {{ \Carbon\Carbon::parse($reporte->periodo_inicio)->format('d/m/Y') }}

                                <br>

                                <small class="text-muted">
                                    hasta
                                    {{ \Carbon\Carbon::parse($reporte->periodo_fin)->format('d/m/Y') }}
                                </small>

                            </td>


                            <td>

                                {{ \Carbon\Carbon::parse($reporte->fecha_presentacion)->format('d/m/Y') }}

                            </td>


                            <td style="min-width: 160px;">

                                @if($reporte->porcentaje_avance !== null)

                                    <div class="progress"
                                         style="height: 20px;">

                                        <div
                                            class="progress-bar"
                                            role="progressbar"
                                            style="width: {{ $reporte->porcentaje_avance }}%;"
                                            aria-valuenow="{{ $reporte->porcentaje_avance }}"
                                            aria-valuemin="0"
                                            aria-valuemax="100">

                                            {{ number_format($reporte->porcentaje_avance, 0) }}%

                                        </div>

                                    </div>

                                @else

                                    <span class="text-muted">
                                        No indicado
                                    </span>

                                @endif

                            </td>


                            {{-- ESTADO --}}
                            <td>

                                @switch($reporte->estado)

                                    @case('PENDIENTE')

                                        <span class="badge bg-warning text-dark">
                                            Pendiente
                                        </span>

                                        @break


                                    @case('REVISADO')

                                        <span class="badge bg-success">
                                            Revisado
                                        </span>

                                        @break


                                    @case('OBSERVADO')

                                        <span class="badge bg-danger">
                                            Observado
                                        </span>

                                        @break

                                @endswitch

                            </td>


                            {{-- ACCIONES --}}
                            <td class="text-center">

                                <a
                                    href="{{ route('reportes-avance.show', $reporte->id) }}"
                                    class="btn btn-sm btn-outline-primary">

                                    Ver

                                </a>


                                @if(
                                    auth()->user()->rol->nombre === 'TRABAJADOR'
                                    &&
                                    $reporte->estado !== 'REVISADO'
                                )

                                    <a
                                        href="{{ route('reportes-avance.edit', $reporte->id) }}"
                                        class="btn btn-sm btn-outline-secondary">

                                        Editar

                                    </a>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="7"
                                class="text-center py-4 text-muted">

                                No existen reportes de avance registrados.

                            </td>

                        </tr>

                    @endforelse

                    </tbody>

                </table>

            </div>

        </div>


        {{-- PAGINACION --}}
        @if(method_exists($reportes, 'links'))

            <div class="card-footer">

                {{ $reportes->links() }}

            </div>

        @endif

    </div>

</div>

@endsection