@extends('layouts.app')

@section('title', 'Feriados')
@section('page-title', 'Feriados')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/feriados.css') }}">
@endpush

@section('content')

    {{-- MENSAJES --}}
    @if (session('success'))
        <div class="alert alert--success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert--danger">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert--danger">
            <strong>Revisa la información ingresada.</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

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
                    Sincronizar nacionales
                </button>
            </form>

            <button type="button" class="button button--primary" data-modal-open="modalCrearFeriado">
                + Nuevo feriado
            </button>
        </div>
    </div>

    {{-- FILTROS --}}
    <section class="panel">
        <div class="panel__header">
            <form method="GET" action="{{ route('feriados.index') }}" class="feriados-filtros">
                <input type="text" name="buscar" value="{{ request('buscar') }}" class="form-control"
                    placeholder="Nombre o descripción..." aria-label="Buscar feriado">

                <select name="tipo" class="form-select" aria-label="Tipo de feriado">
                    <option value="">Todos los tipos</option>
                    <option value="NACIONAL" @selected(request('tipo') === 'NACIONAL')>Nacional</option>
                    <option value="REGIONAL" @selected(request('tipo') === 'REGIONAL')>Regional</option>
                    <option value="LOCAL" @selected(request('tipo') === 'LOCAL')>Local</option>
                    <option value="INSTITUCIONAL" @selected(request('tipo') === 'INSTITUCIONAL')>Institucional</option>
                </select>

                <select name="area_id" class="form-select" aria-label="Área de aplicación">
                    <option value="">Todas las áreas</option>
                    <option value="GENERAL" @selected(request('area_id') === 'GENERAL')>General / todas las áreas</option>
                    @foreach ($areas as $area)
                        <option value="{{ $area->id }}" @selected(request('area_id') == $area->id)>
                            {{ $area->nombre }}
                        </option>
                    @endforeach
                </select>

                <select name="estado" class="form-select" aria-label="Estado del feriado">
                    <option value="">Todos los estados</option>
                    <option value="ACTIVO" @selected(request('estado') === 'ACTIVO')>Activos</option>
                    <option value="INACTIVO" @selected(request('estado') === 'INACTIVO')>Inactivos</option>
                </select>

                <input type="number" name="anio" value="{{ request('anio') }}" class="form-control" min="2020"
                    max="2100" placeholder="Año" aria-label="Año del feriado">

                <button type="submit" class="button button--secondary">Filtrar</button>
                <a href="{{ route('feriados.index') }}" class="button button--secondary">Limpiar</a>
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
                            <td>
                                <div class="feriado-fecha">
                                    <strong>{{ $feriado->fecha?->format('d') }}</strong>
                                    <span>{{ $feriado->fecha?->translatedFormat('M Y') }}</span>
                                </div>
                            </td>

                            <td>
                                <strong>{{ $feriado->nombre }}</strong>
                            </td>

                            <td>
                                <span class="feriado-tipo feriado-tipo--{{ strtolower($feriado->tipo) }}">
                                    {{ ucfirst(strtolower($feriado->tipo)) }}
                                </span>
                            </td>

                            <td>
                                @if ($feriado->area)
                                    <span class="area-badge">{{ $feriado->area->nombre }}</span>
                                @else
                                    <span class="area-badge area-badge--general">Todas las áreas</span>
                                @endif
                            </td>

                            <td>
                                <span class="feriado-descripcion">
                                    {{ $feriado->descripcion ?: 'Sin descripción' }}
                                </span>
                            </td>

                            <td>
                                @if ($feriado->estado)
                                    <span class="badge badge--success">Activo</span>
                                @else
                                    <span class="badge badge--secondary">Inactivo</span>
                                @endif
                            </td>

                            <td>
                                <div class="table-actions">
                                    <button type="button" class="button button--secondary button--small"
                                        data-editar-feriado data-url="{{ route('feriados.update', $feriado) }}"
                                        data-fecha="{{ $feriado->fecha?->format('Y-m-d') }}"
                                        data-nombre="{{ $feriado->nombre }}" data-tipo="{{ $feriado->tipo }}"
                                        data-area="{{ $feriado->area_id ?? '' }}"
                                        data-descripcion="{{ $feriado->descripcion }}">
                                        Editar
                                    </button>

                                    <button type="button"
                                        class="button button--small {{ $feriado->estado ? 'button--danger' : 'button--secondary' }}"
                                        data-cambiar-estado-feriado data-url="{{ route('feriados.estado', $feriado) }}"
                                        data-nombre="{{ $feriado->nombre }}"
                                        data-activo="{{ $feriado->estado ? '1' : '0' }}">
                                        {{ $feriado->estado ? 'Desactivar' : 'Activar' }}
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
        <form method="POST" action="{{ route('feriados.store') }}" class="form">
            @csrf

            <div class="form-grid form-grid--2">
                <div class="form-group">
                    <label class="form-label">Fecha *</label>
                    <input type="date" name="fecha" value="{{ old('fecha') }}" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Tipo *</label>
                    <select name="tipo" class="form-select" required>
                        <option value="">Seleccione</option>
                        <option value="NACIONAL">Nacional</option>
                        <option value="REGIONAL">Regional</option>
                        <option value="LOCAL">Local</option>
                        <option value="INSTITUCIONAL">Institucional</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Nombre *</label>
                <input type="text" name="nombre" class="form-control" maxlength="150"
                    placeholder="Ej. Fiestas Patrias" required>
            </div>

            <div class="form-group">
                <label class="form-label">Área de aplicación</label>
                <select name="area_id" class="form-select">
                    <option value="">Todas las áreas</option>
                    @foreach ($areas as $area)
                        <option value="{{ $area->id }}">{{ $area->nombre }}</option>
                    @endforeach
                </select>
                <small class="form-help">
                    Déjalo en "Todas las áreas" si el feriado aplica a todo el personal.
                </small>
            </div>

            <div class="form-group">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-textarea" rows="3" maxlength="500"
                    placeholder="Descripción opcional..."></textarea>
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
