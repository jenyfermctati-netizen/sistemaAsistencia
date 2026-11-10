@extends('layouts.app')
@section('title', 'Administradores | CONNEXT')
@section('page-title', 'Administradores empresariales')
@section('page-subtitle', 'Cuentas administrativas separadas por empresa')
@push('styles')<link rel="stylesheet" href="{{ asset('css/pages/superadmin.css') }}">@endpush
@section('content')
<div class="sa-page"><section class="sa-panel"><form class="sa-form" method="GET"><label>Buscar<input name="buscar" value="{{ request('buscar') }}" placeholder="Nombre o correo"></label><label>Empresa<select name="empresa_id"><option value="">Todas</option>@foreach($empresas as $empresa)<option value="{{ $empresa->id }}" @selected((string)request('empresa_id')===(string)$empresa->id)>{{ $empresa->razon_social }}</option>@endforeach</select></label><div class="full sa-actions"><button class="sa-button">Filtrar</button><a class="sa-button sa-button--secondary" href="{{ route('superadmin.administradores.index') }}">Limpiar</a></div></form></section>
<section class="sa-panel"><div class="sa-table-wrap"><table class="sa-table"><thead><tr><th>Administrador</th><th>Empresa</th><th>Estado</th><th>Último acceso</th><th></th></tr></thead><tbody>@forelse($administradores as $admin)<tr><td><strong>{{ $admin->name }}</strong><br><small>{{ $admin->email }}</small></td><td>{{ $admin->empresa?->razon_social }}</td><td>{{ $admin->estado?'ACTIVO':'INACTIVO' }}</td><td>{{ $admin->ultimo_acceso?->format('d/m/Y H:i') ?: 'Nunca' }}</td><td><a class="sa-button sa-button--secondary" href="{{ route('superadmin.empresas.show',$admin->empresa_id) }}">Ver empresa</a></td></tr>@empty<tr><td colspan="5">No se encontraron administradores.</td></tr>@endforelse</tbody></table></div>{{ $administradores->links() }}</section></div>
@endsection

