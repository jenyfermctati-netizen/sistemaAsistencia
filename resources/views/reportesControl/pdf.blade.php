<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte consolidado de control</title>
    <style>
        @page { margin: 28px 32px 34px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #1f2937; font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        h1, h2, h3, p { margin: 0; }
        .header { border-bottom: 2px solid #0f766e; padding-bottom: 12px; }
        .header h1 { color: #0f4f4a; font-size: 19px; }
        .header p { margin-top: 5px; color: #64748b; font-size: 9px; }
        .scope { margin-top: 6px; color: #0f766e; font-weight: bold; }
        .metrics { width: 100%; margin: 14px 0; border-collapse: separate; border-spacing: 5px; }
        .metrics td { width: 12.5%; padding: 8px; border: 1px solid #d6e6e3; background: #f4faf8; vertical-align: top; }
        .metrics span { display: block; color: #64748b; font-size: 7px; text-transform: uppercase; }
        .metrics strong { display: block; margin-top: 4px; color: #134e4a; font-size: 13px; }
        .section { margin-top: 14px; }
        .section h2 { margin-bottom: 7px; color: #134e4a; font-size: 12px; }
        .area { page-break-before: always; }
        .area:first-of-type { page-break-before: auto; }
        .area-header { margin-bottom: 8px; padding: 8px 10px; background: #dff3ef; }
        .area-header h2 { margin: 0; }
        .area-header p { margin-top: 3px; color: #475569; }
        table.data { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.data th { padding: 6px 5px; background: #134e4a; color: #fff; font-size: 7px; text-align: left; }
        table.data td { padding: 5px; border-bottom: 1px solid #dbe3e2; vertical-align: top; overflow-wrap: break-word; }
        table.data tr:nth-child(even) td { background: #f8fbfa; }
        .worker { margin-top: 12px; page-break-inside: avoid; }
        .worker-title { padding: 6px 8px; border-left: 3px solid #0f766e; background: #f3f7f6; font-size: 10px; }
        .worker-title small { color: #64748b; font-weight: normal; }
        .subhead { margin: 8px 0 4px; color: #334155; font-size: 9px; }
        .compact-list { margin: 0; padding-left: 16px; }
        .compact-list li { margin-bottom: 4px; }
        .muted { color: #64748b; }
        .empty { padding: 12px; color: #64748b; text-align: center; }
        .footer { position: fixed; bottom: -18px; width: 100%; color: #94a3b8; font-size: 7px; text-align: center; }
        .page-number:after { content: counter(page); }
    </style>
</head>
<body>
@php
    $horas = fn(int $minutos) => intdiv($minutos, 60).' h '.($minutos % 60).' min';
    $estado = fn(?string $valor) => ucfirst(strtolower(str_replace('_', ' ', $valor ?? 'Sin revisión')));
@endphp

<div class="footer">
    Sistema de Control de Asistencia - Generado el {{ now()->format('d/m/Y H:i') }} - Página <span class="page-number"></span>
</div>

<header class="header">
    <h1>Reporte consolidado de control</h1>
    <p>Periodo: {{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }}</p>
    <div class="scope">Alcance: {{ $alcanceLabel }}</div>
</header>

<table class="metrics">
    <tr>
        <td><span>Trabajadores</span><strong>{{ $metricas['trabajadores'] }}</strong></td>
        <td><span>Días registrados</span><strong>{{ $metricas['dias_registrados'] }}</strong></td>
        <td><span>Horas</span><strong>{{ $horas($metricas['minutos_trabajados']) }}</strong></td>
        <td><span>Tardanzas</span><strong>{{ $metricas['tardanzas'] }}</strong></td>
        <td><span>Faltas</span><strong>{{ $metricas['faltas'] }}</strong></td>
        <td><span>Actividades</span><strong>{{ $metricas['actividades'] }}</strong></td>
        <td><span>Pendientes</span><strong>{{ $metricas['pendientes_abiertos'] }}</strong></td>
        <td><span>Finalizadas</span><strong>{{ $metricas['finalizadas'] }}</strong></td>
    </tr>
</table>

<section class="section">
    <h2>Resumen por trabajador</h2>
    <table class="data">
        <thead>
            <tr>
                @if($esVistaGeneral)<th style="width:12%">Área</th>@endif
                <th style="width:20%">Trabajador</th>
                <th>Días</th>
                <th>Completos</th>
                <th>Tardanzas</th>
                <th>Faltas</th>
                <th style="width:10%">Horas</th>
                <th>Reportes</th>
                <th>Actividades</th>
                <th>Pendientes</th>
                <th>Finalizadas</th>
            </tr>
        </thead>
        <tbody>
            @forelse($areasReporte as $areaData)
                @foreach($areaData['trabajadores'] as $trabajadorData)
                    @php($m = $trabajadorData['metricas'])
                    <tr>
                        @if($esVistaGeneral)<td>{{ $areaData['area']->nombre }}</td>@endif
                        <td>{{ $trabajadorData['trabajador']->nombres }} {{ $trabajadorData['trabajador']->apellidos }}</td>
                        <td>{{ $m['dias_registrados'] }}</td>
                        <td>{{ $m['dias_completos'] }}</td>
                        <td>{{ $m['tardanzas'] }}</td>
                        <td>{{ $m['faltas'] }}</td>
                        <td>{{ $horas($m['minutos_trabajados']) }}</td>
                        <td>{{ $m['reportes'] }}</td>
                        <td>{{ $m['actividades'] }}</td>
                        <td>{{ $m['pendientes_abiertos'] }}</td>
                        <td>{{ $m['finalizadas'] }}</td>
                    </tr>
                @endforeach
            @empty
                <tr><td colspan="{{ $esVistaGeneral ? 11 : 10 }}" class="empty">Sin información para el periodo.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>

@if($esVistaGeneral)
    @foreach($areasReporte as $areaData)
        <section class="area">
            <div class="area-header">
                <h2>{{ $areaData['area']->nombre }}</h2>
                <p>
                    {{ $areaData['metricas']['trabajadores'] }} trabajadores -
                    {{ $areaData['metricas']['actividades'] }} actividades -
                    {{ $areaData['metricas']['finalizadas'] }} tareas finalizadas
                </p>
            </div>

            @foreach($areaData['trabajadores'] as $trabajadorData)
                <div class="worker">
                    <div class="worker-title">
                        <strong>{{ $trabajadorData['trabajador']->nombres }} {{ $trabajadorData['trabajador']->apellidos }}</strong>
                        <small>- DNI {{ $trabajadorData['trabajador']->dni }} - {{ $trabajadorData['trabajador']->cargo ?? 'Sin cargo' }}</small>
                    </div>

                    <h3 class="subhead">Asistencia</h3>
                    <table class="data">
                        <thead><tr><th>Fecha</th><th>Entrada</th><th>Refrigerio</th><th>Retorno</th><th>Salida final</th><th>Horas</th><th>Tardanza</th><th>Estado</th></tr></thead>
                        <tbody>
                            @forelse($trabajadorData['asistencias'] as $asistencia)
                                <tr>
                                    <td>{{ $asistencia->fecha->format('d/m/Y') }}</td>
                                    <td>{{ $asistencia->hora_entrada ? substr($asistencia->hora_entrada, 0, 5) : '-' }}</td>
                                    <td>{{ $asistencia->hora_salida_refrigerio ? substr($asistencia->hora_salida_refrigerio, 0, 5) : '-' }}</td>
                                    <td>{{ $asistencia->hora_retorno_refrigerio ? substr($asistencia->hora_retorno_refrigerio, 0, 5) : '-' }}</td>
                                    <td>{{ $asistencia->hora_salida ? substr($asistencia->hora_salida, 0, 5) : '-' }}</td>
                                    <td>{{ $horas((int) $asistencia->minutos_trabajados) }}</td>
                                    <td>{{ (int) $asistencia->minutos_tardanza }} min</td>
                                    <td>{{ $estado($asistencia->estado) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="empty">Sin asistencias.</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                    <h3 class="subhead">Actividades</h3>
                    <ul class="compact-list">
                        @forelse($trabajadorData['actividades'] as $actividad)
                            <li>{{ $actividad->reporte->fecha_presentacion->format('d/m/Y') }} - {{ $actividad->descripcion }}</li>
                        @empty
                            <li class="muted">Sin actividades registradas.</li>
                        @endforelse
                    </ul>

                    <h3 class="subhead">Tareas relacionadas</h3>
                    <ul class="compact-list">
                        @forelse($trabajadorData['pendientes'] as $pendiente)
                            <li>
                                {{ $pendiente->created_at->format('d/m/Y') }} - {{ $pendiente->descripcion }}
                                ({{ $estado($pendiente->estado_revision ?? $pendiente->estado) }})
                            </li>
                        @empty
                            <li class="muted">Sin tareas relacionadas.</li>
                        @endforelse
                    </ul>
                </div>
            @endforeach
        </section>
    @endforeach
@else
    <section class="section">
        <h2>Resumen de supervisión</h2>
        <p class="muted">
            Esta versión presenta indicadores consolidados del equipo perteneciente a tu área.
        </p>
    </section>
@endif
</body>
</html>
