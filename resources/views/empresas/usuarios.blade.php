@extends('layouts.app')
@section('title', 'Accesos de la empresa')
@section('page-title', 'Accesos de la empresa')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/empresas.css') }}">
@endpush
@section('content')
<div class="empresa-gestion">
    <h2>{{ $empresa->nombre }}</h2>
    <p><a href="{{ route('empresas.index') }}">Cambiar o crear empresa</a></p>
    <form method="POST" action="{{ route('empresas.update') }}">
        @csrf
        @method('PUT')
        <p><label>Nombre de la empresa <input name="nombre" value="{{ old('nombre', $empresa->nombre) }}" maxlength="150" required></label></p>
        <p><label>RUC <input name="ruc" value="{{ old('ruc', $empresa->ruc) }}" inputmode="numeric" pattern="[0-9]{11}" maxlength="11"></label></p>
        <button type="submit" class="button button--primary">Guardar empresa</button>
    </form>
    <h3>Usuarios con acceso</h3>
    <div class="table-container"><table class="table">
        <thead><tr><th>Usuario</th><th>Correo</th><th>Rol</th><th>Acceso</th></tr></thead>
        <tbody>@foreach($usuarios as $usuario)
            <tr><td>{{ $usuario->name }}</td><td>{{ $usuario->email }}</td><td>{{ $roles->firstWhere('id', $usuario->pivot->rol_id)?->nombre }}</td><td>{{ $usuario->pivot->estado ? 'Activo' : 'Inactivo' }}</td></tr>
        @endforeach</tbody>
    </table></div>
    <h3>Asignar o actualizar acceso</h3>
    <p>Usa el correo de una cuenta existente para darle acceso a esta empresa. Para una cuenta nueva, completa también el nombre y la contraseña.</p>
    <form method="POST" action="{{ route('empresas.usuarios.store') }}">
        @csrf
        <p><label>Correo <input type="email" name="email" value="{{ old('email') }}" required></label></p>
        <p><label>Nombre (cuenta nueva) <input name="name" value="{{ old('name') }}" maxlength="150"></label></p>
        <p><label>Contraseña (cuenta nueva) <input type="password" name="password" minlength="8" autocomplete="new-password"></label></p>
        <p><label>Confirmar contraseña <input type="password" name="password_confirmation" autocomplete="new-password"></label></p>
        <p><label>Rol <select name="rol_id" required>@foreach($roles as $rol)<option value="{{ $rol->id }}" @selected((int) old('rol_id') === $rol->id)>{{ $rol->nombre }}</option>@endforeach</select></label></p>
        <p><label>Trabajador <select name="trabajador_id"><option value="">Sin trabajador vinculado</option>@foreach($trabajadores as $trabajador)<option value="{{ $trabajador->id }}" @selected((int) old('trabajador_id') === $trabajador->id)>{{ $trabajador->apellidos }} {{ $trabajador->nombres }} — {{ $trabajador->dni }}</option>@endforeach</select></label></p>
        <p><label>Acceso <select name="estado"><option value="1">Activo</option><option value="0" @selected(old('estado') === '0')>Inactivo</option></select></label></p>
        <button type="submit" class="button button--primary">Guardar acceso</button>
    </form>
    <h3>Registrar dispositivo biométrico</h3>
    @if(session('dispositivo_token'))<p>Token del dispositivo: <code>{{ session('dispositivo_token') }}</code>. Configúralo como parámetro <code>token</code> en las peticiones del dispositivo o como cabecera <code>X-Device-Token</code>.</p>@endif
    <form method="POST" action="{{ route('empresas.dispositivos.store') }}">
        @csrf
        <p><label>Nombre <input name="nombre" maxlength="100" required></label></p>
        <p><label>Serial <input name="serial" maxlength="100" required></label></p>
        <button type="submit" class="button button--primary">Registrar dispositivo</button>
    </form>
    @foreach($dispositivos as $dispositivo)
        <form method="POST" action="{{ route('empresas.dispositivos.token', $dispositivo) }}" class="empresa-dispositivo">
            @csrf
            <span><strong>{{ $dispositivo->nombre }}</strong> · {{ $dispositivo->serial }}</span>
            <button type="submit" class="button button--secondary">{{ $dispositivo->token_hash ? 'Renovar token' : 'Generar token' }}</button>
        </form>
    @endforeach
</div>
@endsection
