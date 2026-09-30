@extends('layouts.app')

@section('title', 'Horarios')

@section('page-title', 'Horarios')

@section('page-subtitle', 'Configuración y asignación de horarios laborales')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/horarios.css') }}">
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


    {{-- ENCABEZADO --}}
    <div class="page-header">

        <div>
            <h2 class="page-header__title">
                Horarios
            </h2>

            <p class="page-header__description">
                Configura los horarios semanales y asígnalos al personal.
            </p>
        </div>

        <div class="page-header__actions">

            <a href="{{ route('trabajadores.index') }}" class="button button--secondary">
                ← Trabajadores
            </a>

            <button type="button" class="button button--secondary" data-modal-open="modalAsignarHorario">
                Asignar horario
            </button>

            <button type="button" class="button button--primary" data-modal-open="modalCrearHorario">
                + Nuevo horario
            </button>

        </div>

    </div>


    {{-- HORARIOS --}}
    <section class="panel">

        <div class="panel__header">

            <form method="GET" action="{{ route('trabajadores.horarios') }}" class="horarios-filtros">

                <input type="text" name="buscar" value="{{ request('buscar') }}" class="form-control"
                    placeholder="Buscar horario...">

                <select name="estado" class="form-select">
                    <option value="">
                        Todos los estados
                    </option>

                    <option value="1" @selected(request('estado') === '1')>
                        Activos
                    </option>

                    <option value="0" @selected(request('estado') === '0')>
                        Inactivos
                    </option>
                </select>

                <button type="submit" class="button button--secondary">
                    Filtrar
                </button>

                <a href="{{ route('trabajadores.horarios') }}" class="button button--secondary">
                    Limpiar
                </a>

            </form>

        </div>


        <div class="horarios-grid">

            @forelse($horarios as $horario)

                @php
                    $diasHorario = [];

                    foreach ($horario->dias as $dia) {
                        $diasHorario[] = [
                            'dia_semana' => (int) $dia->dia_semana,
                            'es_laborable' => (bool) $dia->es_laborable,
                            'hora_entrada' => $dia->hora_entrada ? substr($dia->hora_entrada, 0, 5) : '',
                            'hora_salida' => $dia->hora_salida ? substr($dia->hora_salida, 0, 5) : '',
                            'tolerancia_minutos' => (int) $dia->tolerancia_minutos,
                        ];
                    }

                    $diasHorarioJson = json_encode($diasHorario);
                @endphp


                <article class="horario-card">

                    <div class="horario-card__header">

                        <div>

                            <h3 class="horario-card__title">
                                {{ $horario->nombre }}
                            </h3>

                            <p class="horario-card__description">
                                {{ $horario->descripcion ?: 'Sin descripción' }}
                            </p>

                        </div>


                        @if ($horario->estado)
                            <span class="badge badge--success">
                                Activo
                            </span>
                        @else
                            <span class="badge badge--danger">
                                Inactivo
                            </span>
                        @endif

                    </div>


                    <div class="horario-card__dias">

                        @forelse($horario->dias as $dia)
                            <div class="horario-dia">

                                <div>

                                    <strong>
                                        {{ $diasSemana[$dia->dia_semana] ?? 'Día' }}
                                    </strong>

                                    @if ($dia->es_laborable)
                                        <span>
                                            {{ substr($dia->hora_entrada, 0, 5) }}
                                            -
                                            {{ substr($dia->hora_salida, 0, 5) }}
                                        </span>
                                    @else
                                        <span class="horario-dia__libre">
                                            No laborable
                                        </span>
                                    @endif

                                </div>


                                @if ($dia->es_laborable)
                                    <small>
                                        Tolerancia:
                                        {{ $dia->tolerancia_minutos }}
                                        min.
                                    </small>
                                @endif

                            </div>

                        @empty

                            <div class="table-empty">
                                Este horario todavía no tiene días configurados.
                            </div>
                        @endforelse

                    </div>


                    <div class="horario-card__footer">

                        <small class="table-secondary-text">
                            {{ $horario->trabajador_horarios_count }}
                            asignación(es)
                        </small>


                        <div class="table-actions">

                            <button type="button" class="button button--secondary button--small" data-editar-horario
                                data-url="{{ route('trabajadores.horarios.update', $horario) }}"
                                data-nombre="{{ $horario->nombre }}" data-descripcion="{{ $horario->descripcion }}"
                                data-dias="{{ e($diasHorarioJson) }}">
                                Editar
                            </button>


                            <button type="button"
                                class="button button--small {{ $horario->estado ? 'button--danger' : 'button--primary' }}"
                                data-cambiar-estado-horario data-nombre="{{ $horario->nombre }}"
                                data-estado="{{ $horario->estado ? 1 : 0 }}"
                                data-url="{{ route('trabajadores.horarios.estado', $horario) }}">
                                {{ $horario->estado ? 'Desactivar' : 'Activar' }}
                            </button>

                        </div>

                    </div>

                </article>

            @empty

                <div class="table-empty">
                    No hay horarios registrados.
                </div>

            @endforelse

        </div>

    </section>


    <div class="pagination-container">
        {{ $horarios->links() }}
    </div>


    {{-- HISTORIAL DE ASIGNACIONES --}}
    <section class="panel horarios-asignaciones">

        <div class="panel__header">

            <div>

                <h3>
                    Asignaciones de horarios
                </h3>

                <p class="table-secondary-text">
                    Historial de horarios asignados al personal.
                </p>

            </div>

        </div>


        <div class="table-container">

            <table class="table">

                <thead>

                    <tr>
                        <th>Trabajador</th>
                        <th>Área</th>
                        <th>Horario</th>
                        <th>Control</th>
                        <th>Desde</th>
                        <th>Hasta</th>
                        <th>Estado</th>
                    </tr>

                </thead>

                <tbody>

                    @forelse($asignaciones as $asignacion)
                        <tr>

                            <td>

                                <strong>
                                    {{ $asignacion->trabajador?->nombres }}
                                    {{ $asignacion->trabajador?->apellidos }}
                                </strong>

                                <div class="table-secondary-text">
                                    {{ $asignacion->trabajador?->tipo_vinculo ?? '-' }}
                                </div>

                            </td>


                            <td>
                                {{ $asignacion->trabajador?->area?->nombre ?? '-' }}
                            </td>


                            <td>
                                {{ $asignacion->horario?->nombre ?? '-' }}
                            </td>


                            <td>

                                @if ($asignacion->tipo_control === 'OBLIGATORIO')
                                    <span class="badge badge--primary">
                                        Obligatorio
                                    </span>
                                @else
                                    <span class="badge badge--info">
                                        Referencial
                                    </span>
                                @endif

                            </td>


                            <td>
                                {{ $asignacion->fecha_inicio?->format('d/m/Y') ?? '-' }}
                            </td>


                            <td>

                                @if ($asignacion->fecha_fin)
                                    {{ $asignacion->fecha_fin->format('d/m/Y') }}
                                @else
                                    Actual
                                @endif

                            </td>


                            <td>

                                @if ($asignacion->estado)
                                    <span class="badge badge--success">
                                        Vigente
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

                            <td colspan="7" class="table-empty">
                                Todavía no existen asignaciones.
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


    {{-- MODAL CREAR --}}
    <x-modal id="modalCrearHorario" title="Nuevo horario" size="large">

        <form method="POST" action="{{ route('trabajadores.horarios.store') }}" class="form">

            @csrf


            <div class="form-grid form-grid--2">

                <div class="form-group">

                    <label class="form-label">
                        Nombre *
                    </label>

                    <input type="text" name="nombre" value="{{ old('nombre') }}" class="form-control" maxlength="100"
                        required>

                </div>


                <div class="form-group">

                    <label class="form-label">
                        Descripción
                    </label>

                    <input type="text" name="descripcion" value="{{ old('descripcion') }}" class="form-control"
                        maxlength="300">

                </div>

            </div>


            <div class="form-section">

                <h3 class="form-section__title">
                    Configuración semanal
                </h3>


                <div class="dias-editor">

                    @foreach ($diasSemana as $numero => $nombre)
                        <div class="dia-editor">

                            <input type="hidden" name="dias[{{ $numero }}][dia_semana]"
                                value="{{ $numero }}">

                            <input type="hidden" name="dias[{{ $numero }}][es_laborable]" value="0">


                            <label class="dia-editor__nombre">

                                <input type="checkbox" name="dias[{{ $numero }}][es_laborable]" value="1"
                                    class="dia-laborable" @checked($numero <= 6)>

                                {{ $nombre }}

                            </label>


                            <div class="form-group">

                                <label class="form-label">
                                    Entrada
                                </label>

                                <input type="time" name="dias[{{ $numero }}][hora_entrada]"
                                    class="form-control dia-hora hora-entrada" value="{{ $numero <= 6 ? '08:00' : '' }}">

                            </div>


                            <div class="form-group">

                                <label class="form-label">
                                    Salida
                                </label>

                                <input type="time" name="dias[{{ $numero }}][hora_salida]"
                                    class="form-control dia-hora hora-salida"
                                    @if ($numero === 6) value="13:00"
                                    @elseif($numero <= 5)
                                        value="17:00"
                                    @else
                                        value="" @endif>

                            </div>


                            <div class="form-group">

                                <label class="form-label">
                                    Tolerancia
                                </label>

                                <input type="number" name="dias[{{ $numero }}][tolerancia_minutos]"
                                    class="form-control dia-hora tolerancia" value="0" min="0"
                                    max="180">

                            </div>

                        </div>
                    @endforeach

                </div>

            </div>


            <div class="modal__actions">

                <button type="button" class="button button--secondary" data-modal-close>
                    Cancelar
                </button>

                <button type="submit" class="button button--primary">
                    Guardar horario
                </button>

            </div>

        </form>

    </x-modal>


    {{-- MODAL EDITAR --}}
    <x-modal id="modalEditarHorario" title="Editar horario" size="large">

        <form method="POST" id="formEditarHorario" class="form">

            @csrf
            @method('PUT')


            <div class="form-grid form-grid--2">

                <div class="form-group">

                    <label class="form-label">
                        Nombre *
                    </label>

                    <input id="editarHorarioNombre" type="text" name="nombre" class="form-control" maxlength="100"
                        required>

                </div>


                <div class="form-group">

                    <label class="form-label">
                        Descripción
                    </label>

                    <input id="editarHorarioDescripcion" type="text" name="descripcion" class="form-control"
                        maxlength="300">

                </div>

            </div>


            <div class="form-section">

                <h3 class="form-section__title">
                    Configuración semanal
                </h3>


                <div class="dias-editor" id="editarDiasHorario">

                    @foreach ($diasSemana as $numero => $nombre)
                        <div class="dia-editor" data-dia="{{ $numero }}">

                            <input type="hidden" name="dias[{{ $numero }}][dia_semana]"
                                value="{{ $numero }}">

                            <input type="hidden" name="dias[{{ $numero }}][es_laborable]" value="0">


                            <label class="dia-editor__nombre">

                                <input type="checkbox" name="dias[{{ $numero }}][es_laborable]" value="1"
                                    class="dia-laborable">

                                {{ $nombre }}

                            </label>


                            <div class="form-group">

                                <label class="form-label">
                                    Entrada
                                </label>

                                <input type="time" name="dias[{{ $numero }}][hora_entrada]"
                                    class="form-control dia-hora hora-entrada">

                            </div>


                            <div class="form-group">

                                <label class="form-label">
                                    Salida
                                </label>

                                <input type="time" name="dias[{{ $numero }}][hora_salida]"
                                    class="form-control dia-hora hora-salida">

                            </div>


                            <div class="form-group">

                                <label class="form-label">
                                    Tolerancia
                                </label>

                                <input type="number" name="dias[{{ $numero }}][tolerancia_minutos]"
                                    class="form-control dia-hora tolerancia" value="0" min="0"
                                    max="180">

                            </div>

                        </div>
                    @endforeach

                </div>

            </div>


            <div class="modal__actions">

                <button type="button" class="button button--secondary" data-modal-close>
                    Cancelar
                </button>

                <button type="submit" class="button button--primary">
                    Guardar cambios
                </button>

            </div>

        </form>

    </x-modal>


    {{-- MODAL ASIGNAR --}}
    <x-modal id="modalAsignarHorario" title="Asignar horario">

        <form method="POST" action="{{ route('trabajadores.horarios.asignar') }}" class="form">

            @csrf


            <div class="form-group">

                <label class="form-label">
                    Trabajador *
                </label>

                <select name="trabajador_id" id="asignarTrabajador" class="form-select" required>

                    <option value="">
                        Seleccione un trabajador
                    </option>

                    @foreach ($trabajadores as $trabajador)
                        <option value="{{ $trabajador->id }}" data-vinculo="{{ $trabajador->tipo_vinculo }}">
                            {{ $trabajador->apellidos }},
                            {{ $trabajador->nombres }}
                            -
                            {{ $trabajador->tipo_vinculo }}
                        </option>
                    @endforeach

                </select>

            </div>


            <div id="tipoControlInfo" class="form-help-box">
                Seleccione un trabajador para determinar el tipo de control.
            </div>


            <div class="form-group">

                <label class="form-label">
                    Horario *
                </label>

                <select name="horario_id" class="form-select" required>

                    <option value="">
                        Seleccione un horario
                    </option>

                    @foreach ($horariosActivos as $horario)
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

                <input type="date" name="fecha_inicio" value="{{ old('fecha_inicio', now()->toDateString()) }}"
                    class="form-control" required>

            </div>


            <div class="modal__actions">

                <button type="button" class="button button--secondary" data-modal-close>
                    Cancelar
                </button>

                <button type="submit" class="button button--primary">
                    Asignar horario
                </button>

            </div>

        </form>

    </x-modal>


    {{-- MODAL ESTADO --}}
    <x-modal id="modalEstadoHorario" title="Cambiar estado" size="small">

        <p id="estadoHorarioMensaje"></p>


        <form method="POST" id="formEstadoHorario">

            @csrf
            @method('PATCH')


            <div class="modal__actions">

                <button type="button" class="button button--secondary" data-modal-close>
                    Cancelar
                </button>

                <button type="submit" id="btnEstadoHorario" class="button button--danger">
                    Confirmar
                </button>

            </div>

        </form>

    </x-modal>

@endsection


@push('scripts')
    <script src="{{ asset('js/pages/horarios.js') }}"></script>
@endpush
