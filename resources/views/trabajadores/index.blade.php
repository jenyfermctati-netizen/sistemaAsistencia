@extends('layouts.app')

@section('title', 'Trabajadores')
@section('page-title', 'Trabajadores')
@section('page-subtitle', 'Gestión del personal y accesos al sistema')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/trabajadores.css') }}">
@endpush

@section('content')

    {{-- ENCABEZADO --}}
    <div class="page-header">
        <div>
            <h2 class="page-header__title">Trabajadores</h2>
            <p class="page-header__description">Administra el personal registrado en el sistema.</p>
        </div>

        <div class="page-header__actions">

            <a href="{{ route('trabajadores.horarios') }}" class="button button--secondary">
                Horarios
            </a>

            <button type="button" class="button button--primary" data-modal-open="modalCrearTrabajador">
                + Nuevo trabajador
            </button>

        </div>
    </div>

    {{-- PANEL PRINCIPAL --}}
    <section class="panel">
        {{-- FILTROS --}}
        <div class="panel__header">
            <form method="GET" action="{{ route('trabajadores.index') }}" class="trabajadores-filtros">
                <input type="text" name="buscar" value="{{ request('buscar') }}" class="form-control"
                    placeholder="DNI, nombre, correo o código...">

                <select name="area_id" class="form-select">
                    <option value="">Todas las áreas</option>
                    @foreach ($areas as $area)
                        <option value="{{ $area->id }}" @selected(request('area_id') == $area->id)>
                            {{ $area->nombre }}
                        </option>
                    @endforeach
                </select>

                <select name="tipo_vinculo" class="form-select">
                    <option value="">Todos los vínculos</option>
                    <option value="CONTRATADO" @selected(request('tipo_vinculo') === 'CONTRATADO')>Contratado</option>
                    <option value="LOCADOR" @selected(request('tipo_vinculo') === 'LOCADOR')>Locador</option>
                </select>

                <select name="estado" class="form-select">
                    <option value="">Todos los estados</option>
                    <option value="ACTIVO" @selected(request('estado') === 'ACTIVO')>Activo</option>
                    <option value="INACTIVO" @selected(request('estado') === 'INACTIVO')>Inactivo</option>
                </select>

                <button type="submit" class="button button--secondary">Filtrar</button>
                <a href="{{ route('trabajadores.index') }}" class="button button--secondary">Limpiar</a>
            </form>
        </div>

        {{-- TABLA --}}
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>DNI</th>
                        <th>Trabajador</th>
                        <th>Área</th>
                        <th>Vínculo</th>
                        <th>Cargo</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($trabajadores as $trabajador)
                        <tr>
                            <td>{{ $trabajador->dni }}</td>
                            <td>
                                <strong>{{ $trabajador->nombres }} {{ $trabajador->apellidos }}</strong>
                                @if ($trabajador->codigo_biometrico)
                                    <div class="table-secondary-text">Biométrico: {{ $trabajador->codigo_biometrico }}
                                    </div>
                                @endif
                            </td>
                            <td>{{ $trabajador->area?->nombre ?? '-' }}</td>
                            <td>
                                <span
                                    class="badge {{ $trabajador->tipo_vinculo === 'CONTRATADO' ? 'badge--primary' : 'badge--info' }}">
                                    {{ $trabajador->tipo_vinculo }}
                                </span>
                            </td>
                            <td>{{ $trabajador->cargo ?? '-' }}</td>
                            <td>{{ $trabajador->user?->email ?? '-' }}</td>
                            <td>{{ $trabajador->user?->rol?->nombre ?? '-' }}</td>
                            <td>
                                @if ($trabajador->estado === 'ACTIVO')
                                    <span class="badge badge--success">Activo</span>
                                @else
                                    <span class="badge badge--danger">Inactivo</span>
                                @endif
                            </td>
                            <td>
                                <div class="table-actions">
                                    {{-- EDITAR --}}
                                    <button type="button" class="button button--secondary button--small"
                                        data-editar-trabajador
                                        data-update-url="{{ route('trabajadores.update', $trabajador) }}"
                                        data-area-id="{{ $trabajador->area_id }}"
                                        data-codigo-biometrico="{{ $trabajador->codigo_biometrico }}"
                                        data-dni="{{ $trabajador->dni }}" data-nombres="{{ $trabajador->nombres }}"
                                        data-apellidos="{{ $trabajador->apellidos }}"
                                        data-tipo-vinculo="{{ $trabajador->tipo_vinculo }}"
                                        data-cargo="{{ $trabajador->cargo }}" data-telefono="{{ $trabajador->telefono }}"
                                        data-fecha-ingreso="{{ $trabajador->fecha_ingreso?->format('Y-m-d') }}"
                                        data-fecha-fin="{{ $trabajador->fecha_fin?->format('Y-m-d') }}"
                                        data-estado="{{ $trabajador->estado }}"
                                        data-email="{{ $trabajador->user?->email }}"
                                        data-rol-id="{{ $trabajador->user?->rol_id }}">
                                        Editar
                                    </button>

                                    {{-- CAMBIAR ESTADO --}}
                                    <button type="button"
                                        class="button button--small {{ $trabajador->estado === 'ACTIVO' ? 'button--danger' : 'button--primary' }}"
                                        data-cambiar-estado
                                        data-name="{{ $trabajador->nombres }} {{ $trabajador->apellidos }}"
                                        data-estado="{{ $trabajador->estado }}"
                                        data-url="{{ route('trabajadores.estado', $trabajador) }}">
                                        {{ $trabajador->estado === 'ACTIVO' ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="table-empty">No se encontraron trabajadores.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- PAGINACIÓN --}}
    <div class="pagination-container">
        {{ $trabajadores->onEachSide(1)->links('components.pagination') }}
    </div>

    {{-- MODAL CREAR TRABAJADOR --}}
    <x-modal id="modalCrearTrabajador" title="Nuevo trabajador" size="large">
        <form method="POST" action="{{ route('trabajadores.store') }}" class="form">
            @csrf

            {{-- DATOS PERSONALES --}}
            <div class="form-section">
                <h3 class="form-section__title">Datos personales</h3>
                <div class="form-grid form-grid--2">
                    <div class="form-group">
                        <label class="form-label">DNI *</label>
                        <input type="text" name="dni" value="{{ old('dni') }}" class="form-control"
                            maxlength="15" required>
                        @error('dni')
                            <small class="form-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Código biométrico</label>
                        <input type="text" name="codigo_biometrico" value="{{ old('codigo_biometrico') }}"
                            class="form-control" maxlength="50">
                        @error('codigo_biometrico')
                            <small class="form-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nombres *</label>
                        <input type="text" name="nombres" value="{{ old('nombres') }}" class="form-control"
                            maxlength="100" required>
                        @error('nombres')
                            <small class="form-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Apellidos *</label>
                        <input type="text" name="apellidos" value="{{ old('apellidos') }}" class="form-control"
                            maxlength="100" required>
                        @error('apellidos')
                            <small class="form-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" value="{{ old('telefono') }}"
                            class="form-control telefono-input" maxlength="9" inputmode="numeric" pattern="[0-9]{9}"
                            placeholder="999999999">
                        @error('telefono')
                            <small class="form-error">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- DATOS LABORALES --}}
            <div class="form-section">
                <h3 class="form-section__title">Datos laborales</h3>
                <div class="form-grid form-grid--2">
                    <div class="form-group">
                        <label class="form-label">Área *</label>
                        <select name="area_id" class="form-select" required>
                            <option value="">Seleccione</option>
                            @foreach ($areas as $area)
                                <option value="{{ $area->id }}" @selected(old('area_id') == $area->id)>
                                    {{ $area->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('area_id')
                            <small class="form-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Cargo</label>
                        <input type="text" name="cargo" value="{{ old('cargo') }}" class="form-control"
                            maxlength="150">
                        @error('cargo')
                            <small class="form-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tipo de vínculo *</label>
                        <select name="tipo_vinculo" class="form-select" required>
                            <option value="">Seleccione</option>
                            <option value="CONTRATADO" @selected(old('tipo_vinculo') === 'CONTRATADO')>Contratado</option>
                            <option value="LOCADOR" @selected(old('tipo_vinculo') === 'LOCADOR')>Locador</option>
                        </select>
                        @error('tipo_vinculo')
                            <small class="form-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Estado *</label>
                        <select name="estado" class="form-select" required>
                            <option value="ACTIVO" @selected(old('estado', 'ACTIVO') === 'ACTIVO')>Activo</option>
                            <option value="INACTIVO" @selected(old('estado') === 'INACTIVO')>Inactivo</option>
                        </select>
                        @error('estado')
                            <small class="form-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Fecha de ingreso</label>
                        <input type="date" name="fecha_ingreso" value="{{ old('fecha_ingreso') }}"
                            class="form-control">
                        @error('fecha_ingreso')
                            <small class="form-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Fecha de fin</label>
                        <input type="date" name="fecha_fin" value="{{ old('fecha_fin') }}" class="form-control">
                        @error('fecha_fin')
                            <small class="form-error">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- ACCESO AL SISTEMA --}}
            <div class="form-section">
                <h3 class="form-section__title">Acceso al sistema</h3>
                <div class="form-grid form-grid--2">
                    <div class="form-group form-group--full">
                        <label class="form-label">Correo electrónico *</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control"
                            maxlength="255" required>
                        @error('email')
                            <small class="form-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Rol *</label>
                        <select name="rol_id" class="form-select" required>
                            <option value="">Seleccione</option>
                            @foreach ($roles as $rol)
                                <option value="{{ $rol->id }}" @selected(old('rol_id') == $rol->id)>
                                    {{ $rol->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('rol_id')
                            <small class="form-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div></div>

                    <div class="form-group">
                        <label class="form-label">Contraseña *</label>
                        <input type="password" name="password" class="form-control" minlength="8"
                            autocomplete="new-password" required>
                        <small class="form-help">Mínimo 8 caracteres.</small>
                        @error('password')
                            <small class="form-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Confirmar contraseña *</label>
                        <input type="password" name="password_confirmation" class="form-control" minlength="8"
                            autocomplete="new-password" required>
                    </div>
                </div>
            </div>

            <div class="modal__actions">
                <button type="button" class="button button--secondary" data-modal-close>Cancelar</button>
                <button type="submit" class="button button--primary">Guardar trabajador</button>
            </div>
        </form>
    </x-modal>

    {{-- MODAL EDITAR TRABAJADOR --}}
    <x-modal id="modalEditarTrabajador" title="Editar trabajador" size="large">
        <form method="POST" id="formEditarTrabajador" class="form">
            @csrf
            @method('PUT')

            {{-- DATOS PERSONALES --}}
            <div class="form-section">
                <h3 class="form-section__title">Datos personales</h3>
                <div class="form-grid form-grid--2">
                    <div class="form-group">
                        <label class="form-label">DNI *</label>
                        <input id="editarDni" type="text" name="dni" class="form-control" maxlength="15"
                            required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Código biométrico</label>
                        <input id="editarCodigoBiometrico" type="text" name="codigo_biometrico" class="form-control"
                            maxlength="50">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nombres *</label>
                        <input id="editarNombres" type="text" name="nombres" class="form-control" maxlength="100"
                            required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Apellidos *</label>
                        <input id="editarApellidos" type="text" name="apellidos" class="form-control"
                            maxlength="100" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input id="editarTelefono" type="text" name="telefono" class="form-control telefono-input"
                            maxlength="9" inputmode="numeric" pattern="[0-9]{9}" placeholder="999999999">
                    </div>
                </div>
            </div>

            {{-- DATOS LABORALES --}}
            <div class="form-section">
                <h3 class="form-section__title">Datos laborales</h3>
                <div class="form-grid form-grid--2">
                    <div class="form-group">
                        <label class="form-label">Área *</label>
                        <select id="editarArea" name="area_id" class="form-select" required>
                            @foreach ($areas as $area)
                                <option value="{{ $area->id }}">{{ $area->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Cargo</label>
                        <input id="editarCargo" type="text" name="cargo" class="form-control" maxlength="150">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tipo de vínculo *</label>
                        <select id="editarTipoVinculo" name="tipo_vinculo" class="form-select" required>
                            <option value="CONTRATADO">Contratado</option>
                            <option value="LOCADOR">Locador</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Estado *</label>
                        <select id="editarEstado" name="estado" class="form-select" required>
                            <option value="ACTIVO">Activo</option>
                            <option value="INACTIVO">Inactivo</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Fecha ingreso</label>
                        <input id="editarFechaIngreso" type="date" name="fecha_ingreso" class="form-control">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Fecha fin</label>
                        <input id="editarFechaFin" type="date" name="fecha_fin" class="form-control">
                    </div>
                </div>
            </div>

            {{-- ACCESO AL SISTEMA --}}
            <div class="form-section">
                <h3 class="form-section__title">Acceso al sistema</h3>
                <div class="form-grid form-grid--2">
                    <div class="form-group">
                        <label class="form-label">Correo electrónico *</label>
                        <input id="editarEmail" type="email" name="email" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Rol *</label>
                        <select id="editarRol" name="rol_id" class="form-select" required>
                            @foreach ($roles as $rol)
                                <option value="{{ $rol->id }}">{{ $rol->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nueva contraseña</label>
                        <input type="password" name="password" class="form-control" minlength="8"
                            autocomplete="new-password">
                        <small class="form-help">Déjala vacía para conservar la actual.</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Confirmar nueva contraseña</label>
                        <input type="password" name="password_confirmation" class="form-control" minlength="8"
                            autocomplete="new-password">
                    </div>
                </div>
            </div>

            <div class="modal__actions">
                <button type="button" class="button button--secondary" data-modal-close>Cancelar</button>
                <button type="submit" class="button button--primary">Guardar cambios</button>
            </div>
        </form>
    </x-modal>

    {{-- MODAL CAMBIAR ESTADO --}}
    <x-modal id="modalEstadoTrabajador" title="Cambiar estado" size="small">
        <p id="estadoTrabajadorMensaje"></p>

        <form method="POST" id="formEstadoTrabajador">
            @csrf
            @method('PATCH')

            <div class="modal__actions">
                <button type="button" class="button button--secondary" data-modal-close>Cancelar</button>
                <button type="submit" id="btnConfirmarEstado" class="button button--danger">Confirmar</button>
            </div>
        </form>
    </x-modal>

@endsection

@push('scripts')
    <script src="{{ asset('js/pages/trabajadores.js') }}"></script>
@endpush
