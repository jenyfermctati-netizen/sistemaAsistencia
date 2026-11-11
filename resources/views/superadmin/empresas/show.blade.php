@extends('layouts.app')
@section('title', $empresa->razon_social.' | CONNEXT')
@section('page-title', $empresa->nombre_comercial ?: $empresa->razon_social)
@section('page-subtitle', 'RUC '.$empresa->ruc.' · '.$empresa->estado)
@push('styles')<link rel="stylesheet" href="{{ asset('css/pages/superadmin.css') }}">@endpush
@section('content')
<div class="sa-page"><a class="sa-back" href="{{ route('superadmin.empresas.index') }}"><x-ui-icon name="arrow-left" /> Todas las empresas</a>@php
    $seccion = request('seccion', 'informacion');
    if (!in_array($seccion, ['informacion', 'administradores', 'configuracion'], true)) $seccion = 'informacion';
    if ($errors->hasAny(['name', 'email'])) $seccion = 'administradores';
    if ($errors->hasAny(['razon_social', 'ruc', 'zona_horaria', 'nombre_comercial', 'telefono', 'direccion', 'color_principal', 'serial', 'nombre', 'modelo']) || session('secreto_dispositivo')) $seccion = 'configuracion';
@endphp

    <section class="sa-hero"><div><h2>{{ $empresa->razon_social }}</h2><p>{{ $empresa->direccion ?: 'Dirección no registrada' }}</p></div><span class="sa-status sa-status--{{ $empresa->estado }}">{{ $empresa->estado }}</span></section>
    <section class="sa-grid"><article class="sa-card"><span>Trabajadores</span><strong>{{ $empresa->trabajadores_count }}</strong></article><article class="sa-card"><span>Usuarios</span><strong>{{ $empresa->users_count }}</strong></article><article class="sa-card"><span>Dispositivos</span><strong>{{ $empresa->dispositivos_biometricos_count }}</strong></article></section>
    @if($seccion === 'informacion')
<section class="sa-panel"><h3>Información de la empresa</h3><dl class="sa-company-info">
<div><dt>Razón social</dt><dd>{{ $empresa->razon_social }}</dd></div>
<div><dt>Nombre comercial</dt><dd>{{ $empresa->nombre_comercial ?: 'No registrado' }}</dd></div>
<div><dt>RUC</dt><dd>{{ $empresa->ruc }}</dd></div>
<div><dt>Correo</dt><dd>{{ $empresa->email ?: 'No registrado' }}</dd></div>
<div><dt>Teléfono</dt><dd>{{ $empresa->telefono ?: 'No registrado' }}</dd></div>
<div><dt>Dirección</dt><dd>{{ $empresa->direccion ?: 'No registrada' }}</dd></div>
<div><dt>Administradores activos</dt><dd>{{ $administradores->where('estado', true)->count() }} de {{ $administradores->count() }}</dd></div>
<div><dt>Zona horaria</dt><dd>{{ $empresa->zona_horaria }}</dd></div>
</dl><div class="sa-actions"><a class="sa-button" href="{{ route('superadmin.empresas.show', ['empresa' => $empresa, 'seccion' => 'administradores']) }}"><x-ui-icon name="users" /> Gestionar administradores</a><a class="sa-button sa-button--secondary" href="{{ route('superadmin.auditoria.index', ['empresa_id' => $empresa->id]) }}"><x-ui-icon name="report" /> Ver auditoría</a></div></section>
@endif
@if($seccion === 'configuracion')<details open class="sa-section" id="datos"><summary><x-ui-icon name="edit" /> Datos y acceso <span>Editar información o suspender la empresa</span></summary><section class="sa-layout"><div class="sa-panel"><h3>Datos empresariales</h3><form class="sa-form" method="POST" action="{{ route('superadmin.empresas.update',$empresa) }}">@csrf @method('PUT')<label class="full">Color de la empresa<input type="color" name="color_principal" value="{{ old('color_principal', isset($empresa) ? ($empresa->color_principal ?: '#00a9c7') : '#00a9c7') }}"><small>Personaliza el turquesa y sus variantes. Los fondos blancos se conservan. Lo verán los usuarios de esta empresa al recargar.</small></label><label class="full">Razón social<input name="razon_social" value="{{ old('razon_social',$empresa->razon_social) }}" required></label><label class="full">Nombre comercial<input name="nombre_comercial" value="{{ old('nombre_comercial',$empresa->nombre_comercial) }}"></label><label>RUC<input name="ruc" value="{{ old('ruc',$empresa->ruc) }}" required maxlength="11"></label><label>Zona horaria<input name="zona_horaria" value="{{ old('zona_horaria',$empresa->zona_horaria) }}" required></label><label>Correo<input type="email" name="email" value="{{ old('email',$empresa->email) }}"></label><label>Teléfono<input name="telefono" value="{{ old('telefono',$empresa->telefono) }}"></label><label class="full">Dirección<textarea name="direccion">{{ old('direccion',$empresa->direccion) }}</textarea></label><div class="full"><button class="sa-button">Guardar cambios</button></div></form></div>
        <aside class="sa-panel"><h3>Estado de acceso</h3><p class="sa-muted">Suspender o desactivar bloquea el acceso operativo sin eliminar registros.</p><div class="sa-actions">@foreach(['ACTIVA','SUSPENDIDA','INACTIVA'] as $estado)<form method="POST" action="{{ route('superadmin.empresas.estado',$empresa) }}">@csrf @method('PATCH')<input type="hidden" name="estado" value="{{ $estado }}"><button class="sa-button {{ $estado==='ACTIVA'?'sa-button--secondary':($estado==='SUSPENDIDA'?'sa-button--warning':'sa-button--danger') }}" @disabled($empresa->estado===$estado)>{{ $estado }}</button></form>@endforeach</div></aside></section>
    </details>@endif @if($seccion === 'administradores')<section id="administradores"><div class="sa-section-heading"><h3>Administradores</h3><p>Son las personas que gestionan esta empresa. La invitación les permite definir su contraseña.</p></div><section class="sa-layout"><div class="sa-panel"><h3>Personas con acceso</h3><div class="sa-table-wrap"><table class="sa-table"><thead><tr><th>Nombre</th><th>Correo</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>@forelse($administradores as $admin)<tr><td>{{ $admin->name }}</td><td>{{ $admin->email }}</td><td>{{ $admin->estado?'ACTIVO':'INACTIVO' }}</td><td><div class="sa-actions"><form method="POST" action="{{ route('superadmin.administradores.invitacion',[$empresa,$admin]) }}">@csrf<button class="sa-button sa-button--secondary" title="Reenviar invitación por correo" aria-label="Reenviar invitación a {{ $admin->name }}"><x-ui-icon name="mail" /><span class="sr-only">Reenviar invitación</span></button></form><form method="POST" action="{{ route('superadmin.administradores.estado',[$empresa,$admin]) }}">@csrf @method('PATCH')<button class="sa-button {{ $admin->estado?'sa-button--danger':'sa-button--secondary' }}">{{ $admin->estado?'Desactivar':'Activar' }}</button></form></div></td></tr>@empty<tr><td colspan="4">Sin administradores.</td></tr>@endforelse</tbody></table></div></div>
        <aside class="sa-panel"><h3>Agregar administrador</h3><form class="sa-form" method="POST" action="{{ route('superadmin.administradores.store',$empresa) }}">@csrf<label class="full">Nombre<input name="name" value="{{ old('name') }}" required></label><label class="full">Correo<input name="email" type="email" value="{{ old('email') }}" required></label><div class="full"><button class="sa-button"><x-ui-icon name="plus" /> Agregar e invitar</button></div></form></aside></section>
    </section>@endif @if($seccion === 'configuracion')<details class="sa-section" id="dispositivos" @if(session('secreto_dispositivo') || $errors->hasAny(['serial', 'nombre', 'modelo'])) open @endif><summary><x-ui-icon name="fingerprint" /> Dispositivos <span>Opcional · configurar equipos biométricos</span></summary><section class="sa-layout"><div class="sa-panel"><h3>Dispositivos asignados</h3><div class="sa-table-wrap"><table class="sa-table"><thead><tr><th>Nombre</th><th>Serial</th><th>Estado</th><th>Última conexión</th></tr></thead><tbody>@forelse($dispositivos as $dispositivo)<tr><td>{{ $dispositivo->nombre }}</td><td>{{ $dispositivo->serial }}</td><td>{{ $dispositivo->estado?'ACTIVO':'INACTIVO' }}</td><td>{{ $dispositivo->ultima_conexion?->format('d/m/Y H:i') ?: 'Nunca' }}</td></tr>@empty<tr><td colspan="4">Sin dispositivos.</td></tr>@endforelse</tbody></table></div></div>
        <aside class="sa-panel"><h3>Registrar dispositivo</h3>@if(session('secreto_dispositivo'))<div class="sa-secret"><strong>Secreto (se muestra una sola vez)</strong><br>{{ session('secreto_dispositivo') }}</div>@endif<form class="sa-form" method="POST" action="{{ route('superadmin.empresas.dispositivos.store',$empresa) }}">@csrf<label class="full">Nombre<input name="nombre" required></label><label class="full">Serial físico<input name="serial" required></label><label class="full">Modelo<input name="modelo"></label><label class="full"><span><input type="checkbox" name="generar_secreto" value="1"> Exigir secreto adicional</span></label><div class="full"><button class="sa-button">Registrar dispositivo</button></div></form></aside></section>
</details>@endif </div>
@endsection

@push('scripts')
<script>
(() => {
    const reveal = () => {
        const section = document.getElementById(location.hash.slice(1));
        if (section instanceof HTMLDetailsElement) section.open = true;
    };
    window.addEventListener('hashchange', reveal);
    document.querySelectorAll('.sa-tabs a').forEach(link => link.addEventListener('click', () => {
        const section = document.getElementById(link.hash.slice(1));
        if (section instanceof HTMLDetailsElement) section.open = true;
    }));
    reveal();
})();
</script>
@endpush