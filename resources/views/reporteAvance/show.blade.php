@extends('layouts.app')

@section('title', 'Detalle del Reporte')

@section('content')

<div class="container py-4">

    <div class="row justify-content-center">

        <div class="col-lg-10">

            {{-- ENCABEZADO --}}
            <div class="d-flex justify-content-between align-items-center mb-3">

                <div>

                    <h3 class="mb-1">
                        Reporte de Avance #{{ $reporte->id }}
                    </h3>

                    <span class="text-muted">
                        Detalle del reporte presentado
                    </span>

                </div>


                <a
                    href="{{ route('reportes-avance.index') }}"
                    class="btn btn-outline-secondary">

                    Volver

                </a>

            </div>


            {{-- ESTADO --}}
            <div class="card shadow-sm mb-4">

                <div class="card-body">

                    <div class="row">

                        <div class="col-md-4">

                            <small class="text-muted">
                                Trabajador
                            </small>

                            <h5>
                                {{ $reporte->trabajador->nombres }}
                                {{ $reporte->trabajador->apellidos }}
                            </h5>

                        </div>


                        <div class="col-md-4">

                            <small class="text-muted">
                                DNI
                            </small>

                            <h5>
                                {{ $reporte->trabajador->dni }}
                            </h5>

                        </div>


                        <div class="col-md-4">

                            <small class="text-muted">
                                Estado
                            </small>

                            <div class="mt-1">

                                @switch($reporte->estado)

                                    @case('PENDIENTE')

                                        <span class="badge bg-warning text-dark fs-6">
                                            Pendiente
                                        </span>

                                        @break


                                    @case('REVISADO')

                                        <span class="badge bg-success fs-6">
                                            Revisado
                                        </span>

                                        @break


                                    @case('OBSERVADO')

                                        <span class="badge bg-danger fs-6">
                                            Observado
                                        </span>

                                        @break

                                @endswitch

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- DATOS DEL REPORTE --}}
            <div class="card shadow-sm mb-4">

                <div class="card-header bg-white">

                    <strong>
                        Información del reporte
                    </strong>

                </div>


                <div class="card-body">

                    <div class="row mb-4">

                        <div class="col-md-4">

                            <label class="text-muted">
                                Periodo
                            </label>

                            <p class="fw-semibold">

                                {{ \Carbon\Carbon::parse($reporte->periodo_inicio)->format('d/m/Y') }}

                                -

                                {{ \Carbon\Carbon::parse($reporte->periodo_fin)->format('d/m/Y') }}

                            </p>

                        </div>


                        <div class="col-md-4">

                            <label class="text-muted">
                                Fecha de presentación
                            </label>

                            <p class="fw-semibold">

                                {{ \Carbon\Carbon::parse($reporte->fecha_presentacion)->format('d/m/Y') }}

                            </p>

                        </div>


                        <div class="col-md-4">

                            <label class="text-muted">
                                Porcentaje de avance
                            </label>

                            <p class="fw-semibold">

                                @if($reporte->porcentaje_avance !== null)

                                    {{ number_format($reporte->porcentaje_avance, 2) }} %

                                @else

                                    No especificado

                                @endif

                            </p>

                        </div>

                    </div>


                    @if($reporte->porcentaje_avance !== null)

                        <div class="mb-4">

                            <div class="progress"
                                 style="height: 25px;">

                                <div
                                    class="progress-bar"
                                    role="progressbar"
                                    style="width: {{ $reporte->porcentaje_avance }}%;">

                                    {{ number_format($reporte->porcentaje_avance, 0) }}%

                                </div>

                            </div>

                        </div>

                    @endif


                    {{-- DESCRIPCION --}}
                    <div class="mb-4">

                        <label class="text-muted mb-2">
                            Descripción del avance
                        </label>

                        <div class="border rounded p-3 bg-light">

                            {!! nl2br(e($reporte->descripcion)) !!}

                        </div>

                    </div>


                    {{-- ARCHIVO --}}
                    <div>

                        <label class="text-muted mb-2">
                            Documento adjunto
                        </label>

                        <br>

                        @if($reporte->archivo)

                            <a
                                href="{{ asset('storage/' . $reporte->archivo) }}"
                                target="_blank"
                                class="btn btn-outline-primary">

                                Ver documento

                            </a>

                        @else

                            <span class="text-muted">
                                No se adjuntó ningún documento.
                            </span>

                        @endif

                    </div>

                </div>

            </div>


            {{-- REVISION ADMINISTRADOR --}}
            @if(
                auth()->user()->rol->nombre === 'ADMINISTRADOR'
                ||
                auth()->user()->rol->nombre === 'GERENTE'
            )

                <div class="card shadow-sm">

                    <div class="card-header bg-white">

                        <strong>
                            Revisión del reporte
                        </strong>

                    </div>

                    <div class="card-body">

                        <form
                            action="{{ route('reportes-avance.revisar', $reporte->id) }}"
                            method="POST">

                            @csrf
                            @method('PATCH')


                            <div class="mb-3">

                                <label class="form-label">
                                    Comentario
                                </label>

                                <textarea
                                    name="comentario_revision"
                                    rows="4"
                                    class="form-control"
                                    placeholder="Ingrese alguna observación o comentario...">{{ old('comentario_revision', $reporte->comentario_revision) }}</textarea>

                            </div>


                            <div class="d-flex gap-2">

                                <button
                                    type="submit"
                                    name="estado"
                                    value="REVISADO"
                                    class="btn btn-success">

                                    Marcar como revisado

                                </button>


                                <button
                                    type="submit"
                                    name="estado"
                                    value="OBSERVADO"
                                    class="btn btn-danger">

                                    Observar reporte

                                </button>

                            </div>

                        </form>

                    </div>

                </div>

            @endif


            {{-- COMENTARIO DE REVISION PARA EL LOCADOR --}}
            @if(
                auth()->user()->rol->nombre === 'TRABAJADOR'
                &&
                $reporte->comentario_revision
            )

                <div class="card shadow-sm mt-4">

                    <div class="card-header">

                        <strong>
                            Comentario de revisión
                        </strong>

                    </div>

                    <div class="card-body">

                        {{ $reporte->comentario_revision }}

                    </div>

                </div>

            @endif

        </div>

    </div>

</div>

@endsection