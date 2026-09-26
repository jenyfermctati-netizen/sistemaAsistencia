@extends('layouts.app')

@section('title', 'Editar Reporte')

@section('content')

<div class="container py-4">

    <div class="row justify-content-center">

        <div class="col-lg-9">

            <div class="card shadow-sm">

                <div class="card-header bg-white">

                    <h4 class="mb-0">
                        Editar Reporte de Avance
                    </h4>

                </div>


                <div class="card-body">

                    @if($errors->any())

                        <div class="alert alert-danger">

                            <ul class="mb-0">

                                @foreach($errors->all() as $error)

                                    <li>
                                        {{ $error }}
                                    </li>

                                @endforeach

                            </ul>

                        </div>

                    @endif


                    <form
                        method="POST"
                        action="{{ route('reportes-avance.update', $reporte->id) }}"
                        enctype="multipart/form-data">

                        @csrf
                        @method('PUT')


                        <div class="row">

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Inicio del periodo
                                </label>

                                <input
                                    type="date"
                                    name="periodo_inicio"
                                    value="{{ old('periodo_inicio', $reporte->periodo_inicio) }}"
                                    class="form-control"
                                    required>

                            </div>


                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Fin del periodo
                                </label>

                                <input
                                    type="date"
                                    name="periodo_fin"
                                    value="{{ old('periodo_fin', $reporte->periodo_fin) }}"
                                    class="form-control"
                                    required>

                            </div>

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Porcentaje de avance
                            </label>

                            <div class="input-group">

                                <input
                                    type="number"
                                    name="porcentaje_avance"
                                    value="{{ old('porcentaje_avance', $reporte->porcentaje_avance) }}"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    class="form-control">

                                <span class="input-group-text">
                                    %
                                </span>

                            </div>

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Descripción
                            </label>

                            <textarea
                                name="descripcion"
                                rows="6"
                                class="form-control"
                                required>{{ old('descripcion', $reporte->descripcion) }}</textarea>

                        </div>


                        <div class="mb-4">

                            <label class="form-label">
                                Cambiar archivo
                            </label>

                            <input
                                type="file"
                                name="archivo"
                                class="form-control">


                            @if($reporte->archivo)

                                <small class="text-muted">

                                    Actualmente existe un archivo adjunto.

                                    <a
                                        href="{{ asset('storage/' . $reporte->archivo) }}"
                                        target="_blank">

                                        Ver archivo

                                    </a>

                                </small>

                            @endif

                        </div>


                        <div class="d-flex justify-content-end gap-2">

                            <a
                                href="{{ route('reportes-avance.show', $reporte->id) }}"
                                class="btn btn-light">

                                Cancelar

                            </a>

                            <button
                                type="submit"
                                class="btn btn-primary">

                                Guardar cambios

                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection