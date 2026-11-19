<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte general de asistencia</title>
    <style>
        @page { margin: 24px 28px; }
        body { font: 8px DejaVu Sans, sans-serif; color: #20252b; }
        .month { page-break-after: always; }
        .month:last-child { page-break-after: auto; }
        h1 { margin: 0 0 4px; font-size: 15px; color: #17324d; }
        p { margin: 0 0 10px; color: #5e6c75; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #555; padding: 4px 5px; text-align: center; vertical-align: middle; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        th { background: #343434; color: #fff; font-size: 7px; }
        td:first-child, th:first-child { width: 22%; text-align: left; }
        td:nth-child(2), th:nth-child(2) { width: 11%; }
        td:last-child, th:last-child { width: 9%; }
        .total td { background: #202b3c; color: #fff; font-weight: bold; }
        .empty td { padding: 14px; color: #758197; }
    </style>
</head>
<body>
    @foreach($meses as $mes)
        <section class="month">
            <h1>Reporte general de asistencia — {{ ucfirst($mes['titulo']) }}</h1>
            <p>Período: {{ $mes['desde']->format('d/m/Y') }} al {{ $mes['hasta']->format('d/m/Y') }}</p>
            <table>
                <thead><tr><th>Colaborador</th><th>Fecha</th><th>Ingreso Mañana</th><th>Salida Mañana</th><th>Ingreso Tarde</th><th>Salida Tarde</th><th>Total Hrs</th><th>Pasajes (S/)</th></tr></thead>
                <tbody>
                    @forelse($mes['registros_generales'] as $registro)
                        <tr>
                            <td>{{ $registro['nombre'] }}</td>
                            <td>{{ $registro['fecha']?->format('d/m/Y') ?? '—' }}</td>
                            <td>{{ $registro['entrada_manana'] }}</td>
                            <td>{{ $registro['salida_manana'] }}</td>
                            <td>{{ $registro['entrada_tarde'] }}</td>
                            <td>{{ $registro['salida_tarde'] }}</td>
                            <td>{{ rtrim(rtrim(number_format($registro['horas'], 2, '.', ''), '0'), '.') }}</td>
                            <td>{{ $registro['pasaje'] === null ? '—' : rtrim(rtrim(number_format($registro['pasaje'], 2, '.', ''), '0'), '.') }}</td>
                        </tr>
                    @empty
                        <tr class="empty"><td colspan="8">No hay asistencias registradas en este período.</td></tr>
                    @endforelse
                    <tr class="total"><td colspan="6">TOTAL GENERAL DEL MES</td><td>{{ rtrim(rtrim(number_format($mes['total_general_minutos'] / 60, 2, '.', ''), '0'), '.') }}</td><td>{{ $mes['hay_locadores'] ? rtrim(rtrim(number_format($mes['total_general_pasaje'], 2, '.', ''), '0'), '.') : '—' }}</td></tr>
                </tbody>
            </table>
        </section>
    @endforeach
</body>
</html>
