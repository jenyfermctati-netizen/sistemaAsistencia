@extends('layouts.app')
@section('title', 'Nueva empresa')
@section('page-title', 'Nueva empresa')
@section('page-subtitle', 'Registra la empresa y designa a su responsable')
@push('styles')<link rel="stylesheet" href="{{ asset('css/pages/superadmin.css') }}">@endpush
@section('content')
<div class="sa-page">
<a class="sa-back" href="{{ route('superadmin.empresas.index') }}"><x-ui-icon name="arrow-left" /> Volver a empresas</a>
<section class="sa-panel sa-create">
<form method="POST" action="{{ route('superadmin.empresas.store') }}" class="sa-form">@csrf
<div class="full"><h3>1. Datos de la empresa</h3><p class="sa-muted">Los campos marcados con * son obligatorios.</p></div>
<label class="full">Color de la empresa<input type="color" name="color_principal" value="{{ old('color_principal', isset($empresa) ? ($empresa->color_principal ?: '#00a9c7') : '#00a9c7') }}"><small>Personaliza el turquesa y sus variantes. Los fondos blancos se conservan. Lo verán los usuarios de esta empresa al recargar.</small></label><label class="full">Razón social *<input name="razon_social" value="{{ old('razon_social') }}" required maxlength="200" placeholder="Ej. Servicios del Norte S.A.C."></label>
<label>RUC *<input name="ruc" value="{{ old('ruc') }}" required inputmode="numeric" pattern="[0-9]{11}" minlength="11" maxlength="11"><small>11 dígitos, sin espacios.</small></label>
<label>Zona horaria *<select name="zona_horaria" required><option value="America/Lima">Perú (America/Lima)</option>@if(old('zona_horaria') && old('zona_horaria') !== 'America/Lima')<option selected value="{{ old('zona_horaria') }}">{{ old('zona_horaria') }}</option>@endif</select></label>
<details class="full sa-section" @if(old('nombre_comercial') || old('email') || old('telefono') || old('direccion')) open @endif>
<summary>Datos adicionales <span>Opcional · contacto y dirección</span></summary>
<div class="sa-form sa-guide">
<label class="full">Nombre comercial<input name="nombre_comercial" value="{{ old('nombre_comercial') }}" maxlength="200"></label>
<label>Correo empresarial<input name="email" type="email" value="{{ old('email') }}"></label>
<label>Teléfono<input name="telefono" value="{{ old('telefono') }}" maxlength="20"></label>
<label class="full">Dirección<textarea name="direccion" maxlength="500">{{ old('direccion') }}</textarea></label>
</div></details>
<div class="full sa-form-heading"><h3>2. Designar administrador</h3><p>Esta persona gestionará el personal y la asistencia. Le enviaremos un correo para que cree su contraseña.</p></div>
<label>Nombre del administrador *<input name="admin_nombre" value="{{ old('admin_nombre') }}" required maxlength="150" autocomplete="name"></label>
<label>Correo del administrador *<input name="admin_email" type="email" value="{{ old('admin_email') }}" required maxlength="255" autocomplete="email"><small>Usa un correo que no tenga otra cuenta en el sistema.</small></label>
<div class="full sa-actions"><button class="sa-button"><x-ui-icon name="mail" /> Crear empresa y enviar invitación</button><a class="sa-back" href="{{ route('superadmin.empresas.index') }}">Cancelar</a></div>
</form></section>
</div>
@endsection