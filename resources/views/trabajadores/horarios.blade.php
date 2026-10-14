@extends('layouts.app')

@section('title', 'Horario general')
@section('page-title', 'Horario general')
@section('page-subtitle', 'Configuración del horario único de la empresa')

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
        $diasConfigurados = $horario?->dias?->keyBy('dia_semana') ?? collect();
        $formatoSeleccionado = old('formato_hora', $horario?->formato_hora ?? '24');
    @endphp

    <div class="page-header">
        <div>
            <h2 class="page-header__title">Horario general</h2>
            <p class="page-header__description">
                Este horario se aplica automáticamente a todos los trabajadores activos.
            </p>
        </div>

        <div class="page-header__actions">
            <a href="{{ route('trabajadores.index') }}" class="button button--secondary">
                ← Trabajadores
            </a>
        </div>
    </div>

    <section class="panel horario-general">
        <div class="panel__header horario-general__header">
            <div>
                <h3>{{ $horario ? 'Editar horario' : 'Configurar horario' }}</h3>
                <p class="table-secondary-text">
                    Las horas se guardan sin perder precisión aunque cambies entre formato de 12 y 24 horas.
                </p>
            </div>

            @if ($horario)
                <span class="badge badge--success">Vigente</span>
            @endif
        </div>

        <form method="POST"
            action="{{ $horario ? route('trabajadores.horarios.update', $horario) : route('trabajadores.horarios.store') }}"
            class="form horario-general__form" id="formHorarioGeneral" novalidate>
            @csrf
            @if ($horario)
                @method('PUT')
            @endif

            @if ($errors->any())
                <div class="form-errors" role="alert">
                    <strong>No se pudo guardar el horario.</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="form-grid form-grid--3">
                <div class="form-group">
                    <label class="form-label" for="horarioNombre">Nombre *</label>
                    <input id="horarioNombre" type="text" name="nombre"
                        value="{{ old('nombre', $horario?->nombre ?? 'Horario general') }}" class="form-control"
                        maxlength="100" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="horarioDescripcion">Descripción</label>
                    <input id="horarioDescripcion" type="text" name="descripcion"
                        value="{{ old('descripcion', $horario?->descripcion) }}" class="form-control" maxlength="300">
                </div>

                <div class="form-group">
                    <label class="form-label" for="formatoHora">Formato de hora *</label>
                    <select id="formatoHora" name="formato_hora" class="form-select" required>
                        <option value="24" @selected($formatoSeleccionado === '24')>24 horas (18:30)</option>
                        <option value="12" @selected($formatoSeleccionado === '12')>12 horas (06:30 PM)</option>
                    </select>
                </div>
            </div>

            <div class="form-help-box">
                <strong>Un solo horario para toda la empresa.</strong>
                Al guardar, esta configuración queda vinculada automáticamente a cada trabajador activo. Los
                contratados mantienen control obligatorio y los locadores control referencial.
            </div>

            <div class="form-section">
                <div class="form-section__heading">
                    <div>
                        <h3 class="form-section__title">Configuración semanal</h3>
                        <p class="table-secondary-text">Marca los días laborables e indica entrada, salida y tolerancia.</p>
                    </div>
                    <span class="formato-indicador" id="formatoIndicador"></span>
                </div>

                <div class="dias-editor">
                    @foreach ($diasSemana as $numero => $nombre)
                        @php
                            $dia = $diasConfigurados->get($numero);
                            $esLaborable = (string) old(
                                "dias.$numero.es_laborable",
                                $dia ? (int) $dia->es_laborable : ($numero <= 6 ? 1 : 0),
                            ) === '1';
                            $entrada = old(
                                "dias.$numero.hora_entrada",
                                $dia?->hora_entrada ? substr($dia->hora_entrada, 0, 5) : ($numero <= 6 ? '08:00' : ''),
                            );
                            $salidaPredeterminada = $numero === 6 ? '13:00' : ($numero <= 5 ? '17:00' : '');
                            $salida = old(
                                "dias.$numero.hora_salida",
                                $dia?->hora_salida ? substr($dia->hora_salida, 0, 5) : $salidaPredeterminada,
                            );
                            $tolerancia = old(
                                "dias.$numero.tolerancia_minutos",
                                $dia?->tolerancia_minutos ?? 0,
                            );
                        @endphp

                        <div class="dia-editor" data-dia="{{ $numero }}">
                            <input type="hidden" name="dias[{{ $numero }}][dia_semana]" value="{{ $numero }}">
                            <input type="hidden" name="dias[{{ $numero }}][es_laborable]" value="0">

                            <label class="dia-editor__nombre">
                                <input type="checkbox" name="dias[{{ $numero }}][es_laborable]" value="1"
                                    class="dia-laborable" @checked($esLaborable)>
                                <span>{{ $nombre }}</span>
                            </label>

                            @foreach (['entrada' => 'Entrada', 'salida' => 'Salida'] as $tipo => $etiqueta)
                                @php($hora = $tipo === 'entrada' ? $entrada : $salida)
                                <div class="form-group hora-editor" data-hora-editor>
                                    <label class="form-label">{{ $etiqueta }}</label>
                                    <input type="hidden" name="dias[{{ $numero }}][hora_{{ $tipo }}]"
                                        value="{{ $hora }}" class="hora-normalizada" data-hora-normalizada>

                                    <input type="text" inputmode="numeric" autocomplete="off" maxlength="5"
                                        placeholder="HH:mm" value="{{ $hora }}"
                                        class="form-control dia-hora hora-campo hora-campo--24" data-hora-24
                                        aria-label="{{ $etiqueta }} del {{ strtolower($nombre) }} en formato de 24 horas">

                                    <div class="hora-12" data-hora-12>
                                        <input type="text" inputmode="numeric" autocomplete="off" placeholder="hh:mm"
                                            class="form-control dia-hora hora-campo hora-campo--12"
                                            data-hora-12-texto maxlength="5"
                                            aria-label="{{ $etiqueta }} del {{ strtolower($nombre) }} en formato de 12 horas">
                                        <select class="form-select dia-hora hora-periodo" data-hora-periodo
                                            aria-label="Periodo de {{ strtolower($etiqueta) }} del {{ strtolower($nombre) }}">
                                            <option value="AM">AM</option>
                                            <option value="PM">PM</option>
                                        </select>
                                    </div>
                                </div>
                            @endforeach

                            <div class="form-group">
                                <label class="form-label">Tolerancia (min)</label>
                                <input type="number" name="dias[{{ $numero }}][tolerancia_minutos]"
                                    class="form-control dia-hora tolerancia" value="{{ $tolerancia }}" min="0"
                                    max="180">
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="modal__actions horario-general__actions">
                <a href="{{ route('trabajadores.index') }}" class="button button--secondary">Cancelar</a>
                <button type="submit" class="button button--primary">
                    {{ $horario ? 'Guardar cambios' : 'Guardar horario' }}
                </button>
            </div>
        </form>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('js/pages/horarios.js') }}"></script>
@endpush
