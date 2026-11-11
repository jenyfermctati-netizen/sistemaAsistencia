@extends('layouts.app')
@section('title', 'Auditoría | CONNEXT')
@section('page-title', 'Auditoría de plataforma')
@section('page-subtitle', 'Estado actual de las empresas e historial de cambios')
@push('styles')<link rel="stylesheet" href="{{ asset('css/pages/superadmin.css') }}">@endpush
@section('content')
<div class="sa-page">
<section class="sa-grid" aria-label="Resumen del estado de empresas">
@foreach(['ACTIVA' => 'Activas', 'SUSPENDIDA' => 'Suspendidas', 'INACTIVA' => 'Inactivas'] as $estado => $etiqueta)
<article class="sa-card"><span>{{ $etiqueta }}</span><strong>{{ $resumenEstados[$estado] }}</strong></article>
@endforeach
</section>
<section class="sa-panel"><h3>Estado actual de las empresas</h3><p class="sa-muted">Este resumen respeta el filtro de empresa. El filtro de acción se aplica al historial.</p>
<div class="sa-table-wrap"><table class="sa-table"><thead><tr><th>Empresa</th><th>Estado</th><th>Última actualización de la ficha</th><th>Acciones</th></tr></thead><tbody>
@forelse($estadoEmpresas as $empresaEstado)
<tr><td>{{ $empresaEstado->nombre_comercial ?: $empresaEstado->razon_social }}<br><small>RUC {{ $empresaEstado->ruc }}</small></td><td><span class="sa-status sa-status--{{ $empresaEstado->estado }}">{{ $empresaEstado->estado }}</span></td><td>{{ $empresaEstado->updated_at?->format('d/m/Y H:i') ?: 'Sin registro' }}</td><td><a class="sa-button sa-button--secondary" href="{{ route('superadmin.empresas.show', $empresaEstado) }}"><x-ui-icon name="eye" /> Abrir</a></td></tr>
@empty<tr><td colspan="4">No hay empresas para este filtro.</td></tr>@endforelse
</tbody></table></div>{{ $estadoEmpresas->links() }}</section><section class="sa-panel"><form class="sa-form" method="GET"><label>Acción<input name="accion" value="{{ request('accion') }}"></label><label>Empresa<select name="empresa_id"><option value="">Todas, incluida plataforma</option>@foreach($empresas as $empresa)<option value="{{ $empresa->id }}" @selected((string)request('empresa_id')===(string)$empresa->id)>{{ $empresa->razon_social }}</option>@endforeach</select></label><div class="full"><button class="sa-button">Filtrar</button></div></form></section><section class="sa-panel"><h3>Historial de cambios</h3><div class="sa-table-wrap"><table class="sa-table"><thead><tr><th>Fecha</th><th>Acción</th><th>Empresa</th><th>Usuario</th><th>Recurso</th><th>IP</th></tr></thead><tbody>@forelse($registros as $registro)<tr><td>{{ $registro->created_at?->format('d/m/Y H:i:s') }}</td><td><strong>{{ $registro->accion }}</strong></td><td>{{ $registro->empresa?->razon_social ?: 'Plataforma' }}</td><td>{{ $registro->usuario?->email ?: 'Sistema' }}</td><td>{{ $registro->tabla }} #{{ $registro->registro_id }}</td><td>{{ $registro->ip ?: '-' }}</td></tr>@empty<tr><td colspan="6">No hay eventos para los filtros indicados.</td></tr>@endforelse</tbody></table></div>{{ $registros->links() }}</section></div>
@endsection

