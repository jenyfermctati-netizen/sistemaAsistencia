@php
    $etiquetas = [
        'PRESENTE' => 'Puntual', 'TARDANZA' => 'Tarde', 'CAMPO' => 'En campo',
        'FALTA' => 'Falta', 'SIN_REGISTRO' => 'Sin registro',
        'PERMISO' => 'Permiso', 'VACACIONES' => 'Vacaciones',
        'JUSTIFICADO' => 'Justificado', 'FERIADO' => 'Feriado', 'NO_LABORABLE' => 'No laborable',
    ];
    $metodos = ['BIOMETRICO' => 'Biométrico', 'MANUAL' => 'Manual', 'MIXTO' => 'Mixto'];
@endphp
<div class="dashboard-table-wrap dashboard-table-wrap--clean">
    <table class="dashboard-table dashboard-table--clean">
        <thead><tr>
            <th>Nombre</th><th>Área</th><th>Hora entrada</th><th>Método marcaje</th><th>Estado</th><th>Acciones</th>
        </tr></thead>
        <tbody>
            @forelse($asistencias as $asistencia)
                @php
                    $nombre = trim(($asistencia->trabajador?->nombres ?? '').' '.($asistencia->trabajador?->apellidos ?? ''));
                    $estado = $etiquetas[$asistencia->estado] ?? ucfirst(strtolower(str_replace('_', ' ', $asistencia->estado)));
                    $metodo = $metodos[$asistencia->origen_marcacion] ?? 'Sin registro';
                @endphp
                <tr>
                    <td data-label="Nombre"><span class="dashboard-person"><span class="dashboard-person__avatar">{{ mb_strtoupper(mb_substr($nombre ?: 'T', 0, 1)) }}</span><strong>{{ $nombre ?: 'Sin nombre' }}</strong></span></td>
                    <td data-label="Área">{{ $asistencia->trabajador?->area?->nombre ?? 'Sin área' }}</td>
                    <td data-label="Hora entrada" class="dashboard-table__time">{{ $asistencia->hora_entrada ? substr($asistencia->hora_entrada, 0, 5) : '—' }}</td>
                    <td data-label="Método marcaje">{{ $metodo }}</td>
                    <td data-label="Estado"><span class="dashboard-status dashboard-status--{{ strtolower(str_replace('_', '-', $asistencia->estado)) }}">{{ $estado }}</span></td>
                    <td data-label="Acciones">
                        <button type="button" class="dashboard-eye-button" aria-label="Ver detalle de {{ $nombre }}" title="Ver detalle"
                            data-dashboard-detail
                            data-name="{{ $nombre }}"
                            data-area="{{ $asistencia->trabajador?->area?->nombre ?? 'Sin área' }}"
                            data-status="{{ $estado }}"
                            data-method="{{ $metodo }}"
                            data-date="{{ $asistencia->fecha?->format('d/m/Y') ?? '—' }}"
                            data-in="{{ $asistencia->hora_entrada ? substr($asistencia->hora_entrada, 0, 5) : '—' }}"
                            data-lunch-out="{{ $asistencia->hora_salida_refrigerio ? substr($asistencia->hora_salida_refrigerio, 0, 5) : '—' }}"
                            data-lunch-in="{{ $asistencia->hora_retorno_refrigerio ? substr($asistencia->hora_retorno_refrigerio, 0, 5) : '—' }}"
                            data-out="{{ $asistencia->hora_salida ? substr($asistencia->hora_salida, 0, 5) : '—' }}"
                            data-hours="{{ intdiv((int)$asistencia->minutos_trabajados, 60) }} h {{ (int)$asistencia->minutos_trabajados % 60 }} min">
                            <x-ui-icon name="eye" />
                        </button>
                    </td>
                </tr>
            @empty
                <tr class="dashboard-table__empty-row"><td colspan="6"><div class="dashboard-empty">Todavía no hay asistencias registradas.</div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="dashboard-detail-overlay" id="dashboardDetailOverlay" hidden>
    <section class="dashboard-detail-modal" role="dialog" aria-modal="true" aria-labelledby="dashboardDetailHeading">
        <div class="dashboard-detail-heading"><div><small>DETALLE DE ASISTENCIA</small><h3 id="dashboardDetailHeading">Detalle</h3></div><button type="button" id="dashboardDetailClose" aria-label="Cerrar detalle" class="dashboard-detail-close"><x-ui-icon name="close" /></button></div>
        <dl class="dashboard-detail-grid">
            <div><dt>Área</dt><dd data-detail-field="area">—</dd></div>
            <div><dt>Fecha</dt><dd data-detail-field="date">—</dd></div>
            <div><dt>Entrada</dt><dd data-detail-field="in">—</dd></div>
            <div><dt>Salida a almorzar</dt><dd data-detail-field="lunchOut">—</dd></div>
            <div><dt>Retorno</dt><dd data-detail-field="lunchIn">—</dd></div>
            <div><dt>Salida final</dt><dd data-detail-field="out">—</dd></div>
            <div><dt>Horas trabajadas</dt><dd data-detail-field="hours">—</dd></div>
            <div><dt>Método</dt><dd data-detail-field="method">—</dd></div>
            <div><dt>Estado</dt><dd data-detail-field="status">—</dd></div>
        </dl>
    </section>
</div>
