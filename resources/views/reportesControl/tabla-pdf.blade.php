<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $titulo }}</title>
    <style>
        body { font: 10px DejaVu Sans, sans-serif; color: #183844; }
        h1 { font-size: 19px; margin-bottom: 4px; }
        p { margin: 0 0 12px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #c8dade; padding: 6px; text-align: left; vertical-align: top; overflow-wrap: break-word; }
        th { background: #e2f0f2; }
        thead { display: table-header-group; }
    </style>
</head>
<body>
    <h1>{{ $titulo }}</h1>
    <p>Período {{ $periodo }}</p>
    <table>
        <thead>
            <tr>@foreach($columnas as $columna)<th>{{ $columna }}</th>@endforeach</tr>
        </thead>
        <tbody>
            @forelse($filas as $fila)
                <tr>@foreach($fila as $valor)<td>{{ $valor }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="{{ count($columnas) }}">No hay registros en esta consulta.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
