@if(!$trabajador)
    <section class="dashboard-panel dashboard-unlinked" role="status">
        <span class="dashboard-unlinked__icon"><x-ui-icon name="alert" /></span>
        <div>
            <h3>Cuenta sin trabajador vinculado</h3>
            <p>Solicita al administrador que relacione tu usuario con un trabajador para mostrar tu asistencia, área y reportes personales.</p>
        </div>
    </section>
@else
    @php
        $estadoHoy = $asistenciaHoy?->estado ?? 'SIN_PROCESAR';
        $etiquetasEstado = [
            'PRESENTE' => 'Presente',
            'TARDANZA' => 'Tardanza',
            'FALTA' => 'Falta',
            'FJ' => 'Falta justificada',
            'TJ' => 'Tardanza justificada',
            'JUSTIFICADO' => 'Justificado',
            'CAMPO' => 'Trabajo en campo',
            'VACACIONES' => 'Vacaciones',
            'PERMISO' => 'Permiso',
            'FERIADO' => 'Feriado',
            'NO_LABORABLE' => 'No laborable',
            'SIN_REGISTRO' => 'Sin registro',
            'SIN_PROCESAR' => 'Aún sin procesar',
        ];
        $etiquetaHoy = $etiquetasEstado[$estadoHoy] ?? ucfirst(strtolower(str_replace('_', ' ', $estadoHoy)));
        $tieneRefrigerio = $asistenciaHoy && (
            $asistenciaHoy->hora_programada_salida_refrigerio
            || $asistenciaHoy->hora_salida_refrigerio
            || $asistenciaHoy->hora_retorno_refrigerio
        );
        $iniciales = mb_strtoupper(
            mb_substr(trim((string) $trabajador->nombres), 0, 1)
            .mb_substr(trim((string) $trabajador->apellidos), 0, 1)
        );
    @endphp

    <section class="dashboard-today dashboard-today--{{ strtolower(str_replace('_', '-', $estadoHoy)) }}" aria-labelledby="dashboardTodayTitle">
        <div class="dashboard-today__status">
            <span class="dashboard-panel__eyebrow">TU JORNADA · {{ now()->locale('es')->translatedFormat('l d \d\e F') }}</span>
            <div class="dashboard-today__heading">
                <h3 id="dashboardTodayTitle">{{ $etiquetaHoy }}</h3>
                <span class="dashboard-today__badge">{{ $asistenciaHoy ? 'Procesado' : 'Pendiente' }}</span>
            </div>
            <p>
                {{ $asistenciaHoy
                    ? 'Estos son los horarios consolidados de tu jornada de hoy.'
                    : 'Tu resumen aparecerá cuando la asistencia del día sea procesada.' }}
            </p>

            <div class="dashboard-timeline" aria-label="Horarios de la jornada de hoy">
                <div class="dashboard-timeline__item {{ $asistenciaHoy?->hora_entrada ? 'is-complete' : '' }}">
                    <span class="dashboard-timeline__dot" aria-hidden="true"></span>
                    <small>Entrada</small>
                    <strong>{{ $asistenciaHoy?->hora_entrada ? substr($asistenciaHoy->hora_entrada, 0, 5) : '—' }}</strong>
                    <span>{{ $asistenciaHoy?->hora_entrada ? 'Registrada' : 'Pendiente' }}</span>
                </div>
                @if($tieneRefrigerio)
                    <div class="dashboard-timeline__item {{ $asistenciaHoy?->hora_salida_refrigerio ? 'is-complete' : '' }}">
                        <span class="dashboard-timeline__dot" aria-hidden="true"></span>
                        <small>Inicio refrigerio</small>
                        <strong>{{ $asistenciaHoy?->hora_salida_refrigerio ? substr($asistenciaHoy->hora_salida_refrigerio, 0, 5) : '—' }}</strong>
                        <span>{{ $asistenciaHoy?->hora_salida_refrigerio ? 'Registrado' : 'Pendiente' }}</span>
                    </div>
                    <div class="dashboard-timeline__item {{ $asistenciaHoy?->hora_retorno_refrigerio ? 'is-complete' : '' }}">
                        <span class="dashboard-timeline__dot" aria-hidden="true"></span>
                        <small>Retorno</small>
                        <strong>{{ $asistenciaHoy?->hora_retorno_refrigerio ? substr($asistenciaHoy->hora_retorno_refrigerio, 0, 5) : '—' }}</strong>
                        <span>{{ $asistenciaHoy?->hora_retorno_refrigerio ? 'Registrado' : 'Pendiente' }}</span>
                    </div>
                @endif
                <div class="dashboard-timeline__item {{ $asistenciaHoy?->hora_salida ? 'is-complete' : '' }}">
                    <span class="dashboard-timeline__dot" aria-hidden="true"></span>
                    <small>Salida</small>
                    <strong>{{ $asistenciaHoy?->hora_salida ? substr($asistenciaHoy->hora_salida, 0, 5) : '—' }}</strong>
                    <span>{{ $asistenciaHoy?->hora_salida ? 'Registrada' : 'Pendiente' }}</span>
                </div>
            </div>
        </div>
    </section>

    <section class="dashboard-metrics" aria-label="Tu resumen de {{ $periodoLabel }}">
        <article class="dashboard-metric dashboard-metric--petrol">
            <span class="dashboard-metric__icon"><x-ui-icon name="calendar" /></span>
            <span class="dashboard-metric__label">Días con asistencia</span>
            <strong class="dashboard-metric__value">{{ $metrics['presentes'] }}</strong>
            <span class="dashboard-metric__caption">presente, tardanza o campo</span>
        </article>
        <article class="dashboard-metric dashboard-metric--blue">
            <span class="dashboard-metric__icon"><x-ui-icon name="clock" /></span>
            <span class="dashboard-metric__label">Horas trabajadas</span>
            <strong class="dashboard-metric__value dashboard-metric__value--compact">{{ $metrics['horas'] }}</strong>
            <span class="dashboard-metric__caption">tiempo procesado este mes</span>
        </article>
        <article class="dashboard-metric dashboard-metric--sand">
            <span class="dashboard-metric__icon"><x-ui-icon name="alert" /></span>
            <span class="dashboard-metric__label">Tardanzas</span>
            <strong class="dashboard-metric__value">{{ $metrics['tardanzas'] }}</strong>
            <span class="dashboard-metric__caption">durante {{ $periodoLabel }}</span>
        </article>
        <article class="dashboard-metric dashboard-metric--graphite">
            <span class="dashboard-metric__icon"><x-ui-icon name="report" /></span>
            <span class="dashboard-metric__label">Incidencias</span>
            <strong class="dashboard-metric__value">{{ $metrics['incidencias'] }}</strong>
            <span class="dashboard-metric__caption">faltas o días sin registro</span>
        </article>
    </section>

    <div id="dashboardWorkerChartData" hidden aria-hidden="true">
        <span data-kind="status" data-label="A tiempo / campo" data-value="{{ $estadoResumenMes['puntuales'] }}"></span>
        <span data-kind="status" data-label="Tardanzas" data-value="{{ $estadoResumenMes['tardanzas'] }}"></span>
        <span data-kind="status" data-label="Justificados" data-value="{{ $estadoResumenMes['justificadas'] }}"></span>
        <span data-kind="status" data-label="Incidencias" data-value="{{ $estadoResumenMes['incidencias'] }}"></span>
    </div>

    <section class="dashboard-analytics dashboard-analytics--personal" aria-label="Resumen visual de tu asistencia mensual">
        <article class="dashboard-panel dashboard-panel--chart">
            <div class="dashboard-panel__header">
                <div>
                    <span class="dashboard-panel__eyebrow">RESUMEN MENSUAL</span>
                    <h3>Cómo se distribuyen tus días</h3>
                    <p class="dashboard-panel__description">Solo considera asistencias ya procesadas.</p>
                </div>
                <span class="dashboard-period-chip">{{ ucfirst($periodoLabel) }}</span>
            </div>
            <div class="dashboard-chart-container">
                <div id="dashboardWorkerDonut" class="dashboard-chart-host" aria-live="polite"></div>
            </div>
            <div id="dashboardWorkerDonutLegend" class="dashboard-chart-legend"></div>
        </article>

        <article class="dashboard-panel dashboard-panel--chart dashboard-panel--chart-wide dashboard-panel--calendar">
            <div class="dashboard-panel__header">
                <div>
                    <span class="dashboard-panel__eyebrow">TU MES, DÍA POR DÍA</span>
                    <h3>Calendario de asistencia</h3>
                    <p class="dashboard-panel__description">Ubica rápidamente los días correctos y los que podrían requerir revisión.</p>
                </div>
                <span class="dashboard-period-chip">{{ ucfirst($periodoLabel) }}</span>
            </div>

            <div class="dashboard-attendance-calendar">
                <div class="dashboard-attendance-calendar__weekdays" aria-hidden="true">
                    <span>Lun</span><span>Mar</span><span>Mié</span><span>Jue</span><span>Vie</span><span>Sáb</span><span>Dom</span>
                </div>
                <div class="dashboard-attendance-calendar__grid" role="list" aria-label="Estados de asistencia de {{ $periodoLabel }}">
                    @for($blank = 0; $blank < $calendarioOffset; $blank++)
                        <span class="dashboard-attendance-calendar__blank" aria-hidden="true"></span>
                    @endfor
                    @foreach($calendarioMes as $dia)
                        <span
                            class="dashboard-attendance-day dashboard-attendance-day--{{ $dia['tone'] }} {{ $dia['is_today'] ? 'is-today' : '' }}"
                            role="listitem"
                            aria-label="Día {{ $dia['day'] }}: {{ $dia['label'] }}{{ $dia['is_today'] ? ', hoy' : '' }}"
                            title="{{ str_pad($dia['day'], 2, '0', STR_PAD_LEFT) }}/{{ now()->format('m/Y') }} · {{ $dia['label'] }}"
                        >
                            <strong>{{ $dia['day'] }}</strong>
                            <small>{{ $dia['is_today'] ? 'Hoy' : $dia['label'] }}</small>
                        </span>
                    @endforeach
                </div>

                <div class="dashboard-attendance-calendar__legend" aria-label="Leyenda del calendario">
                    <span><i class="is-success"></i>A tiempo / campo</span>
                    <span><i class="is-warning"></i>Tardanza</span>
                    <span><i class="is-info"></i>Justificado</span>
                    <span><i class="is-danger"></i>Incidencia</span>
                    <span><i class="is-pending"></i>Sin procesar / futuro</span>
                </div>

                @if($metrics['incidencias'] > 0)
                    <div class="dashboard-calendar-notice dashboard-calendar-notice--warning">
                        <span class="dashboard-calendar-notice__icon"><x-ui-icon name="alert" /></span>
                        <span>
                            <strong>{{ $metrics['incidencias'] }} {{ $metrics['incidencias'] === 1 ? 'día requiere' : 'días requieren' }} tu atención</strong>
                            <small>Revisa si corresponde presentar una solicitud de regularización.</small>
                        </span>
                        <a href="#dashboardIncidencias" data-open-incidencias>Ver días y motivo <span aria-hidden="true">→</span></a>
                    </div>
                @else
                    <div class="dashboard-calendar-notice dashboard-calendar-notice--success" role="status">
                        <span class="dashboard-calendar-notice__icon"><x-ui-icon name="check" /></span>
                        <span>
                            <strong>Sin incidencias registradas este mes</strong>
                            <small>Los días en gris aún no han sido procesados o corresponden a fechas futuras.</small>
                        </span>
                    </div>
                @endif
            </div>
        </article>
    </section>

    <div class="dashboard-columns dashboard-columns--personal">
        @if($metrics['incidencias'] > 0)
            <details id="dashboardIncidencias" class="dashboard-panel worker-dashboard-disclosure worker-incidents">
                <summary class="dashboard-panel__header"><div><span class="dashboard-panel__eyebrow">REQUIEREN ATENCIÓN</span><h3>Días por revisar ({{ $incidenciasMes->count() }})</h3></div></summary>
                <div class="worker-incidents__list">
                    @foreach($incidenciasMes as $incidencia)
                        <article>
                            <strong>{{ $incidencia->fecha->format('d/m/Y') }} · {{ $etiquetasEstado[$incidencia->estado] ?? $incidencia->estado }}</strong>
                            <p>{{ $incidencia->observacion ?: 'No hay registros de asistencia suficientes para esta fecha.' }}</p>
                            <a class="button button--secondary button--small" href="{{ route('solicitudes.index', ['regularizar' => $incidencia->fecha->toDateString(), 'tipo_regularizacion' => $incidencia->estado === 'FALTA' ? 'FALTA' : 'OMISION_MARCACION']) }}">Solicitar regularización</a>
                        </article>
                    @endforeach
                </div>
            </details>
        @endif
        <details class="dashboard-panel dashboard-panel--wide worker-dashboard-disclosure">
            <summary class="dashboard-panel__header">
                <div>
                    <span class="dashboard-panel__eyebrow">HISTORIAL PERSONAL</span>
                    <h3>Últimos días procesados</h3>
                    <p class="dashboard-panel__description">Estado, horario y tiempo reconocido de cada jornada.</p>
                </div>
                <span class="dashboard-period-chip">{{ ucfirst($periodoLabel) }}</span>
            </summary>

            <div class="dashboard-days">
                @forelse($asistenciasRecientes as $asistencia)
                    <div class="dashboard-day">
                        <span class="dashboard-day__date"><strong>{{ $asistencia->fecha->format('d') }}</strong><small>{{ $asistencia->fecha->locale('es')->translatedFormat('M') }}</small></span>
                        <span>
                            <strong>{{ $etiquetasEstado[$asistencia->estado] ?? ucfirst(strtolower(str_replace('_', ' ', $asistencia->estado))) }}</strong>
                            <small>
                                {{ $asistencia->hora_entrada ? substr($asistencia->hora_entrada, 0, 5) : 'Sin entrada' }}
                                @if($asistencia->hora_programada_salida_refrigerio)
                                    · Refrigerio {{ $asistencia->hora_salida_refrigerio ? substr($asistencia->hora_salida_refrigerio, 0, 5) : '—' }}
                                    / {{ $asistencia->hora_retorno_refrigerio ? substr($asistencia->hora_retorno_refrigerio, 0, 5) : '—' }}
                                @endif
                                · Salida {{ $asistencia->hora_salida ? substr($asistencia->hora_salida, 0, 5) : 'pendiente' }}
                            </small>
                            <small>
                                {{ match($asistencia->origen_marcacion) {
                                    'BIOMETRICO' => 'Biométrico',
                                    'MANUAL' => 'Manual',
                                    'WEB_CAMPO' => 'Web campo',
                                    'MIXTO' => 'Mixto',
                                    default => 'Sin marcación asociada',
                                } }}
                            </small>
                        </span>
                        <b>{{ intdiv((int) $asistencia->minutos_trabajados, 60) }} h {{ (int) $asistencia->minutos_trabajados % 60 }} min</b>
                    </div>
                @empty
                    <div class="dashboard-empty dashboard-empty--card">
                        <span class="dashboard-empty__icon"><x-ui-icon name="calendar" /></span>
                        <strong>Aún no hay jornadas procesadas</strong>
                        <span>Cuando se procese tu asistencia, verás aquí los últimos días.</span>
                    </div>
                @endforelse
            </div>
        </details>

        <details class="dashboard-panel worker-dashboard-disclosure">
            <summary class="dashboard-panel__header">
                <div>
                    <span class="dashboard-panel__eyebrow">ACCESOS RÁPIDOS</span>
                    <h3>Gestiones personales</h3>
                    <p class="dashboard-panel__description">Entra directamente a la tarea que necesitas realizar.</p>
                </div>
            </summary>

            <nav class="dashboard-quick-grid" aria-label="Gestiones personales">
                <a href="{{ route('solicitudes.index', ['estado' => 'PENDIENTE']) }}" class="dashboard-quick">
                    <span class="dashboard-quick__icon"><x-ui-icon name="calendar" /></span>
                    <span class="dashboard-quick__copy">
                        <strong>Mis solicitudes</strong>
                        <small>{{ $solicitudesPendientes }} {{ $solicitudesPendientes === 1 ? 'pendiente' : 'pendientes' }}</small>
                    </span>
                    <x-ui-icon name="chevron-right" class="dashboard-quick__arrow" />
                </a>
                <a href="{{ route('reportesAvance.index') }}" class="dashboard-quick {{ !$reporteHoy || $reporteHoy->estado === 'OBSERVADO' ? 'dashboard-quick--attention' : '' }}">
                    <span class="dashboard-quick__icon"><x-ui-icon name="report" /></span>
                    <span class="dashboard-quick__copy">
                        <strong>Avance de hoy</strong>
                        <small>
                            @if(!$reporteHoy)
                                Falta registrar
                            @elseif($reporteHoy->estado === 'OBSERVADO')
                                Requiere corrección
                            @elseif($reporteHoy->estado === 'REVISADO')
                                Revisado
                            @else
                                Pendiente de revisión
                            @endif
                        </small>
                    </span>
                    <x-ui-icon name="chevron-right" class="dashboard-quick__arrow" />
                </a>
                @if($puedeSolicitarVacaciones)
                    <a href="{{ route('vacaciones.index') }}" class="dashboard-quick">
                        <span class="dashboard-quick__icon"><x-ui-icon name="calendar" /></span>
                        <span class="dashboard-quick__copy">
                            <strong>Vacaciones</strong>
                            <small>
                                @if($vacacionActiva)
                                    {{ ucfirst(strtolower($vacacionActiva->estado)) }} · {{ $vacacionActiva->fecha_inicio->format('d/m/Y') }}
                                @else
                                    Sin solicitud activa
                                @endif
                            </small>
                        </span>
                        <x-ui-icon name="chevron-right" class="dashboard-quick__arrow" />
                    </a>
                @endif
                <a href="{{ route('reportesAvance.index', ['pestana' => 'pendientes']) }}" class="dashboard-quick">
                    <span class="dashboard-quick__icon"><x-ui-icon name="alert" /></span>
                    <span class="dashboard-quick__copy">
                        <strong>Pendientes del área</strong>
                        <small>{{ $pendientesArea }} {{ $pendientesArea === 1 ? 'actividad abierta o por validar' : 'actividades abiertas o por validar' }}</small>
                    </span>
                    <x-ui-icon name="chevron-right" class="dashboard-quick__arrow" />
                </a>
                @if($esEncargadoArea)
                    <a href="{{ route('reportesAvance.index', ['vista' => 'equipo', 'pestana' => 'avances']) }}" class="dashboard-quick">
                        <span class="dashboard-quick__icon"><x-ui-icon name="users" /></span>
                        <span class="dashboard-quick__copy">
                            <strong>Avances de mi área</strong>
                            <small>Seguimiento del equipo</small>
                        </span>
                        <x-ui-icon name="chevron-right" class="dashboard-quick__arrow" />
                    </a>
                @endif
            </nav>
        </details>
    </div>
@endif

@push('scripts')
<script>
document.querySelectorAll('[data-open-incidencias]').forEach(link => {
    link.addEventListener('click', event => {
        const panel = document.getElementById('dashboardIncidencias');
        if (!panel) return;
        event.preventDefault();
        panel.open = true;
        panel.scrollIntoView({block: 'start'});
        panel.querySelector('summary')?.focus();
    });
});
</script>
@endpush
