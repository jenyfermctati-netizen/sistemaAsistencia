@extends('layouts.app')

@section('title', $empresa->razon_social.' | CONNEXT')
@section('page-title', 'Ficha de empresa')
@section('page-subtitle', 'Información, administradores y configuración')

@push('styles')<link rel="stylesheet" href="{{ asset('css/pages/superadmin.css') }}">@endpush
@push('scripts')<script src="{{ asset('js/pages/company-color-picker.js') }}" defer></script>@endpush

@section('content')
    <div class="sa-page">
        <a href="{{ route('superadmin.empresas.index') }}#informacion" class="sa-back-link">← <span>Empresas</span></a>
        <nav class="sa-company-nav" aria-label="Navegación de ficha de empresa">
            <a class="sa-company-nav__link" href="#inicio" aria-label="Ir al resumen de la empresa"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V10Z"/></svg><span>Inicio</span></a>
            <a class="sa-company-nav__link" href="#accesos" aria-label="Ir a accesos de administradores"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM20 8v6M23 11h-6"/></svg><span>Accesos</span></a>
            <a class="sa-company-nav__link" href="#configuracion" aria-label="Ir a configuración de la empresa"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z"/><path d="m19.4 15 .1.1 1.4 1.1-1.4 2.4-1.7-.7a8 8 0 0 1-1.5.9l-.3 1.8h-2.8l-.3-1.8a8 8 0 0 1-1.5-.9l-1.7.7-1.4-2.4 1.4-1.1a7 7 0 0 1 0-1.8l-1.4-1.1 1.4-2.4 1.7.7a8 8 0 0 1 1.5-.9l.3-1.8h2.8l.3 1.8a8 8 0 0 1 1.5.9l1.7-.7 1.4 2.4-1.4 1.1a7 7 0 0 1-.1 1.7Z"/></svg><span>Configuración</span></a>
        </nav>
        <section class="sa-hero sa-company-hero" id="inicio">
            <div><span class="sa-eyebrow">EMPRESA</span><h2>{{ $empresa->nombre_comercial ?: $empresa->razon_social }}</h2><p>RUC {{ $empresa->ruc }}</p></div>
            <div class="sa-company-hero__actions"><span class="sa-status sa-status--{{ $empresa->estado }}">{{ ucfirst(strtolower($empresa->estado)) }}</span></div>
        </section>

        <section class="sa-grid sa-company-metrics" aria-label="Resumen de empresa">
            <article class="sa-card"><span>Administradores</span><strong>{{ number_format($administradores->count()) }}</strong></article>
            <article class="sa-card"><span>Administradores activos</span><strong>{{ number_format($administradores->where('estado', true)->count()) }}</strong></article>
        </section>

        <section class="sa-panel sa-info-panel">
            <div class="sa-section-heading"><div><span class="sa-eyebrow">INFORMACIÓN</span><h2>Datos de la empresa</h2></div><a class="sa-icon-button" href="#configuracion" aria-label="Editar información de la empresa" title="Editar información"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m14 5 5 5M4 20l4.5-1 10.8-10.8a2.1 2.1 0 0 0-3-3L5.5 16 4 20Z"/></svg></a></div>
            <dl class="sa-company-info">
                <div><dt>Razón social</dt><dd>{{ $empresa->razon_social }}</dd></div>
                <div><dt>Nombre comercial</dt><dd>{{ $empresa->nombre_comercial ?: '—' }}</dd></div>
                <div><dt>RUC</dt><dd>{{ $empresa->ruc }}</dd></div>
                <div><dt>Correo</dt><dd>{{ $empresa->email ?: '—' }}</dd></div>
                <div><dt>Teléfono</dt><dd>{{ $empresa->telefono ?: '—' }}</dd></div>
                <div><dt>Dirección</dt><dd>{{ $empresa->direccion ?: '—' }}</dd></div>
            </dl>
        </section>

        <section class="sa-panel sa-management-panel" id="accesos">
            <div class="sa-section-heading"><div><span class="sa-eyebrow">ACCESOS</span><h2>Administradores</h2><p>Ingresos, último acceso y estado de contraseña.</p></div><span class="sa-count-pill">{{ $administradores->count() }} cuentas</span></div>
            <div class="sa-table-wrap"><table class="sa-table sa-admin-table"><thead><tr><th>Administrador</th><th>Estado</th><th>Ingresos</th><th>Último acceso</th><th>Contraseña</th><th><span class="sr-only">Acciones</span></th></tr></thead><tbody>
                @forelse($administradores as $admin)
                    <tr><td data-label="Administrador"><strong>{{ $admin->name }}</strong><small>{{ $admin->email }}</small></td><td data-label="Estado"><span class="sa-status {{ $admin->estado ? '' : 'sa-status--INACTIVA' }}">{{ $admin->estado ? 'Activo' : 'Inactivo' }}</span></td><td data-label="Ingresos">{{ number_format($admin->ingresos_count) }}</td><td data-label="Último acceso">{{ $admin->ultimo_acceso?->format('d/m/Y H:i') ?: 'Nunca' }}</td><td data-label="Contraseña">{{ $admin->password_changed_at?->format('d/m/Y') ?: 'Sin cambio registrado' }}</td><td data-label="Acciones"><div class="sa-actions"><form method="POST" action="{{ route('superadmin.administradores.invitacion', [$empresa, $admin]) }}">@csrf<button class="sa-button sa-button--quiet" type="submit">Invitar</button></form><form method="POST" action="{{ route('superadmin.administradores.estado', [$empresa, $admin]) }}">@csrf @method('PATCH')<button class="sa-button {{ $admin->estado ? 'sa-button--danger' : 'sa-button--secondary' }}" type="submit">{{ $admin->estado ? 'Desactivar' : 'Activar' }}</button></form></div></td></tr>
                @empty
                    <tr><td colspan="6" class="sa-empty-state"><strong>Aún no hay administradores</strong><span>Añade una cuenta para continuar con la configuración.</span></td></tr>
                @endforelse
            </tbody></table></div>
            <details class="sa-inline-create" @if($errors->has('name') || $errors->has('email')) open @endif>
                <summary>+ <span>Añadir administrador</span></summary>
                <form class="sa-inline-form" method="POST" action="{{ route('superadmin.administradores.store', $empresa) }}">@csrf
                    <label><span>Nombre</span><input name="name" value="{{ old('name') }}" required></label>
                    <label><span>Correo de acceso</span><input name="email" type="email" value="{{ old('email') }}" required></label>
                    <button class="sa-button" type="submit">Crear y enviar invitación</button>
                </form>
            </details>
        </section>

        <section class="sa-panel" id="configuracion">
            <div class="sa-section-heading"><div><span class="sa-eyebrow">CONFIGURACIÓN</span><h2>Preferencias de empresa</h2></div></div>
            <form class="sa-form sa-company-form" method="POST" action="{{ route('superadmin.empresas.update', $empresa) }}">
                @csrf @method('PUT')
                <label><span>Razón social</span><input name="razon_social" value="{{ old('razon_social', $empresa->razon_social) }}" required></label>
                <label><span>Nombre comercial</span><input name="nombre_comercial" value="{{ old('nombre_comercial', $empresa->nombre_comercial) }}"></label>
                <label><span>RUC</span><input name="ruc" value="{{ old('ruc', $empresa->ruc) }}" required maxlength="11"></label>
                <label><span>Correo</span><input type="email" name="email" value="{{ old('email', $empresa->email) }}"></label>
                <label><span>Teléfono</span><input name="telefono" value="{{ old('telefono', $empresa->telefono) }}"></label>
                <label><span>Zona horaria</span><input name="zona_horaria" value="{{ old('zona_horaria', $empresa->zona_horaria) }}" required></label>
                <label class="sa-field--full"><span>Dirección</span><textarea name="direccion" rows="2">{{ old('direccion', $empresa->direccion) }}</textarea></label>
                <div class="sa-field--full">@include('superadmin.empresas.partials.color-picker', ['value' => old('color_turquesa', $empresa->color_turquesa ?: '#00A9C7')])</div>
                <div class="sa-company-form__footer"><span>El color se aplica a los acentos turquesa de la interfaz.</span><button class="sa-button" type="submit">Guardar configuración</button></div>
            </form>
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
