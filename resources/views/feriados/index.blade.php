@extends('layouts.app')

@section('title', 'Feriados')
@section('page-title', 'Feriados')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/feriados.css') }}">
@endpush

@section('content')
    {{-- ENCABEZADO --}}
    <div class="page-header">
        <div>
            <h2 class="page-header__title">Gestión de feriados</h2>
            <p class="page-header__description">
                Administra los días feriados y no laborables que afectan al control de asistencia.
            </p>
        </div>

        <div class="page-header__actions">
            <form method="POST" action="{{ route('feriados.sincronizar') }}" class="sincronizar-feriados">
                @csrf
                <div class="sincronizar-feriados__campo">
                    <label for="anioSincronizacion">Año</label>
                    <input type="number" id="anioSincronizacion" name="anio" value="{{ request('anio', now()->year) }}"
                        min="2020" max="2100" required>
                </div>

                    <button type="submit" class="button button--secondary">
                        <x-ui-icon name="sync" />
                        <span>Sincronizar nacionales</span>
                </button>
            </form>

                <button type="button" class="button button--primary" data-modal-open="modalCrearFeriado">
                    <x-ui-icon name="plus" />
                    <span>Nuevo feriado</span>
            </button>
        </div>
    </div>

    {{-- FILTROS --}}
    <section class="panel feriados-panel">
        <div class="panel__header">
            <form method="GET" action="{{ route('feriados.index') }}" class="feriados-filtros">
                <label class="filter-field">
                    <span class="filter-field__label">Buscar feriado</span>
                    <input type="text" name="buscar" value="{{ request('buscar') }}" class="form-control"
                        placeholder="Nombre o descripción...">
                </label>

                <label class="filter-field">
                    <span class="filter-field__label">Tipo</span>
                    <select name="tipo" class="form-select">
                        <option value="">Todos los tipos</option>
                        <option value="NACIONAL" @selected(request('tipo') === 'NACIONAL')>Nacional</option>
                        <option value="REGIONAL" @selected(request('tipo') === 'REGIONAL')>Regional</option>
                        <option value="LOCAL" @selected(request('tipo') === 'LOCAL')>Local</option>
                        <option value="INSTITUCIONAL" @selected(request('tipo') === 'INSTITUCIONAL')>Institucional</option>
                    </select>
                </label>

                <label class="filter-field">
                    <span class="filter-field__label">Área de aplicación</span>
                    <select name="area_id" class="form-select">
                        <option value="">Todas las áreas</option>
                        <option value="GENERAL" @selected(request('area_id') === 'GENERAL')>General / todas las áreas</option>
                        @foreach ($areas as $area)
                            <option value="{{ $area->id }}" @selected(request('area_id') == $area->id)>
                                {{ $area->nombre }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="filter-field">
                    <span class="filter-field__label">Estado</span>
                    <select name="estado" class="form-select">
                        <option value="">Todos los estados</option>
                        <option value="ACTIVO" @selected(request('estado') === 'ACTIVO')>Activos</option>
                        <option value="INACTIVO" @selected(request('estado') === 'INACTIVO')>Inactivos</option>
                    </select>
                </label>

                <label class="filter-field">
                    <span class="filter-field__label">Año</span>
                    <input type="number" name="anio" value="{{ request('anio') }}" class="form-control" min="2020"
                        max="2100" placeholder="Ej. {{ now()->year }}">
                </label>

                <div class="filter-actions">
                    <button type="submit" class="button button--primary"><x-ui-icon name="filter" /> Filtrar</button>
                    <a href="{{ route('feriados.index') }}" class="button button--secondary"><x-ui-icon name="reset" /> Restablecer</a>
                </div>
            </form>
        </div>

        {{-- TABLA --}}
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Aplicación</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($feriados as $feriado)
                        <tr>
                            <td data-label="Fecha">
                                <div class="feriado-fecha">
                                    <strong>{{ $feriado->fecha?->format('d') }}</strong>
                                    <span>{{ $feriado->fecha?->locale('es')->translatedFormat('M Y') }}</span>
                                </div>
                            </td>

                            <td data-label="Nombre">
                                <strong>{{ $feriado->nombre }}</strong>
                            </td>

                            <td data-label="Tipo">
                                <span class="feriado-tipo feriado-tipo--{{ strtolower($feriado->tipo) }}">
                                    {{ ucfirst(strtolower($feriado->tipo)) }}
                                </span>
                            </td>

                            <td data-label="Aplicación">
                                @if ($feriado->area)
                                    <span class="area-badge">{{ $feriado->area->nombre }}</span>
                                @else
                                    <span class="area-badge area-badge--general">Todas las áreas</span>
                                @endif
                            </td>

                            <td data-label="Descripción">
                                <span class="feriado-descripcion">
                                    {{ $feriado->descripcion ?: 'Sin descripción' }}
                                </span>
                            </td>

                            <td data-label="Estado">
                                @if ($feriado->estado)
                                    <span class="badge badge--success">Activo</span>
                                @else
                                    <span class="badge badge--secondary">Inactivo</span>
                                @endif
                            </td>

                            <td data-label="Acciones">
                                <div class="table-actions">
                                    <button type="button" class="icon-button"
                                        aria-label="Editar feriado {{ $feriado->nombre }}" data-tooltip="Editar"
                                        data-editar-feriado data-url="{{ route('feriados.update', $feriado) }}"
                                        data-fecha="{{ $feriado->fecha?->format('Y-m-d') }}"
                                        data-nombre="{{ $feriado->nombre }}" data-tipo="{{ $feriado->tipo }}"
                                        data-area="{{ $feriado->area_id ?? '' }}"
                                        data-descripcion="{{ $feriado->descripcion }}">
                                        <x-ui-icon name="edit" />
                                    </button>

                                    <button type="button"
                                        class="icon-button {{ $feriado->estado ? 'icon-button--danger' : '' }}"
                                        aria-label="{{ $feriado->estado ? 'Desactivar' : 'Activar' }} feriado {{ $feriado->nombre }}"
                                        data-tooltip="{{ $feriado->estado ? 'Desactivar' : 'Activar' }}"
                                        data-cambiar-estado-feriado data-url="{{ route('feriados.estado', $feriado) }}"
                                        data-nombre="{{ $feriado->nombre }}"
                                        data-activo="{{ $feriado->estado ? '1' : '0' }}">
                                        <x-ui-icon name="power" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="table-empty">
                                <div class="empty-state">
                                    <strong>No hay feriados registrados</strong>
                                    <span>Registra el primer feriado para comenzar.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="pagination-container">
        {{ $feriados->onEachSide(1)->links('components.pagination') }}
    </div>

    {{-- MODAL CREAR --}}
    <x-modal id="modalCrearFeriado" title="Nuevo feriado">
        <form method="POST" action="{{ route('feriados.store') }}" class="form" data-form-crear-feriado
            data-reopen-on-error="{{ $errors->any() && old('_form') === 'crear' ? '1' : '0' }}">
            @csrf
            <input type="hidden" name="_form" value="crear">

            <div class="form-grid form-grid--2">
                <div class="form-group">
                    <label class="form-label">Fecha *</label>
                    <input type="date" name="fecha" value="{{ old('fecha') }}" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Tipo *</label>
                    <select name="tipo" class="form-select" required>
                        <option value="">Seleccione</option>
                        <option value="NACIONAL" @selected(old('tipo') === 'NACIONAL')>Nacional</option>
                        <option value="REGIONAL" @selected(old('tipo') === 'REGIONAL')>Regional</option>
                        <option value="LOCAL" @selected(old('tipo') === 'LOCAL')>Local</option>
                        <option value="INSTITUCIONAL" @selected(old('tipo') === 'INSTITUCIONAL')>Institucional</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Nombre *</label>
                <input type="text" name="nombre" value="{{ old('nombre') }}" class="form-control" maxlength="150"
                    placeholder="Ej. Fiestas Patrias" required>
            </div>

            <div class="form-group">
                <label class="form-label">Área de aplicación</label>
                <select name="area_id" class="form-select">
                    <option value="">Todas las áreas</option>
                    @foreach ($areas as $area)
                        <option value="{{ $area->id }}" @selected(old('area_id') == $area->id)>{{ $area->nombre }}</option>
                    @endforeach
                </select>
                <small class="form-help">
                    Déjalo en "Todas las áreas" si el feriado aplica a todo el personal.
                </small>
            </div>

            <div class="form-group">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-textarea" rows="3" maxlength="500"
                    placeholder="Descripción opcional...">{{ old('descripcion') }}</textarea>
            </div>

            <div class="modal__actions">
                <button type="button" class="button button--secondary" data-modal-close>Cancelar</button>
                <button type="submit" class="button button--primary">Guardar feriado</button>
            </div>
        </form>
    </x-modal>

    {{-- MODAL EDITAR --}}
    <x-modal id="modalEditarFeriado" title="Editar feriado">
        <form method="POST" id="formEditarFeriado" class="form">
            @csrf
            @method('PUT')

            <div class="form-grid form-grid--2">
                <div class="form-group">
                    <label class="form-label">Fecha *</label>
                    <input type="date" name="fecha" id="editarFeriadoFecha" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Tipo *</label>
                    <select name="tipo" id="editarFeriadoTipo" class="form-select" required>
                        <option value="NACIONAL">Nacional</option>
                        <option value="REGIONAL">Regional</option>
                        <option value="LOCAL">Local</option>
                        <option value="INSTITUCIONAL">Institucional</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Nombre *</label>
                <input type="text" name="nombre" id="editarFeriadoNombre" class="form-control" maxlength="150"
                    required>
            </div>

            <div class="form-group">
                <label class="form-label">Área de aplicación</label>
                <select name="area_id" id="editarFeriadoArea" class="form-select">
                    <option value="">Todas las áreas</option>
                    @foreach ($areas as $area)
                        <option value="{{ $area->id }}">{{ $area->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" id="editarFeriadoDescripcion" class="form-textarea" rows="3" maxlength="500"></textarea>
            </div>

            <div class="modal__actions">
                <button type="button" class="button button--secondary" data-modal-close>Cancelar</button>
                <button type="submit" class="button button--primary">Guardar cambios</button>
            </div>
        </form>
    </x-modal>

    {{-- MODAL ESTADO --}}
    <x-modal id="modalEstadoFeriado" title="Cambiar estado" size="small">
        <form method="POST" id="formEstadoFeriado">
            @csrf
            @method('PATCH')

            <p id="estadoFeriadoMensaje"></p>

            <div class="modal__actions">
                <button type="button" class="button button--secondary" data-modal-close>Cancelar</button>
                <button type="submit" id="btnEstadoFeriado" class="button button--danger">Confirmar</button>
            </div>
        </form>
    </x-modal>

@endsection

@push('scripts')
    <script src="{{ asset('js/pages/feriados.js') }}"></script>
@endpush
