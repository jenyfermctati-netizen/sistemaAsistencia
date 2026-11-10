@extends('layouts.app')
@section('title', 'Empresas | CONNEXT')
@section('page-title', 'Empresas')
@section('page-subtitle', 'Alta, búsqueda y control de acceso empresarial')
@push('styles')<link rel="stylesheet" href="{{ asset('css/pages/superadmin.css') }}">@endpush
@section('content')
<div class="sa-page">
    <section class="sa-layout">
        <div class="sa-panel"><h3>Empresas registradas</h3>
            <form class="sa-form" method="GET"><label>Buscar<input name="buscar" value="{{ request('buscar') }}" placeholder="Razón social, nombre o RUC"></label><label>Estado<select name="estado"><option value="">Todos</option>@foreach(['ACTIVA','SUSPENDIDA','INACTIVA'] as $estado)<option @selected(request('estado')===$estado)>{{ $estado }}</option>@endforeach</select></label><div class="full sa-actions"><button class="sa-button">Filtrar</button><a class="sa-button sa-button--secondary" href="{{ route('superadmin.empresas.index') }}">Limpiar</a></div></form>
            <div class="sa-table-wrap"><table class="sa-table"><thead><tr><th>Empresa</th><th>RUC</th><th>Estado</th><th>Personal</th><th>Dispositivos</th><th></th></tr></thead><tbody>
                @forelse($empresas as $empresa)<tr><td><strong>{{ $empresa->nombre_comercial ?: $empresa->razon_social }}</strong><br><small>{{ $empresa->email ?: 'Sin correo corporativo' }}</small></td><td>{{ $empresa->ruc }}</td><td><span class="sa-status sa-status--{{ $empresa->estado }}">{{ $empresa->estado }}</span></td><td>{{ $empresa->trabajadores_count }} trabajadores<br><small>{{ $empresa->users_count }} usuarios</small></td><td>{{ $empresa->dispositivos_biometricos_count }}</td><td><a class="sa-button sa-button--secondary" href="{{ route('superadmin.empresas.show',$empresa) }}">Administrar</a></td></tr>
                @empty<tr><td colspan="6" class="sa-muted">No se encontraron empresas.</td></tr>@endforelse
            </tbody></table></div>{{ $empresas->links() }}
        </div>
        <aside class="sa-panel"><h3>Nueva empresa y administrador principal</h3><form method="POST" action="{{ route('superadmin.empresas.store') }}" class="sa-form">@csrf
            <label class="full">Razón social<input name="razon_social" value="{{ old('razon_social') }}" required maxlength="200"></label><label class="full">Nombre comercial<input name="nombre_comercial" value="{{ old('nombre_comercial') }}" maxlength="200"></label><label>RUC<input name="ruc" value="{{ old('ruc') }}" required inputmode="numeric" minlength="11" maxlength="11"></label><label>Zona horaria<input name="zona_horaria" value="{{ old('zona_horaria','America/Lima') }}" required></label><label>Correo empresarial<input name="email" type="email" value="{{ old('email') }}"></label><label>Teléfono<input name="telefono" value="{{ old('telefono') }}" maxlength="20"></label><label class="full">Dirección<textarea name="direccion" maxlength="500">{{ old('direccion') }}</textarea></label><label class="full">Nombre del administrador<input name="admin_nombre" value="{{ old('admin_nombre') }}" required></label><label class="full">Correo del administrador<input name="admin_email" type="email" value="{{ old('admin_email') }}" required></label><div class="full"><button class="sa-button">Crear empresa y enviar invitación</button></div>
        </form></aside>
    </section>
</div>
@endsection

