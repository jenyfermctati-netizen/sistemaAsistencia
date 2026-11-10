@extends('layouts.app')
@section('title', 'Auditoría | CONNEXT')
@section('page-title', 'Auditoría de plataforma')
@section('page-subtitle', 'Operaciones administrativas globales y empresariales')
@push('styles')<link rel="stylesheet" href="{{ asset('css/pages/superadmin.css') }}">@endpush
@section('content')
<div class="sa-page"><section class="sa-panel"><form class="sa-form" method="GET"><label>Acción<input name="accion" value="{{ request('accion') }}"></label><label>Empresa<select name="empresa_id"><option value="">Todas, incluida plataforma</option>@foreach($empresas as $empresa)<option value="{{ $empresa->id }}" @selected((string)request('empresa_id')===(string)$empresa->id)>{{ $empresa->razon_social }}</option>@endforeach</select></label><div class="full"><button class="sa-button">Filtrar</button></div></form></section><section class="sa-panel"><div class="sa-table-wrap"><table class="sa-table"><thead><tr><th>Fecha</th><th>Acción</th><th>Empresa</th><th>Usuario</th><th>Recurso</th><th>IP</th></tr></thead><tbody>@forelse($registros as $registro)<tr><td>{{ $registro->created_at?->format('d/m/Y H:i:s') }}</td><td><strong>{{ $registro->accion }}</strong></td><td>{{ $registro->empresa?->razon_social ?: 'Plataforma' }}</td><td>{{ $registro->usuario?->email ?: 'Sistema' }}</td><td>{{ $registro->tabla }} #{{ $registro->registro_id }}</td><td>{{ $registro->ip ?: '-' }}</td></tr>@empty<tr><td colspan="6">No hay eventos para los filtros indicados.</td></tr>@endforelse</tbody></table></div>{{ $registros->links() }}</section></div>
@endsection

