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

    <div class="horario-page">
        <div class="page-header horario-page__header">
            <div>
                <span class="horario-page__eyebrow">Configuración global</span>
                <h2 class="page-header__title">Horario general</h2>
                <p class="page-header__description">
                    Define la semana laboral que se aplicará automáticamente a todo el personal activo.
                </p>
            </div>

            <div class="page-header__actions horario-page__header-actions">
                <span class="horario-page__scope">
                    <x-ui-icon name="users" />
                    Todo el personal activo
                </span>
                <a href="{{ route('trabajadores.index') }}" class="button button--secondary">
                    <x-ui-icon name="arrow-left" /> Trabajadores
                </a>
            </div>
        </div>

        <section class="panel horario-general" aria-labelledby="horarioEditorTitulo">
            <div class="panel__header horario-general__header">
                <div>
                    <span class="horario-general__eyebrow">Editor semanal</span>
                    <h3 id="horarioEditorTitulo">{{ $horario ? 'Organiza el horario vigente' : 'Configura el horario de la empresa' }}</h3>
                    <p>Completa los datos generales y después revisa cada día antes de guardar.</p>
                </div>

                @if ($horario)
                    <span class="horario-general__status">
                        <span aria-hidden="true"></span>
                        Configuración activa
                    </span>
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
                    <div class="form-errors" role="alert" tabindex="-1">
                        <strong>No se pudo guardar el horario.</strong>
                        <p>Revisa los siguientes datos:</p>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <section class="horario-step" aria-labelledby="datosHorarioTitulo">
                    <div class="horario-step__heading">
                        <span class="horario-step__number" aria-hidden="true">1</span>
                        <div>
                            <h3 id="datosHorarioTitulo">Datos del horario</h3>
                            <p>Identifica esta configuración y elige cómo prefieres visualizar las horas.</p>
                        </div>
                    </div>

                    <div class="form-grid form-grid--3 horario-general__metadata">
                        <div class="form-group">
                            <label class="form-label" for="horarioNombre">Nombre del horario *</label>
                            <input id="horarioNombre" type="text" name="nombre"
                                value="{{ old('nombre', $horario?->nombre ?? 'Horario general') }}"
                                class="form-control" maxlength="100" required
                                aria-describedby="horarioNombreAyuda">
                            <small id="horarioNombreAyuda" class="form-help">Ejemplo: Horario de oficina.</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="horarioDescripcion">Descripción</label>
                            <input id="horarioDescripcion" type="text" name="descripcion"
                                value="{{ old('descripcion', $horario?->descripcion) }}"
                                class="form-control" maxlength="300"
                                placeholder="Describe a quién o para qué aplica">
                            <small class="form-help">Opcional. Ayuda a reconocer la configuración.</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="formatoHora">Formato de hora *</label>
                            <select id="formatoHora" name="formato_hora" class="form-select" required>
                                <option value="24" @selected($formatoSeleccionado === '24')>24 horas · 18:30</option>
                                <option value="12" @selected($formatoSeleccionado === '12')>12 horas · 06:30 PM</option>
                            </select>
                            <small class="form-help">Solo cambia la visualización; las horas conservan su precisión.</small>
                        </div>
                    </div>

                    <div class="horario-step__support">
                        <div class="horario-scope-note">
                            <span class="horario-scope-note__icon"><x-ui-icon name="users" /></span>
                            <div>
                                <strong>Aplicación automática</strong>
                                <p>Al guardar, este horario se vinculará con todos los trabajadores activos.</p>
                            </div>
                        </div>

                        <details class="form-help-box horario-help">
                            <summary>
                                <x-ui-icon name="clock" />
                                ¿Qué significa cada tipo de jornada?
                            </summary>
                            <div class="horario-help__content">
                                <p><strong>Jornada completa:</strong> requiere entrada, inicio y fin del refrigerio, y salida final.</p>
                                <p><strong>Media jornada:</strong> solo requiere entrada y salida; el refrigerio no aplica.</p>
                                <p><strong>Tolerancia:</strong> minutos permitidos después de la hora de entrada antes de considerar tardanza.</p>
                            </div>
                        </details>
                    </div>
                </section>

                <section class="horario-step horario-step--week" aria-labelledby="semanaHorarioTitulo">
                    <div class="horario-step__heading horario-step__heading--week">
                        <span class="horario-step__number" aria-hidden="true">2</span>
                        <div>
                            <h3 id="semanaHorarioTitulo">Configura la semana</h3>
                            <p>Activa los días laborables y define el recorrido horario de cada jornada.</p>
                        </div>

                        <div class="horario-bulk-actions">
                            <span class="formato-indicador" id="formatoIndicador"></span>
                            <button type="button" class="button button--secondary button--small" id="copiarLunes">
                                <x-ui-icon name="sync" /> Copiar lunes a martes–viernes
                            </button>
                            <span class="horario-copy-status" id="horarioCopyStatus" role="status" aria-live="polite"></span>
                        </div>
                    </div>

                    <div class="horario-week-guide">
                        <div class="horario-week-guide__summary">
                            <span class="horario-week-guide__icon"><x-ui-icon name="calendar" /></span>
                            <div>
                                <strong id="horarioResumenSemana">Semana sin revisar</strong>
                                <span>Desmarca un día para convertirlo en descanso.</span>
                            </div>
                        </div>

                        <ol class="horario-flow" aria-label="Orden de una jornada completa">
                            <li><span>1</span>Entrada</li>
                            <li><span>2</span>Inicio refrigerio</li>
                            <li><span>3</span>Fin refrigerio</li>
                            <li><span>4</span>Salida</li>
                        </ol>
                    </div>

                    <div class="dias-editor__columns" aria-hidden="true">
                        <span>Día y jornada</span>
                        <span>Entrada</span>
                        <span>Inicio refrigerio</span>
                        <span>Fin refrigerio</span>
                        <span>Salida</span>
                        <span>Tolerancia</span>
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
                                    $dia?->hora_entrada ? substr($dia->hora_entrada, 0, 5) : ($numero <= 6 ? '08:20' : ''),
                                );
                                $salidaRefrigerio = old(
                                    "dias.$numero.hora_salida_refrigerio",
                                    $dia?->hora_salida_refrigerio ? substr($dia->hora_salida_refrigerio, 0, 5) : ($numero <= 5 ? '13:00' : ''),
                                );
                                $retornoRefrigerio = old(
                                    "dias.$numero.hora_retorno_refrigerio",
                                    $dia?->hora_retorno_refrigerio ? substr($dia->hora_retorno_refrigerio, 0, 5) : ($numero <= 5 ? '14:00' : ''),
                                );
                                $salidaPredeterminada = $numero <= 5 ? '19:00' : ($numero === 6 ? '13:00' : '');
                                $salida = old(
                                    "dias.$numero.hora_salida",
                                    $dia?->hora_salida ? substr($dia->hora_salida, 0, 5) : $salidaPredeterminada,
                                );
                                $tolerancia = old(
                                    "dias.$numero.tolerancia_minutos",
                                    $dia?->tolerancia_minutos ?? 20,
                                );
                                $tipoJornadaPredeterminado = $numero === 6 ? 'MEDIA' : 'COMPLETA';
                                $tipoJornadaGuardado = $dia
                                    ? ($dia->hora_salida_refrigerio && $dia->hora_retorno_refrigerio ? 'COMPLETA' : 'MEDIA')
                                    : $tipoJornadaPredeterminado;
                                $tipoJornada = old("dias.$numero.tipo_jornada", $tipoJornadaGuardado);
                            @endphp

                            <article class="dia-editor {{ !$esLaborable ? 'dia-editor--descanso' : '' }} {{ $tipoJornada === 'MEDIA' ? 'dia-editor--media' : '' }}"
                                data-dia="{{ $numero }}">
                                <input type="hidden" name="dias[{{ $numero }}][dia_semana]" value="{{ $numero }}">
                                <input type="hidden" name="dias[{{ $numero }}][es_laborable]" value="0">

                                <div class="dia-editor__config">
                                    <div class="dia-editor__topline">
                                        <label class="dia-editor__nombre">
                                            <input type="checkbox" name="dias[{{ $numero }}][es_laborable]" value="1"
                                                class="dia-laborable" @checked($esLaborable)>
                                            <span>{{ $nombre }}</span>
                                        </label>

                                        <span class="dia-editor__estado">
                                            {{ $esLaborable ? ($tipoJornada === 'MEDIA' ? 'Media jornada' : 'Completa') : 'Descanso' }}
                                        </span>
                                    </div>

                                    <p class="dia-editor__summary" data-dia-summary></p>

                                    <label class="dia-editor__journey">
                                        <span class="dia-editor__journey-label">Tipo de jornada</span>
                                        <select name="dias[{{ $numero }}][tipo_jornada]" class="form-select tipo-jornada"
                                            data-tipo-jornada aria-label="Tipo de jornada del {{ strtolower($nombre) }}">
                                            <option value="COMPLETA" @selected($tipoJornada === 'COMPLETA')>Jornada completa</option>
                                            <option value="MEDIA" @selected($tipoJornada === 'MEDIA')>Media jornada</option>
                                        </select>
                                    </label>
                                </div>

                                @foreach ([
                                    'entrada' => ['Entrada', $entrada],
                                    'salida_refrigerio' => ['Inicio del refrigerio', $salidaRefrigerio],
                                    'retorno_refrigerio' => ['Fin del refrigerio', $retornoRefrigerio],
                                    'salida' => ['Salida', $salida],
                                ] as $tipo => [$etiqueta, $hora])
                                    <div class="form-group hora-editor" data-hora-editor data-hora-tipo="{{ $tipo }}">
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

                                        <span class="hora-editor__empty">No aplica</span>
                                    </div>
                                @endforeach

                                <div class="form-group dia-editor__tolerance">
                                    <label class="form-label">Tolerancia</label>
                                    <div class="dia-editor__minutes">
                                        <input type="number" name="dias[{{ $numero }}][tolerancia_minutos]"
                                            class="form-control dia-hora tolerancia" value="{{ $tolerancia }}" min="0"
                                            max="180" aria-label="Tolerancia del {{ strtolower($nombre) }} en minutos">
                                        <span>min</span>
                                    </div>
                                </div>

                                <p class="dia-editor__feedback" data-dia-feedback role="alert" hidden></p>
                            </article>
                        @endforeach
                    </div>
                </section>

                <div class="horario-general__actions">
                    <div class="horario-save-summary">
                        <span class="horario-save-summary__icon"><x-ui-icon name="calendar" /></span>
                        <div>
                            <strong id="horarioResumenGuardado">Revisa la configuración semanal</strong>
                            <span id="horarioFormStatus" role="status" aria-live="polite">Los cambios se aplicarán al guardar.</span>
                        </div>
                    </div>

                    <div class="horario-general__action-buttons">
                        <a href="{{ route('trabajadores.index') }}" class="button button--secondary">Cancelar</a>
                        <button type="submit" class="button button--primary" data-horario-submit>
                            <x-ui-icon name="check" />
                            <span>{{ $horario ? 'Guardar cambios' : 'Guardar horario' }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </section>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/pages/horarios.js') }}"></script>
@endpush
