@extends('layouts.app')

@section('title', 'Administradores | CONNEXT')
@section('page-title', 'Administradores')
@section('page-subtitle', 'Elige una empresa para gestionar sus administradores y configuración')

@push('styles')<link rel="stylesheet" href="{{ asset('css/pages/superadmin.css') }}">@endpush

@section('content')
    <div class="sa-page">
        <section class="sa-panel">
            <div class="sa-section-heading"><div><span class="sa-eyebrow">EMPRESAS</span><h2>Administradores por empresa</h2><p>Abre una empresa para revisar sus accesos y ajustes.</p></div><span class="sa-count-pill">{{ $empresas->total() }} empresas</span></div>
            <form class="sa-filter-form" method="GET">
                <label class="sa-field sa-field--search"><span>Buscar empresa</span><input name="buscar" value="{{ request('buscar') }}" placeholder="Razón social, nombre comercial o RUC"></label>
                <div class="sa-filter-actions"><button class="sa-button" type="submit">Buscar</button>@if(request()->filled('buscar'))<a class="sa-button sa-button--quiet" href="{{ route('superadmin.administradores.index') }}">Limpiar</a>@endif</div>
            </form>
            <div class="sa-table-wrap"><table class="sa-table"><thead><tr><th>Empresa</th><th>RUC</th><th>Administradores</th><th>Estado</th><th><span class="sr-only">Acciones</span></th></tr></thead><tbody>
                @forelse($empresas as $empresa)
                    <tr><td><strong class="sa-company-name"><i class="sa-company-color-dot" style="--company-color: {{ $empresa->color_turquesa ?: '#00A9C7' }}" aria-hidden="true"></i>{{ $empresa->nombre_comercial ?: $empresa->razon_social }}</strong><small>{{ $empresa->razon_social }}</small></td><td>{{ $empresa->ruc }}</td><td>{{ number_format($empresa->administradores_count) }}</td><td><span class="sa-status sa-status--{{ $empresa->estado }}">{{ ucfirst(strtolower($empresa->estado)) }}</span></td><td><a class="sa-icon-button" href="{{ route('superadmin.empresas.show', $empresa) }}#configuracion" aria-label="Administrar accesos y configuración de {{ $empresa->razon_social }}" title="Administrar empresa"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3 14 5l3-.2.8 2.9 2.5 1.7-1.1 2.8-3 0.8-1.5 2.6-2.9-.8-2.8 1.1-.8-2.9-2.5-1.7 1.1-2.8 3-.8L10 3.8 12 3Z"/><circle cx="12" cy="12" r="2.4"/></svg></a></td></tr>
                @empty
                    <tr><td colspan="5" class="sa-empty-state"><strong>No hay empresas para mostrar</strong><span>Prueba otro término de búsqueda.</span></td></tr>
                @endforelse
            </tbody></table></div>
            <div class="sa-pagination">{{ $empresas->links() }}</div>
        </section>
    </div>
@endsection
