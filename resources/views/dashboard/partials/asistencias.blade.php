@php
    $etiquetasEstado = [
        'PRESENTE' => 'Presente',
        'TARDANZA' => 'Tardanza',
        'FALTA' => 'Falta',
        'JUSTIFICADO' => 'Justificado',
        'CAMPO' => 'Campo',
        'VACACIONES' => 'Vacaciones',
        'PERMISO' => 'Permiso',
        'FERIADO' => 'Feriado',
        'NO_LABORABLE' => 'No laborable',
        'SIN_REGISTRO' => 'Sin registro',
    ];
@endphp

<div class="dashboard-table-wrap">
    <table class="dashboard-table">
        <thead>
            <tr>
                <th>Trabajador</th>
                @if($mostrarArea)<th>Área</th>@endif
                <th>Entrada mañana</th>
                <th>Salida refrigerio</th>
                <th>Retorno / entrada tarde</th>
                <th>Salida final</th>
                <th>Horas</th>
                <th>Origen</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($asistencias as $asistencia)
                <tr>
                    <td data-label="Trabajador">
                        <span class="dashboard-person">
                            <span class="dashboard-person__avatar">{{ strtoupper(substr($asistencia->trabajador?->nombres ?? 'T', 0, 1)) }}</span>
                            <span>
                                <strong>{{ $asistencia->trabajador?->nombres }} {{ $asistencia->trabajador?->apellidos }}</strong>
                                <small>{{ $asistencia->trabajador?->cargo ?? 'Sin cargo registrado' }}</small>
                            </span>
                        </span>
                    </td>
                    @if($mostrarArea)<td data-label="Área">{{ $asistencia->trabajador?->area?->nombre ?? 'Sin área' }}</td>@endif
                    <td data-label="Entrada mañana">{{ $asistencia->hora_entrada ? substr($asistencia->hora_entrada, 0, 5) : '—' }}</td>
                    <td data-label="Salida refrigerio">{{ $asistencia->hora_salida_refrigerio ? substr($asistencia->hora_salida_refrigerio, 0, 5) : '—' }}</td>
                    <td data-label="Retorno / entrada tarde">{{ $asistencia->hora_retorno_refrigerio ? substr($asistencia->hora_retorno_refrigerio, 0, 5) : '—' }}</td>
                    <td data-label="Salida final">{{ $asistencia->hora_salida ? substr($asistencia->hora_salida, 0, 5) : '—' }}</td>
                    <td data-label="Horas">{{ intdiv((int) $asistencia->minutos_trabajados, 60) }}h {{ (int) $asistencia->minutos_trabajados % 60 }}m</td>
                    <td data-label="Origen">
                        {{ match($asistencia->origen_marcacion) {
                            'BIOMETRICO' => 'Biométrico',
                            'MANUAL' => 'Manual',
                            'MIXTO' => 'Mixto',
                            default => 'Sin marcación',
                        } }}
                    </td>
                    <td data-label="Estado">
                        <span class="dashboard-status dashboard-status--{{ strtolower(str_replace('_', '-', $asistencia->estado)) }}">
                            {{ $etiquetasEstado[$asistencia->estado] ?? ucfirst(strtolower($asistencia->estado)) }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr class="dashboard-table__empty-row"><td colspan="{{ $mostrarArea ? 9 : 8 }}"><div class="dashboard-empty">Todavía no hay asistencias registradas para hoy.</div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
