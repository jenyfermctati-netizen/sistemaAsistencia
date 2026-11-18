@extends('layouts.app')
@section('title', 'Auditoría de empresa')
@section('page-title', 'Auditoría de empresa')
@section('content')
    <section class="panel">
        <h2>Historial de operaciones</h2>
        <div class="table-responsive"><table class="table"><thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Recurso</th><th>Registro</th></tr></thead><tbody>
            @forelse($eventos as $evento)
                <tr><td>{{ $evento->created_at?->format('d/m/Y H:i') }}</td><td>{{ $evento->usuario?->name ?? 'Sistema' }}</td><td>{{ $evento->accion }}</td><td>{{ $evento->tabla }}</td><td>{{ $evento->registro_id ?? '—' }}</td></tr>
            @empty
                <tr><td colspan="5">Todavía no hay operaciones registradas.</td></tr>
            @endforelse
        </tbody></table></div>
        {{ $eventos->links() }}
    </section>
@endsection
