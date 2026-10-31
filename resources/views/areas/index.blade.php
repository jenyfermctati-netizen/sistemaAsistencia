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
            <x-ui-icon name="plus" />
            <span>Nueva área</span>
        </button>
    </div>

    <section class="panel areas-panel">
        <div class="panel__header">
            <form method="GET" action="{{ route('areas.index') }}" class="areas-filtros">
                <label class="filter-field">
                    <span class="filter-field__label">Buscar</span>
                    <input type="text" name="buscar" value="{{ request('buscar') }}" class="form-control"
                        placeholder="Nombre del área...">
                </label>

                <label class="filter-field">
                    <span class="filter-field__label">Estado</span>
                    <select name="estado" class="form-select">
                        <option value="">Todos los estados</option>
                        <option value="1" @selected(request('estado') === '1')>Activa</option>
                        <option value="0" @selected(request('estado') === '0')>Inactiva</option>
                    </select>
                </label>

                <div class="filter-actions">
                    <button type="submit" class="button button--primary"><x-ui-icon name="filter" /> Filtrar</button>
                    <a href="{{ route('areas.index') }}" class="button button--secondary"><x-ui-icon name="reset" /> Restablecer</a>
                </div>
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
                            <td data-label="Área">
                                <strong>{{ $area->nombre }}</strong>
                            </td>
                            <td data-label="Descripción">{{ $area->descripcion ?? '-' }}</td>
                            <td data-label="Trabajadores">{{ $area->trabajadores_count }}</td>
                            <td data-label="Estado">
                                @if ($area->estado)
                                    <span class="badge badge--success">Activa</span>
                                @else
                                    <span class="badge badge--danger">Inactiva</span>
                                @endif
                            </td>
                            <td data-label="Acciones">
                                <div class="table-actions">
                                    <button type="button" class="icon-button" data-editar-area
                                        aria-label="Editar área {{ $area->nombre }}" data-tooltip="Editar"
                                        data-update-url="{{ route('areas.update', $area) }}"
                                        data-nombre="{{ $area->nombre }}" data-descripcion="{{ $area->descripcion }}"
                                        data-estado="{{ $area->estado ? 1 : 0 }}">
                                        <x-ui-icon name="edit" />
                                    </button>

                                    <button type="button"
                                        class="icon-button {{ $area->estado ? 'icon-button--danger' : '' }}"
                                        aria-label="{{ $area->estado ? 'Desactivar' : 'Activar' }} área {{ $area->nombre }}"
                                        data-tooltip="{{ $area->estado ? 'Desactivar' : 'Activar' }}"
                                        data-cambiar-estado-area data-nombre="{{ $area->nombre }}"
                                        data-estado="{{ $area->estado ? 1 : 0 }}"
                                        data-url="{{ route('areas.estado', $area) }}">
                                        <x-ui-icon name="power" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="table-empty">
                                <div class="empty-state">
                                    <strong>No se encontraron áreas</strong>
                                    <span>Prueba con otros filtros o registra una nueva área.</span>
                                    <button type="button" class="button button--primary" data-modal-open="modalCrearArea">
                                        <x-ui-icon name="plus" /> Nueva área
                                    </button>
                                </div>
                            </td>
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
