<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mis empresas</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme-itrafic.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/empresas.css') }}">
</head>
<body>
<main class="empresa-selector empresa-gestion">
    <span class="empresa-eyebrow">CONTROL DE ASISTENCIAS</span>
    <h1>Mis empresas</h1>
    <x-alert />
    <p>Selecciona la empresa con la que deseas trabajar.</p>
    @forelse($empresas as $empresa)
        <form method="POST" action="{{ route('empresas.seleccionar') }}" class="empresa-opcion">
            @csrf
            <input type="hidden" name="empresa_id" value="{{ $empresa->id }}">
            <button type="submit" class="button button--secondary">{{ $empresa->nombre }} @if((int) session('empresa_id') === $empresa->id) (actual) @endif <span aria-hidden="true">→</span></button>
        </form>
    @empty
        <p>No tienes empresas activas asignadas. Solicita acceso a un administrador.</p>
    @endforelse
    @if(auth()->user()->membresias()->where('estado', true)->whereIn('rol_id', \App\Models\Rol::where('nombre', 'ADMINISTRADOR')->select('id'))->exists())
        <hr><h2>Crear empresa</h2>
        <form method="POST" action="{{ route('empresas.store') }}">
            @csrf
            <p><label>Nombre <input name="nombre" value="{{ old('nombre') }}" maxlength="150" required></label></p>
            <p><label>RUC <input name="ruc" value="{{ old('ruc') }}" inputmode="numeric" pattern="[0-9]{11}" maxlength="11"></label></p>
            <button type="submit" class="button button--primary">Crear empresa</button>
        </form>
    @endif
    <form method="POST" action="{{ route('logout') }}" class="empresa-salir">@csrf<button type="submit" class="button button--secondary">Cerrar sesión</button></form>
</main>
</body>
</html>
