@extends('layouts.app')

@section('title', 'Áreas')
@section('page-title', 'Áreas')
@section('page-subtitle', 'Gestión de áreas de la empresa')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/areas.css') }}">
@endpush

@section('content')

    <div class="page-header">
        <div>
            <h2 class="page-header__title">Áreas</h2>
            <p class="page-header__description">Administra las áreas de la empresa.</p>
        </div>

        <button type="button" class="button button--primary" data-modal-open="modalCrearArea">
            + Nueva área
        </button>
    </div>

    <section class="panel">
        <div class="panel__header">
            <form method="GET" action="{{ route('areas.index') }}" class="areas-filtros">
                <input type="text" name="buscar" value="{{ request('buscar') }}" class="form-control"
                    placeholder="Buscar área...">

                <select name="estado" class="form-select">
                    <option value="">Todos los estados</option>
                    <option value="1" @selected(request('estado') === '1')>Activa</option>
                    <option value="0" @selected(request('estado') === '0')>Inactiva</option>
                </select>

                <button type="submit" class="button button--secondary">Filtrar</button>
                <a href="{{ route('areas.index') }}" class="button button--secondary">Limpiar</a>
            </form>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Área</th>
                        <th>Descripción</th>
                        <th>Trabajadores</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($areas as $area)
                        <tr>
                            <td>
                                <strong>{{ $area->nombre }}</strong>
                            </td>
                            <td>{{ $area->descripcion ?? '-' }}</td>
                            <td>{{ $area->trabajadores_count }}</td>
                            <td>
                                @if ($area->estado)
                                    <span class="badge badge--success">Activa</span>
                                @else
                                    <span class="badge badge--danger">Inactiva</span>
                                @endif
                            </td>
                            <td>
                                <div class="table-actions">
                                    <button type="button" class="button button--secondary button--small" data-editar-area
                                        data-update-url="{{ route('areas.update', $area) }}"
                                        data-nombre="{{ $area->nombre }}" data-descripcion="{{ $area->descripcion }}"
                                        data-estado="{{ $area->estado ? 1 : 0 }}">
                                        Editar
                                    </button>

                                    <button type="button"
                                        class="button button--small {{ $area->estado ? 'button--danger' : 'button--primary' }}"
                                        data-cambiar-estado-area data-nombre="{{ $area->nombre }}"
                                        data-estado="{{ $area->estado ? 1 : 0 }}"
                                        data-url="{{ route('areas.estado', $area) }}">
                                        {{ $area->estado ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="table-empty">No se encontraron áreas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="pagination-container">
        {{ $areas->links() }}
    </div>

    {{-- MODAL CREAR --}}
    <x-modal id="modalCrearArea" title="Nueva área">
        <form method="POST" action="{{ route('areas.store') }}" class="form">
            @csrf

            <div class="form-group">
                <label class="form-label">Nombre *</label>
                <input type="text" name="nombre" class="form-control" maxlength="150" required>
            </div>

            <div class="form-group">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-textarea" maxlength="500"></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Estado *</label>
                <select name="estado" class="form-select" required>
                    <option value="1">Activa</option>
                    <option value="0">Inactiva</option>
                </select>
            </div>

            <div class="modal__actions">
                <button type="button" class="button button--secondary" data-modal-close>Cancelar</button>
                <button type="submit" class="button button--primary">Guardar</button>
            </div>
        </form>
    </x-modal>

    {{-- MODAL EDITAR --}}
    <x-modal id="modalEditarArea" title="Editar área">
        <form method="POST" id="formEditarArea" class="form">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label class="form-label">Nombre *</label>
                <input id="editarAreaNombre" type="text" name="nombre" class="form-control" required>
            </div>

            <div class="form-group">
                <label class="form-label">Descripción</label>
                <textarea id="editarAreaDescripcion" name="descripcion" class="form-textarea"></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Estado *</label>
                <select id="editarAreaEstado" name="estado" class="form-select" required>
                    <option value="1">Activa</option>
                    <option value="0">Inactiva</option>
                </select>
            </div>

            <div class="modal__actions">
                <button type="button" class="button button--secondary" data-modal-close>Cancelar</button>
                <button type="submit" class="button button--primary">Guardar cambios</button>
            </div>
        </form>
    </x-modal>

    {{-- MODAL CAMBIAR ESTADO --}}
    <x-modal id="modalEstadoArea" title="Cambiar estado" size="small">
        <p id="estadoAreaMensaje"></p>

        <form method="POST" id="formEstadoArea">
            @csrf
            @method('PATCH')

            <div class="modal__actions">
                <button type="button" class="button button--secondary" data-modal-close>Cancelar</button>
                <button type="submit" id="btnConfirmarEstadoArea" class="button button--danger">Confirmar</button>
            </div>
        </form>
    </x-modal>

@endsection

@push('scripts')
    <script src="{{ asset('js/pages/areas.js') }}"></script>
@endpush
