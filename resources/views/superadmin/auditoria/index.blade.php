@extends('layouts.app')

@section('title', 'Ingresos de administradores | CONNEXT')
@section('page-title', 'Ingresos de administradores')
@section('page-subtitle', 'Cantidad de accesos registrados por cuenta')

@push('styles')<link rel="stylesheet" href="{{ asset('css/pages/superadmin.css') }}">@endpush

@section('content')
    <div class="sa-page">
        <section class="sa-panel">
            <div class="sa-section-heading"><div><span class="sa-eyebrow">ACCESOS</span><h2>Ingresos por administrador</h2></div><span class="sa-count-pill">{{ $administradores->total() }} cuentas</span></div>
            <form class="sa-filter-form" method="GET">
                <label class="sa-field sa-field--search"><span>Buscar</span><input name="buscar" value="{{ request('buscar') }}" placeholder="Administrador o empresa"></label>
                <div class="sa-filter-actions"><button class="sa-button" type="submit">Buscar</button>@if(request()->filled('buscar'))<a class="sa-button sa-button--quiet" href="{{ route('superadmin.auditoria.index') }}">Limpiar</a>@endif</div>
            </form>
            <div class="sa-table-wrap"><table class="sa-table sa-login-table"><thead><tr><th>Administrador</th><th>Empresa</th><th>Ingresos</th></tr></thead><tbody>
                @forelse($administradores as $admin)
                    <tr><td><strong>{{ $admin->name }}</strong><small>{{ $admin->email }}</small></td><td>{{ $admin->empresa?->razon_social ?: '—' }}</td><td><span class="sa-login-count">{{ number_format($admin->ingresos_count) }}</span></td></tr>
                @empty
                    <tr><td colspan="3" class="sa-empty-state"><strong>No hay administradores para mostrar</strong><span>Prueba otro término de búsqueda.</span></td></tr>
                @endforelse
            </tbody></table></div>
            <div class="sa-pagination">{{ $administradores->links() }}</div>
        </section>
    </div>
@endsection
