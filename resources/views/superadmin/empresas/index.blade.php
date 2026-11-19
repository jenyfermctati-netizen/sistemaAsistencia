@extends('layouts.app')

@section('title', 'Empresas | CONNEXT')
@section('page-title', 'Empresas')
@section('page-subtitle', 'Crea y administra las cuentas de las empresas')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/superadmin.css') }}">
@endpush
@push('scripts')
    <script src="{{ asset('js/pages/superadmin-ruc.js') }}" defer></script>
@endpush

@section('content')
    <div class="sa-page">
        <details class="sa-create-panel sa-create-panel--first" id="nueva-empresa" @if($errors->any() || request('section') === 'new') open @endif>
            <summary class="sa-create-summary">
                <span class="sa-create-icon" aria-hidden="true">+</span>
                <span><strong>Nueva empresa</strong><small>Registra la empresa y su administrador único.</small></span>
                <span class="sa-create-toggle">Agregar empresa</span>
            </summary>
            <div class="sa-create-body">
                <form method="POST" action="{{ route('superadmin.empresas.store') }}" class="sa-form sa-form--create" data-consulta-ruc-url="{{ route('superadmin.empresas.consultar-ruc') }}">
                    @csrf
                    <label><span>RUC</span><input name="ruc" value="{{ old('ruc') }}" required inputmode="numeric" pattern="[0-9]{11}" minlength="11" maxlength="11" autocomplete="off" aria-describedby="consulta-ruc-status"><small id="consulta-ruc-status" class="sa-ruc-status" role="status" aria-live="polite">Ingresa primero los 11 dígitos para consultar y completar los datos disponibles.</small></label>
                    <label><span>Razón social</span><input name="razon_social" value="{{ old('razon_social') }}" required maxlength="200"></label>
                    <label><span>Nombre comercial <small>Opcional</small></span><input name="nombre_comercial" value="{{ old('nombre_comercial') }}" maxlength="200"></label>
                    <label><span>Zona horaria</span><input name="zona_horaria" value="{{ old('zona_horaria', 'America/Lima') }}" required></label>
                    <label><span>Correo empresarial <small>Opcional</small></span><input name="email" type="email" value="{{ old('email') }}"></label>
                    <label><span>Celular <small>Opcional · 9 dígitos, empieza con 9</small></span><input type="tel" name="telefono" value="{{ old('telefono') }}" inputmode="numeric" pattern="9[0-9]{8}" minlength="9" maxlength="9" title="Ingresa un celular peruano de 9 dígitos que empiece con 9"></label>
                    <label class="sa-field--full"><span>Dirección <small>Opcional</small></span><textarea name="direccion" maxlength="500" rows="2">{{ old('direccion') }}</textarea></label>
                    <label><span>Nombre del administrador</span><input name="admin_nombre" value="{{ old('admin_nombre') }}" required maxlength="150"></label>
                    <label><span>Correo del administrador</span><input name="admin_email" type="email" value="{{ old('admin_email') }}" required maxlength="255"></label>
                    <div class="sa-form-footer sa-field--full"><span>Al agregar la empresa se enviará una invitación al administrador para crear su contraseña. Luego podrás reenviarla con “Invitar”.</span><button class="sa-button" type="submit">Agregar empresa</button></div>
                </form>
            </div>
        </details>

        <section class="sa-panel sa-list-panel" id="informacion">
            <div class="sa-section-heading">
                <div><span class="sa-eyebrow">DIRECTORIO</span><h2>Empresas registradas</h2><p>Busca una empresa y abre su ficha para gestionar accesos y configuración.</p></div>
                <span class="sa-count-pill">{{ $empresas->total() }} empresas</span>
            </div>
            <form class="sa-filter-form" method="GET">
                <label class="sa-field sa-field--search"><span>Buscar empresa</span><input name="buscar" value="{{ request('buscar') }}" placeholder="Razón social, nombre comercial o RUC"></label>
                <label class="sa-field"><span>Estado</span><select name="estado"><option value="">Todos los estados</option>@foreach(['ACTIVA', 'SUSPENDIDA', 'INACTIVA'] as $estado)<option value="{{ $estado }}" @selected(request('estado') === $estado)>{{ ucfirst(strtolower($estado)) }}</option>@endforeach</select></label>
                <div class="sa-filter-actions"><button class="sa-button" type="submit">Buscar</button>@if(request()->filled('buscar') || request()->filled('estado'))<a class="sa-button sa-button--quiet" href="{{ route('superadmin.empresas.index') }}">Limpiar filtros</a>@endif</div>
            </form>
            <div class="sa-table-wrap"><table class="sa-table"><thead><tr><th>Empresa</th><th>RUC</th><th>Administradores</th><th>Estado</th><th><span class="sr-only">Acciones</span></th></tr></thead><tbody>
                @forelse($empresas as $empresa)
                    <tr><td><strong class="sa-company-name"><i class="sa-company-color-dot" style="--company-color: {{ $empresa->color_turquesa ?: '#00A9C7' }}" aria-hidden="true"></i>{{ $empresa->nombre_comercial ?: $empresa->razon_social }}</strong><small>{{ $empresa->razon_social }}</small></td><td>{{ $empresa->ruc }}</td><td>{{ number_format($empresa->administradores_count) }}</td><td><span class="sa-status sa-status--{{ $empresa->estado }}">{{ ucfirst(strtolower($empresa->estado)) }}</span></td><td><a class="sa-icon-button" href="{{ route('superadmin.empresas.show', $empresa) }}" aria-label="Ver más: {{ $empresa->razon_social }}" title="Ver más"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2.5 12s3.3-6 9.5-6 9.5 6 9.5 6-3.3 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg></a></td></tr>
                @empty
                    <tr><td colspan="5" class="sa-empty-state"><strong>No hay empresas para mostrar</strong><span>Prueba otro término o limpia los filtros.</span></td></tr>
                @endforelse
            </tbody></table></div>
            <div class="sa-pagination">{{ $empresas->links() }}</div>
        </section>
    </div>
@endsection
