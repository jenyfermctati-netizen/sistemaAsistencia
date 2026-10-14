@php
    $actividadesAnteriores = old('actividades', [['descripcion' => '']]);
    $pendientesAnteriores = old('pendientes', []);
@endphp

<x-modal id="modalRegistrarAvanceTrabajador" title="Registrar avance del trabajador" size="large">
    <form method="POST" id="formRegistrarAvanceTrabajador" class="form" enctype="multipart/form-data"
        data-store-url-template="{{ url('trabajadores/__TRABAJADOR__/reportes-avance') }}"
        data-has-errors="{{ $errors->avanceTrabajador->any() ? '1' : '0' }}">
        @csrf

        <input type="hidden" name="reporte_trabajador_id" id="reporteTrabajadorId"
            value="{{ old('reporte_trabajador_id') }}">
        <input type="hidden" name="reporte_trabajador_nombre" id="reporteTrabajadorNombreValor"
            value="{{ old('reporte_trabajador_nombre') }}">
        <input type="hidden" name="reporte_trabajador_area" id="reporteTrabajadorAreaValor"
            value="{{ old('reporte_trabajador_area') }}">

        @if ($errors->avanceTrabajador->any())
            <div class="avance-form-errors" role="alert">
                <strong>Revisa los datos del avance:</strong>
                <ul>
                    @foreach ($errors->avanceTrabajador->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="avance-trabajador-seleccion">
            <div>
                <span>Trabajador</span>
                <strong id="reporteTrabajadorNombre">{{ old('reporte_trabajador_nombre', 'Selecciona un trabajador') }}</strong>
            </div>
            <div>
                <span>Área</span>
                <strong id="reporteTrabajadorArea">{{ old('reporte_trabajador_area', '-') }}</strong>
            </div>
            <div>
                <span>Fecha del reporte</span>
                <strong>{{ now()->format('d/m/Y') }}</strong>
            </div>
        </div>

        <section class="avance-form-section">
            <div class="avance-form-section__heading">
                <div>
                    <h3>Actividades realizadas</h3>
                    <p>Registra todas las actividades que realizó el trabajador durante el día.</p>
                </div>
                <button type="button" class="button button--secondary button--small" data-agregar-actividad-avance>
                    + Agregar actividad
                </button>
            </div>

            <div id="actividadesAvanceTrabajador" class="avance-items">
                @foreach ($actividadesAnteriores as $indice => $actividad)
                    <div class="avance-item" data-actividad-avance data-index="{{ $indice }}">
                        <div class="avance-item__heading">
                            <strong>Actividad <span data-numero-actividad>{{ $loop->iteration }}</span></strong>
                            <button type="button" class="button-link button-link--danger"
                                data-quitar-actividad-avance>Quitar</button>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="actividad-avance-{{ $indice }}">Descripción *</label>
                            <textarea id="actividad-avance-{{ $indice }}" name="actividades[{{ $indice }}][descripcion]"
                                class="form-textarea" rows="3" maxlength="500" required>{{ $actividad['descripcion'] ?? '' }}</textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="archivo-avance-{{ $indice }}">Archivo de respaldo</label>
                            <input id="archivo-avance-{{ $indice }}" type="file"
                                name="actividades[{{ $indice }}][archivo]" class="form-control"
                                accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                            <small class="form-help">PDF, Office o imagen. Máximo 5 MB.</small>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="avance-form-section">
            <div class="avance-form-section__heading">
                <div>
                    <h3>Pendientes del área</h3>
                    <p>Son opcionales y serán visibles para todos los integrantes del área.</p>
                </div>
                <button type="button" class="button button--secondary button--small" data-agregar-pendiente-avance>
                    + Agregar pendiente
                </button>
            </div>

            <div id="pendientesAvanceTrabajador" class="avance-items">
                @foreach ($pendientesAnteriores as $indice => $pendiente)
                    <div class="avance-item avance-item--pendiente" data-pendiente-avance data-index="{{ $indice }}">
                        <div class="avance-item__heading">
                            <strong>Pendiente <span data-numero-pendiente>{{ $loop->iteration }}</span></strong>
                            <button type="button" class="button-link button-link--danger"
                                data-quitar-pendiente-avance>Quitar</button>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="pendiente-avance-{{ $indice }}">Tarea pendiente *</label>
                            <input id="pendiente-avance-{{ $indice }}" type="text"
                                name="pendientes[{{ $indice }}][descripcion]" value="{{ $pendiente['descripcion'] ?? '' }}"
                                class="form-control" maxlength="500" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="detalle-pendiente-avance-{{ $indice }}">Detalle</label>
                            <textarea id="detalle-pendiente-avance-{{ $indice }}" name="pendientes[{{ $indice }}][detalle]"
                                class="form-textarea" rows="3" maxlength="2000">{{ $pendiente['detalle'] ?? '' }}</textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="ruta-pendiente-avance-{{ $indice }}">Ruta de archivos</label>
                            <input id="ruta-pendiente-avance-{{ $indice }}" type="text"
                                name="pendientes[{{ $indice }}][ruta_archivos]"
                                value="{{ $pendiente['ruta_archivos'] ?? '' }}" class="form-control" maxlength="500">
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="modal__actions">
            <button type="button" class="button button--secondary" data-modal-close>Cancelar</button>
            <button type="submit" class="button button--primary">Guardar avance</button>
        </div>
    </form>

    <template id="templateActividadAvanceTrabajador">
        <div class="avance-item" data-actividad-avance data-index="__INDEX__">
            <div class="avance-item__heading">
                <strong>Actividad <span data-numero-actividad></span></strong>
                <button type="button" class="button-link button-link--danger" data-quitar-actividad-avance>Quitar</button>
            </div>
            <div class="form-group">
                <label class="form-label" for="actividad-avance-__INDEX__">Descripción *</label>
                <textarea id="actividad-avance-__INDEX__" name="actividades[__INDEX__][descripcion]"
                    class="form-textarea" rows="3" maxlength="500" required></textarea>
            </div>
            <div class="form-group">
                <label class="form-label" for="archivo-avance-__INDEX__">Archivo de respaldo</label>
                <input id="archivo-avance-__INDEX__" type="file" name="actividades[__INDEX__][archivo]"
                    class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                <small class="form-help">PDF, Office o imagen. Máximo 5 MB.</small>
            </div>
        </div>
    </template>

    <template id="templatePendienteAvanceTrabajador">
        <div class="avance-item avance-item--pendiente" data-pendiente-avance data-index="__INDEX__">
            <div class="avance-item__heading">
                <strong>Pendiente <span data-numero-pendiente></span></strong>
                <button type="button" class="button-link button-link--danger" data-quitar-pendiente-avance>Quitar</button>
            </div>
            <div class="form-group">
                <label class="form-label" for="pendiente-avance-__INDEX__">Tarea pendiente *</label>
                <input id="pendiente-avance-__INDEX__" type="text" name="pendientes[__INDEX__][descripcion]"
                    class="form-control" maxlength="500" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="detalle-pendiente-avance-__INDEX__">Detalle</label>
                <textarea id="detalle-pendiente-avance-__INDEX__" name="pendientes[__INDEX__][detalle]"
                    class="form-textarea" rows="3" maxlength="2000"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label" for="ruta-pendiente-avance-__INDEX__">Ruta de archivos</label>
                <input id="ruta-pendiente-avance-__INDEX__" type="text"
                    name="pendientes[__INDEX__][ruta_archivos]" class="form-control" maxlength="500">
            </div>
        </div>
    </template>
</x-modal>
