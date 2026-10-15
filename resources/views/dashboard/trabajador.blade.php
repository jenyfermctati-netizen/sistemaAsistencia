@if(!$trabajador)
    <section class="dashboard-panel dashboard-unlinked">
        <span class="dashboard-unlinked__icon">!</span>
        <div>
            <h3>Cuenta sin trabajador vinculado</h3>
            <p>Solicita al administrador que relacione tu usuario con un trabajador para mostrar tu asistencia, área y reportes personales.</p>
        </div>
    </section>
@else
    @php
        $estadoHoy = $asistenciaHoy?->estado ?? 'SIN_PROCESAR';
        $etiquetaHoy = [
            'PRESENTE' => 'Presente', 'TARDANZA' => 'Tardanza', 'FALTA' => 'Falta',
            'JUSTIFICADO' => 'Justificado', 'CAMPO' => 'Trabajo en campo',
            'VACACIONES' => 'Vacaciones', 'PERMISO' => 'Permiso', 'FERIADO' => 'Feriado',
            'NO_LABORABLE' => 'No laborable', 'SIN_REGISTRO' => 'Sin registro',
            'SIN_PROCESAR' => 'Aún sin procesar',
        ][$estadoHoy] ?? ucfirst(strtolower($estadoHoy));
    @endphp

    <section class="dashboard-today dashboard-today--{{ strtolower(str_replace('_', '-', $estadoHoy)) }}">
        <div class="dashboard-today__status">
            <span class="dashboard-panel__eyebrow">TU JORNADA DE HOY</span>
            <h3>{{ $etiquetaHoy }}</h3>
            <p>
                @if($asistenciaHoy)
                    Entrada {{ $asistenciaHoy->hora_entrada ? substr($asistenciaHoy->hora_entrada, 0, 5) : 'sin registrar' }}
                    · Salida {{ $asistenciaHoy->hora_salida ? substr($asistenciaHoy->hora_salida, 0, 5) : 'pendiente' }}
                @else
                    La asistencia de hoy todavía no ha sido procesada.
                @endif
            </p>
        </div>
        <div class="dashboard-today__identity">
            <span>{{ strtoupper(substr($trabajador->nombres, 0, 1).substr($trabajador->apellidos, 0, 1)) }}</span>
            <div>
                <strong>{{ $trabajador->nombres }} {{ $trabajador->apellidos }}</strong>
                <small>{{ $trabajador->cargo ?? 'Sin cargo' }} · {{ $trabajador->area?->nombre ?? 'Sin área' }}</small>
            </div>
        </div>
    </section>

    <section class="dashboard-metrics" aria-label="Tu resumen mensual">
        <article class="dashboard-metric dashboard-metric--petrol">
            <span class="dashboard-metric__label">Días registrados</span>
            <strong class="dashboard-metric__value">{{ $metrics['presentes'] }}</strong>
            <span class="dashboard-metric__caption">presente, tardanza o campo</span>
        </article>
        <article class="dashboard-metric dashboard-metric--blue">
            <span class="dashboard-metric__label">Horas del mes</span>
            <strong class="dashboard-metric__value dashboard-metric__value--compact">{{ $metrics['horas'] }}</strong>
            <span class="dashboard-metric__caption">tiempo procesado</span>
        </article>
        <article class="dashboard-metric dashboard-metric--sand">
            <span class="dashboard-metric__label">Tardanzas</span>
            <strong class="dashboard-metric__value">{{ $metrics['tardanzas'] }}</strong>
            <span class="dashboard-metric__caption">durante este mes</span>
        </article>
        <article class="dashboard-metric dashboard-metric--graphite">
            <span class="dashboard-metric__label">Incidencias</span>
            <strong class="dashboard-metric__value">{{ $metrics['incidencias'] }}</strong>
            <span class="dashboard-metric__caption">faltas o sin registro</span>
        </article>
    </section>

    <div class="dashboard-columns dashboard-columns--personal">
        <section class="dashboard-panel dashboard-panel--wide">
            <div class="dashboard-panel__header">
                <div>
                    <span class="dashboard-panel__eyebrow">HISTORIAL PERSONAL</span>
                    <h3>Últimos días procesados</h3>
                </div>
                <span class="dashboard-text-link">Mes actual</span>
            </div>

            <div class="dashboard-days">
                @forelse($asistenciasRecientes as $asistencia)
                    <div class="dashboard-day">
                        <span class="dashboard-day__date"><strong>{{ $asistencia->fecha->format('d') }}</strong><small>{{ $asistencia->fecha->translatedFormat('M') }}</small></span>
                        <span><strong>{{ ucfirst(strtolower(str_replace('_', ' ', $asistencia->estado))) }}</strong><small>{{ $asistencia->hora_entrada ? substr($asistencia->hora_entrada, 0, 5) : '—' }} a {{ $asistencia->hora_salida ? substr($asistencia->hora_salida, 0, 5) : '—' }}</small></span>
                        <b>{{ intdiv((int) $asistencia->minutos_trabajados, 60) }}h {{ (int) $asistencia->minutos_trabajados % 60 }}m</b>
                    </div>
                @empty
                    <div class="dashboard-empty">Todavía no tienes asistencias procesadas.</div>
                @endforelse
            </div>
        </section>

        <aside class="dashboard-panel">
            <div class="dashboard-panel__header">
                <div>
                    <span class="dashboard-panel__eyebrow">ACCESOS RÁPIDOS</span>
                    <h3>Tu trabajo</h3>
                </div>
            </div>

            <div class="dashboard-quick-grid">
                <a href="{{ route('solicitudes.index') }}" class="dashboard-quick">
                    <span>SO</span><strong>Solicitudes</strong><small>{{ $solicitudesPendientes }} pendientes</small>
                </a>
                <a href="{{ route('reportesAvance.index') }}" class="dashboard-quick">
                    <span>RA</span><strong>Avance diario</strong><small>{{ $reporteHoy ? 'Registrado hoy' : 'Falta registrar hoy' }}</small>
                </a>
                @if($puedeSolicitarVacaciones)
                    <a href="{{ route('vacaciones.index') }}" class="dashboard-quick">
                        <span>VA</span><strong>Vacaciones</strong><small>{{ $vacacionActiva ? ucfirst(strtolower($vacacionActiva->estado)) : 'Sin solicitud activa' }}</small>
                    </a>
                @endif
                <a href="{{ route('reportesAvance.index', ['pestana' => 'pendientes']) }}" class="dashboard-quick">
                    <span>PE</span><strong>Pendientes del área</strong><small>{{ $pendientesArea }} abiertos</small>
                </a>
            </div>
        </aside>
    </div>
@endif
