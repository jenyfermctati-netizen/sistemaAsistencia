<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte detallado de asistencia</title>
    <style>
        @page { margin: 30px 36px; }
        body { font: 9px DejaVu Sans, sans-serif; color: #202b3c; }
        .month { page-break-after: always; }
        .month:last-child { page-break-after: auto; }
        h1 { margin: 0 0 14px; font-size: 19px; color: #172b4d; }
        .meta { margin: 4px 0; font-size: 10px; }
        .meta strong { color: #1d2f4f; }
        table { width: 100%; margin-top: 20px; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #d2d8e1; padding: 7px 6px; text-align: center; }
        th { background: #edf1f6; color: #263b5a; font-size: 8px; }
        td:first-child, th:first-child { text-align: left; width: 16%; }
        .week-title td { padding: 6px; background: #e9edf3; text-align: left; font-weight: bold; color: #263b5a; }
        .subtotal td { background: #f5f6f8; font-weight: bold; }
        .total td { background: #172b4d; color: #fff; font-weight: bold; }
        .signatures { width: 76%; margin: 62px auto 0; border: 0; }
        .signatures td { border: 0; padding-top: 24px; width: 50%; }
        .sign-line { display: block; margin: 0 auto 7px; width: 76%; border-top: 1px solid #263b5a; }
        .empty { color: #758197; text-align: center; padding: 14px; }
    </style>
</head>
<body>
    @foreach($meses as $mes)
        @php($empleado = $mes['empleados']->first())
        <section class="month">
            <h1>Reporte Detallado de Asistencia Semanal</h1>
            <p class="meta"><strong>Colaborador:</strong> {{ $empleado['nombre'] ?? '—' }}</p>
            <p class="meta"><strong>DNI:</strong> {{ $empleado['dni'] ?? '—' }}</p>
            <p class="meta"><strong>Período:</strong> {{ ucfirst($mes['titulo']) }}</p>
            <p class="meta"><strong>Horario:</strong> {{ $empleado['horario'] ?? '—' }} · <strong>Tipo:</strong> {{ $empleado['tipo_control'] ?? '—' }}</p>
            <p class="meta"><strong>Vínculo:</strong> {{ $empleado['tipo_vinculo'] ?? '—' }}</p>
            <table>
                <thead><tr><th>Fecha</th><th>Entrada Mañana</th><th>Salida Mañana</th><th>Entrada Tarde</th><th>Salida Tarde</th><th>Total Horas</th><th>Pasaje (S/)</th></tr></thead>
                <tbody>
                    @forelse($empleado['detalle_semanas'] ?? [] as $semana)
                        <tr class="week-title"><td colspan="7">SEMANA {{ $semana['numero'] }}</td></tr>
                        @foreach($semana['dias'] as $dia)
                            <tr><td>{{ \Carbon\Carbon::parse($dia['fecha_iso'])->format('d/m/Y') }}</td><td>{{ $dia['entrada_manana'] }}</td><td>{{ $dia['salida_manana'] }}</td><td>{{ $dia['entrada_tarde'] }}</td><td>{{ $dia['salida_tarde'] }}</td><td>{{ $dia['horas_decimal'] }}h</td><td>{{ $empleado['tipo_vinculo'] === 'LOCADOR' ? number_format($dia['pasaje'], 2) : '—' }}</td></tr>
                        @endforeach
                        <tr class="subtotal"><td colspan="5">Subtotal semanal:</td><td>{{ number_format($semana['minutos'] / 60, 1) }}h</td><td>{{ $empleado['tipo_vinculo'] === 'LOCADOR' ? number_format($semana['pasaje'], 2) : '—' }}</td></tr>
                    @empty
                        <tr><td colspan="7" class="empty">No hay jornadas laborales registradas en este mes.</td></tr>
                    @endforelse
                    <tr class="total"><td colspan="5">TOTAL DEL MES:</td><td>{{ number_format($empleado['total_minutos'] / 60, 1) }}h</td><td>{{ $empleado['tipo_vinculo'] === 'LOCADOR' ? number_format($empleado['total_pasaje'], 2) : '—' }}</td></tr>
                </tbody>
            </table>
            <table class="signatures"><tbody><tr><td><span class="sign-line"></span>Firma Supervisor</td><td><span class="sign-line"></span>Firma Gerente de la Empresa</td></tr></tbody></table>
        </section>
    @endforeach
</body>
</html>
