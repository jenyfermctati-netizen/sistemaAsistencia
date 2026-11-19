@extends('layouts.app')

@section('title', $empresa->razon_social.' | CONNEXT')
@section('page-title', 'Ficha de empresa')
@section('page-subtitle', 'Información, administradores y configuración')

@push('styles')<link rel="stylesheet" href="{{ asset('css/pages/superadmin.css') }}">@endpush
@push('scripts')<script src="{{ asset('js/pages/company-color-picker.js') }}" defer></script><script src="{{ asset('js/pages/superadmin-admin-dialog.js') }}" defer></script>@endpush

@section('content')
    <div class="sa-page">
        <a href="{{ route('superadmin.empresas.index') }}#informacion" class="sa-back-link">← <span>Empresas</span></a>
        <nav class="sa-company-nav" aria-label="Navegación de ficha de empresa">
            <a class="sa-company-nav__link" href="#inicio" aria-label="Ir al resumen de la empresa"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V10Z"/></svg><span>Inicio</span></a>
            <a class="sa-company-nav__link" href="#accesos" aria-label="Ir a accesos de administradores"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM20 8v6M23 11h-6"/></svg><span>Accesos</span></a>
            <a class="sa-company-nav__link" href="#configuracion" data-open-dialog="company-config-dialog" aria-label="Abrir configuración de la empresa"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z"/><path d="m19.4 15 .1.1 1.4 1.1-1.4 2.4-1.7-.7a8 8 0 0 1-1.5.9l-.3 1.8h-2.8l-.3-1.8a8 8 0 0 1-1.5-.9l-1.7.7-1.4-2.4 1.4-1.1a7 7 0 0 1 0-1.8l-1.4-1.1 1.4-2.4 1.7.7a8 8 0 0 1 1.5-.9l.3-1.8h2.8l.3 1.8a8 8 0 0 1 1.5.9l1.7-.7 1.4 2.4-1.4 1.1a7 7 0 0 1-.1 1.7Z"/></svg><span>Configuración</span></a>
        </nav>
        <section class="sa-hero sa-company-hero" id="inicio">
            <div><span class="sa-eyebrow">EMPRESA</span><h2>{{ $empresa->nombre_comercial ?: $empresa->razon_social }}</h2><p>RUC {{ $empresa->ruc }}</p></div>
            <div class="sa-company-hero__actions"><span class="sa-status sa-status--{{ $empresa->estado }}">{{ ucfirst(strtolower($empresa->estado)) }}</span></div>
        </section>

        <section class="sa-grid sa-company-metrics" aria-label="Resumen de empresa">
            <article class="sa-card"><span>Administradores</span><strong>{{ number_format($administradores->count()) }}</strong></article>
            <article class="sa-card"><span>Administradores activos</span><strong>{{ number_format($administradores->where('estado', true)->count()) }}</strong></article>
        </section>

        <section class="sa-panel sa-info-panel" id="configuracion">
            <div class="sa-section-heading"><div><span class="sa-eyebrow">INFORMACIÓN</span><h2>Datos de la empresa</h2></div><button class="sa-icon-button" type="button" data-open-dialog="company-config-dialog" aria-label="Editar información de la empresa" title="Editar información"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m14 5 5 5M4 20l4.5-1 10.8-10.8a2.1 2.1 0 0 0-3-3L5.5 16 4 20Z"/></svg></button></div>
            <dl class="sa-company-info">
                <div><dt>Razón social</dt><dd>{{ $empresa->razon_social }}</dd></div>
                <div><dt>Nombre comercial</dt><dd>{{ $empresa->nombre_comercial ?: '—' }}</dd></div>
                <div><dt>RUC</dt><dd>{{ $empresa->ruc }}</dd></div>
                <div><dt>Correo</dt><dd>{{ $empresa->email ?: '—' }}</dd></div>
                <div><dt>Teléfono</dt><dd>{{ $empresa->telefono ?: '—' }}</dd></div>
                <div><dt>Dirección</dt><dd>{{ $empresa->direccion ?: '—' }}</dd></div>
            </dl>
            <dialog id="company-config-dialog" class="sa-admin-dialog sa-company-edit-dialog" aria-labelledby="company-config-title" @if(old('form_context') === 'company')data-open-on-load="true"@endif>
                <div class="sa-admin-dialog__heading"><div><span class="sa-eyebrow">CONFIGURACIÓN</span><h2 id="company-config-title">Editar datos de la empresa</h2><p>Actualiza la información general y las preferencias.</p></div><button type="button" class="sa-icon-button" data-close-dialog aria-label="Cerrar ventana">×</button></div>
                <form class="sa-form sa-company-form" method="POST" action="{{ route('superadmin.empresas.update', $empresa) }}">
                    @csrf @method('PUT')<input type="hidden" name="form_context" value="company">
                    <label><span>Razón social</span><input name="razon_social" value="{{ old('razon_social', $empresa->razon_social) }}" required>@error('razon_social')<small class="sa-form-error">{{ $message }}</small>@enderror</label>
                    <label><span>Nombre comercial</span><input name="nombre_comercial" value="{{ old('nombre_comercial', $empresa->nombre_comercial) }}">@error('nombre_comercial')<small class="sa-form-error">{{ $message }}</small>@enderror</label>
                    <label><span>RUC</span><input name="ruc" value="{{ old('ruc', $empresa->ruc) }}" required maxlength="11">@error('ruc')<small class="sa-form-error">{{ $message }}</small>@enderror</label>
                    <label><span>Correo</span><input type="email" name="email" value="{{ old('email', $empresa->email) }}">@error('email')<small class="sa-form-error">{{ $message }}</small>@enderror</label>
                    <label><span>Celular <small>9 dígitos, empieza con 9</small></span><input type="tel" name="telefono" value="{{ old('telefono', $empresa->telefono) }}" inputmode="numeric" pattern="9[0-9]{8}" minlength="9" maxlength="9" title="Ingresa un celular peruano de 9 dígitos que empiece con 9">@error('telefono')<small class="sa-form-error">{{ $message }}</small>@enderror</label>
                    <label><span>Zona horaria</span><input name="zona_horaria" value="{{ old('zona_horaria', $empresa->zona_horaria) }}" required>@error('zona_horaria')<small class="sa-form-error">{{ $message }}</small>@enderror</label>
                    <label class="sa-field--full"><span>Dirección</span><textarea name="direccion" rows="2">{{ old('direccion', $empresa->direccion) }}</textarea>@error('direccion')<small class="sa-form-error">{{ $message }}</small>@enderror</label>
                    <div class="sa-field--full">@include('superadmin.empresas.partials.color-picker', ['value' => old('color_turquesa', $empresa->color_turquesa ?: '#00A9C7')])</div>
                    @error('color_turquesa')<small class="sa-form-error">{{ $message }}</small>@enderror
                    <div class="sa-company-form__footer"><span>El color se aplica a los acentos turquesa de la interfaz.</span><div class="sa-admin-dialog__footer"><button type="button" class="sa-button sa-button--quiet" data-close-dialog>Cancelar</button><button class="sa-button" type="submit">Guardar cambios</button></div></div>
                </form>
            </dialog>
        </section>

        <section class="sa-panel sa-management-panel" id="accesos">
            <div class="sa-section-heading"><div><span class="sa-eyebrow">ACCESOS</span><h2>Administradores</h2><p>Ingresos, último acceso y estado de contraseña.</p></div><span class="sa-count-pill">{{ $administradores->count() }} cuentas</span></div>
            <div class="sa-table-wrap"><table class="sa-table sa-admin-table"><thead><tr><th>Administrador</th><th>Estado</th><th>Ingresos</th><th>Último acceso</th><th>Contraseña</th><th><span class="sr-only">Acciones</span></th></tr></thead><tbody>
                @forelse($administradores as $admin)
                    @php($mantenerEdicion = (string) old('administrador_id') === (string) $admin->id)
                    <tr><td data-label="Administrador"><strong>{{ $admin->name }}</strong><small>{{ $admin->email }}</small></td><td data-label="Estado"><span class="sa-status {{ $admin->estado ? '' : 'sa-status--INACTIVA' }}">{{ $admin->estado ? 'Activo' : 'Inactivo' }}</span></td><td data-label="Ingresos">{{ number_format($admin->ingresos_count) }}</td><td data-label="Último acceso">{{ $admin->ultimo_acceso?->format('d/m/Y H:i') ?: 'Nunca' }}</td><td data-label="Contraseña">{{ $admin->password_changed_at?->format('d/m/Y') ?: 'Sin cambio registrado' }}</td><td data-label="Acciones"><div class="sa-actions"><button class="sa-icon-button" type="button" data-open-dialog="admin-edit-{{ $admin->id }}" aria-label="Editar datos de {{ $admin->name }}" title="Editar datos"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m14 5 5 5M4 20l4.5-1 10.8-10.8a2.1 2.1 0 0 0-3-3L5.5 16 4 20Z"/></svg></button>@if($administradores->count() === 1 && $usuariosTransferencia->isNotEmpty())<button class="sa-icon-button" type="button" data-open-dialog="admin-transfer-dialog" aria-label="Transferir administración" title="Transferir administración"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h15l-3-3M20 17H5l3 3"/></svg></button>@endif<form method="POST" action="{{ route('superadmin.administradores.invitacion', [$empresa, $admin]) }}">@csrf<button class="sa-button sa-button--quiet" type="submit">Invitar</button></form><form method="POST" action="{{ route('superadmin.administradores.estado', [$empresa, $admin]) }}">@csrf @method('PATCH')<button class="sa-button {{ $admin->estado ? 'sa-button--danger' : 'sa-button--secondary' }}" type="submit" @disabled($admin->estado && $administradores->count() === 1)>{{ $admin->estado ? 'Desactivar' : 'Activar' }}</button></form></div><dialog id="admin-edit-{{ $admin->id }}" class="sa-admin-dialog" aria-labelledby="admin-edit-title-{{ $admin->id }}" @if($errors->any() && $mantenerEdicion)data-open-on-load="true"@endif><div class="sa-admin-dialog__heading"><div><span class="sa-eyebrow">ACCESOS</span><h2 id="admin-edit-title-{{ $admin->id }}">Editar administrador</h2><p>{{ $admin->email }}</p></div><button type="button" class="sa-icon-button" data-close-dialog aria-label="Cerrar ventana">×</button></div><form class="sa-form sa-admin-dialog__form" method="POST" action="{{ route('superadmin.administradores.update', [$empresa, $admin]) }}">@csrf @method('PATCH')<input type="hidden" name="administrador_id" value="{{ $admin->id }}"><label class="sa-field--full"><span>Nombre</span><input name="name" value="{{ $mantenerEdicion ? old('name', $admin->name) : $admin->name }}" required maxlength="150"></label><label class="sa-field--full"><span>Correo</span><input type="email" name="email" value="{{ $mantenerEdicion ? old('email', $admin->email) : $admin->email }}" required maxlength="255"></label>@error('name')<span class="sa-form-error">{{ $message }}</span>@enderror @error('email')<span class="sa-form-error">{{ $message }}</span>@enderror<div class="sa-admin-dialog__footer"><button type="button" class="sa-button sa-button--quiet" data-close-dialog>Cancelar</button><button class="sa-button" type="submit">Guardar cambios</button></div></form></dialog></td></tr>
                @empty
                    <tr><td colspan="6" class="sa-empty-state"><strong>Aún no hay administradores</strong><span>Añade una cuenta para continuar con la configuración.</span></td></tr>
                @endforelse
            </tbody></table></div>
            @if($administradores->count() === 1 && $usuariosTransferencia->isNotEmpty())
                <dialog id="admin-transfer-dialog" class="sa-admin-dialog sa-transfer-dialog" aria-labelledby="admin-transfer-title" @if($errors->has('usuario_id') || $errors->has('password'))data-open-on-load="true"@endif>
                    <div class="sa-admin-dialog__heading"><div><span class="sa-eyebrow">ACCESOS</span><h2 id="admin-transfer-title">Transferir administración</h2><p>El trabajador elegido será el único administrador. La cuenta actual pasará a trabajador o quedará inactiva si no tiene ficha laboral.</p></div><button type="button" class="sa-icon-button" data-close-dialog aria-label="Cerrar ventana">×</button></div>
                    <form class="sa-form sa-admin-dialog__form" method="POST" action="{{ route('superadmin.administradores.transferir', $empresa) }}">@csrf
                        <label class="sa-field--full"><span>Buscar trabajador</span><input type="search" data-user-search placeholder="Nombre, correo, DNI o cargo"></label>
                        <label class="sa-field--full"><span>Trabajador disponible</span><select name="usuario_id" data-user-select required><option value="">Seleccionar trabajador</option>@foreach($usuariosTransferencia as $usuario)<option value="{{ $usuario->id }}" data-search="{{ mb_strtolower($usuario->name.' '.$usuario->email.' '.($usuario->trabajador?->dni ?? '').' '.($usuario->trabajador?->cargo ?? '')) }}" @selected(old('usuario_id') == $usuario->id)>{{ $usuario->name }} · {{ $usuario->email }}</option>@endforeach</select>@error('usuario_id')<small class="sa-form-error">{{ $message }}</small>@enderror</label>
                        <label class="sa-field--full"><span>Tu contraseña actual</span><input type="password" name="password" autocomplete="current-password" required>@error('password')<small class="sa-form-error">{{ $message }}</small>@enderror</label>
                        <span class="sa-form-error" data-user-search-empty hidden>No hay trabajadores que coincidan con la búsqueda.</span>
                        <div class="sa-admin-dialog__footer"><button type="button" class="sa-button sa-button--quiet" data-close-dialog>Cancelar</button><button class="sa-button" type="submit">Confirmar transferencia</button></div>
                    </form>
                </dialog>
            @endif
            @if($administradores->isEmpty())
            <details class="sa-inline-create" @if($errors->has('name') || $errors->has('email')) open @endif>
                <summary>+ <span>Añadir administrador</span></summary>
                <form class="sa-inline-form" method="POST" action="{{ route('superadmin.administradores.store', $empresa) }}">@csrf
                    <label><span>Nombre</span><input name="name" value="{{ old('name') }}" required></label>
                    <label><span>Correo de acceso</span><input name="email" type="email" value="{{ old('email') }}" required></label>
                    <button class="sa-button" type="submit">Crear y enviar invitación</button>
                </form>
            </details>
            @endif
        </section>

        <section class="sa-panel sa-access-panel" id="estado-acceso">
            <div><span class="sa-eyebrow">ACCESO A LA PLATAFORMA</span><p>Estado actual: <strong>{{ ucfirst(strtolower($empresa->estado)) }}</strong></p></div>
            <div class="sa-actions">
                @foreach(['ACTIVA' => 'Activar', 'SUSPENDIDA' => 'Suspender', 'INACTIVA' => 'Desactivar'] as $estado => $etiqueta)
                    <form method="POST" action="{{ route('superadmin.empresas.estado', $empresa) }}">@csrf @method('PATCH')<input type="hidden" name="estado" value="{{ $estado }}"><button class="sa-button {{ $estado === 'ACTIVA' ? 'sa-button--secondary' : ($estado === 'SUSPENDIDA' ? 'sa-button--warning' : 'sa-button--danger') }}" type="submit" @disabled($empresa->estado === $estado)>{{ $etiqueta }}</button></form>
                @endforeach
            </div>
        </section>

    </div>
@endsection
