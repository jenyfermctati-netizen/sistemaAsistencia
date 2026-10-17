@php
    $erroresAvance = $errors->avanceTrabajador;
    $trabajadorSeleccionadoId = old('reporte_trabajador_id');
    $trabajadorSeleccionadoNombre = old('reporte_trabajador_nombre', 'Selecciona un trabajador');
    $trabajadorSeleccionadoArea = old('reporte_trabajador_area', 'Sin área');
    $actividadesAvance = old('actividades', [['descripcion' => '']]);
    $pendientesAvance = old('pendientes', []);
    $rutaAvanceBase = route('trabajadores.reportes-avance.store', '__TRABAJADOR__');
@endphp

<x-modal id="modalRegistrarAvanceTrabajador" title="Registrar avance diario" size="large">
    <form method="POST"
        action="{{ $trabajadorSeleccionadoId
            ? route('trabajadores.reportes-avance.store', $trabajadorSeleccionadoId)
            : '#' }}"
        enctype="multipart/form-data"
        id="formRegistrarAvanceTrabajador"
        class="form"
        data-has-errors="{{ $erroresAvance->any() ? '1' : '0' }}"
        data-store-url-template="{{ $rutaAvanceBase }}">

        @csrf

        <input type="hidden"
            name="reporte_trabajador_id"
            id="reporteTrabajadorId"
            value="{{ $trabajadorSeleccionadoId }}">

        <input type="hidden"
            name="reporte_trabajador_nombre"
            id="reporteTrabajadorNombreValor"
            value="{{ $trabajadorSeleccionadoNombre }}">

        <input type="hidden"
            name="reporte_trabajador_area"
            id="reporteTrabajadorAreaValor"
            value="{{ $trabajadorSeleccionadoArea }}">

        @if($erroresAvance->any())
            <div class="avance-form-errors" role="alert">
                <strong>Revisa la información del avance:</strong>
                <ul>
                    @foreach($erroresAvance->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @else
            <div class="avance-form-errors" role="alert" hidden></div>
        @endif

        <div class="avance-trabajador-seleccion">
            <div>
                <span>Trabajador</span>
                <strong id="reporteTrabajadorNombre">{{ $trabajadorSeleccionadoNombre }}</strong>
            </div>

            <div>
                <span>Área</span>
                <strong id="reporteTrabajadorArea">{{ $trabajadorSeleccionadoArea }}</strong>
            </div>

            <div>
                <span>Fecha</span>
                <strong>{{ now()->format('d/m/Y') }}</strong>
            </div>
        </div>

        <section class="avance-form-section">
            <div class="avance-form-section__heading">
                <div>
                    <h3>Actividades realizadas</h3>
                    <p>Registra cada actividad completada durante el día.</p>
                </div>

                <button type="button"
                    class="button button--secondary button--small"
                    data-agregar-actividad-avance>
                    + Agregar actividad
                </button>
            </div>

            <div id="actividadesAvanceTrabajador" class="avance-items">
                @foreach($actividadesAvance as $indice => $actividad)
                    <div class="avance-item"
                        data-actividad-avance
                        data-index="{{ $indice }}">

                        <div class="avance-item__heading">
                            <strong>
                                Actividad <span data-numero-actividad>{{ $loop->iteration }}</span>
                            </strong>

                            <button type="button"
                                class="button-link button-link--danger"
                                data-quitar-actividad-avance
                                @if($loop->count === 1) hidden @endif>
                                Quitar
                            </button>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Descripción *</label>
                            <input type="text"
                                name="actividades[{{ $indice }}][descripcion]"
                                value="{{ $actividad['descripcion'] ?? '' }}"
                                class="form-control"
                                maxlength="500"
                                placeholder="Ej. Verificó el inventario del almacén"
                                required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Archivo o imagen de sustento</label>
                            <input type="file"
                                name="actividades[{{ $indice }}][archivo]"
                                class="form-control"
                                accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                            <small class="form-help">Opcional · máximo 5 MB.</small>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="avance-form-section">
            <div class="avance-form-section__heading">
                <div>
                    <h3>Pendientes</h3>
                    <p>Agrega las tareas que deberán continuarse posteriormente.</p>
                </div>

                <button type="button"
                    class="button button--secondary button--small"
                    data-agregar-pendiente-avance>
                    + Agregar pendiente
                </button>
            </div>

            <div id="pendientesAvanceTrabajador" class="avance-items">
                @foreach($pendientesAvance as $indice => $pendiente)
                    <div class="avance-item avance-item--pendiente"
                        data-pendiente-avance
                        data-index="{{ $indice }}">

                        <div class="avance-item__heading">
                            <strong>
                                Pendiente <span data-numero-pendiente>{{ $loop->iteration }}</span>
                            </strong>

                            <button type="button"
                                class="button-link button-link--danger"
                                data-quitar-pendiente-avance>
                                Quitar
                            </button>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Descripción *</label>
                            <input type="text"
                                name="pendientes[{{ $indice }}][descripcion]"
                                value="{{ $pendiente['descripcion'] ?? '' }}"
                                class="form-control"
                                maxlength="500"
                                required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Detalle</label>
                            <textarea name="pendientes[{{ $indice }}][detalle]"
                                class="form-textarea"
                                rows="3"
                                maxlength="2000">{{ $pendiente['detalle'] ?? '' }}</textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Ruta o ubicación de archivos</label>
                            <input type="text"
                                name="pendientes[{{ $indice }}][ruta_archivos]"
                                value="{{ $pendiente['ruta_archivos'] ?? '' }}"
                                class="form-control"
                                maxlength="500">
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="modal__actions">
            <button type="button" class="button button--secondary" data-modal-close>
                Cancelar
            </button>

            <button type="submit" class="button button--primary">
                Guardar avance
            </button>
        </div>
    </form>

    <template id="templateActividadAvanceTrabajador">
        <div class="avance-item" data-actividad-avance data-index="__INDEX__">
            <div class="avance-item__heading">
                <strong>Actividad <span data-numero-actividad></span></strong>
                <button type="button"
                    class="button-link button-link--danger"
                    data-quitar-actividad-avance>
                    Quitar
                </button>
            </div>

            <div class="form-group">
                <label class="form-label">Descripción *</label>
                <input type="text"
                    name="actividades[__INDEX__][descripcion]"
                    class="form-control"
                    maxlength="500"
                    placeholder="Ej. Verificó el inventario del almacén"
                    required>
            </div>

            <div class="form-group">
                <label class="form-label">Archivo o imagen de sustento</label>
                <input type="file"
                    name="actividades[__INDEX__][archivo]"
                    class="form-control"
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                <small class="form-help">Opcional · máximo 5 MB.</small>
            </div>
        </div>
    </template>

    <template id="templatePendienteAvanceTrabajador">
        <div class="avance-item avance-item--pendiente"
            data-pendiente-avance
            data-index="__INDEX__">

            <div class="avance-item__heading">
                <strong>Pendiente <span data-numero-pendiente></span></strong>
                <button type="button"
                    class="button-link button-link--danger"
                    data-quitar-pendiente-avance>
                    Quitar
                </button>
            </div>

            <div class="form-group">
                <label class="form-label">Descripción *</label>
                <input type="text"
                    name="pendientes[__INDEX__][descripcion]"
                    class="form-control"
                    maxlength="500"
                    required>
            </div>

            <div class="form-group">
                <label class="form-label">Detalle</label>
                <textarea name="pendientes[__INDEX__][detalle]"
                    class="form-textarea"
                    rows="3"
                    maxlength="2000"></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Ruta o ubicación de archivos</label>
                <input type="text"
                    name="pendientes[__INDEX__][ruta_archivos]"
                    class="form-control"
                    maxlength="500">
            </div>
        </div>
    </template>
</x-modal>
