<x-modal id="modalAgregarAvance" title="Agregar avance de hoy" size="large">
    <form method="POST" action="{{ route('reportesAvance.agregar', $reporteHoy) }}" enctype="multipart/form-data"
        id="formAgregarAvance" class="form">
        @csrf

        @if ($errors->agregarAvance->any())
            <div class="live-form-errors" role="alert">
                <strong>Revisa el nuevo avance:</strong>
                <ul>
                    @foreach ($errors->agregarAvance->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="reporte-modal-fecha">
            <div>
                <span>Se agregará al reporte correspondiente a</span>
                <strong>{{ now()->format('d/m/Y') }}</strong>
            </div>
            <small>Las actividades anteriores se conservarán.</small>
        </div>

        <div class="form-section">
            <div class="form-section__header">
                <div>
                    <h4>Nuevas actividades realizadas</h4>
                    <p>Describe cada avance adicional que realizaste durante el día.</p>
                </div>

                <button type="button" class="button button--secondary button--small" id="agregarActividadAdicional">
                    + Agregar actividad
                </button>
            </div>

            <div id="actividadesAdicionalesContainer" class="items-dinamicos">
                @foreach (old('actividades', [['descripcion' => '']]) as $indice => $actividadAnterior)
                    <div class="item-dinamico actividad-form-item">
                        <div class="item-dinamico__header">
                            <strong class="item-titulo">Actividad {{ $indice + 1 }}</strong>
                            <button type="button" class="item-eliminar" data-eliminar-item>Eliminar</button>
                        </div>

                        <input type="hidden" data-campo="id">

                        <div class="form-group">
                            <label class="form-label">¿Qué avance realizaste? *</label>
                            <input type="text" name="actividades[{{ $indice }}][descripcion]"
                                data-campo="descripcion" value="{{ $actividadAnterior['descripcion'] ?? '' }}"
                                class="form-control" maxlength="500"
                                placeholder="Ej. Completé la validación del módulo de usuarios" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Archivo o imagen de sustento</label>
                            <input type="file" name="actividades[{{ $indice }}][archivo]"
                                data-campo="archivo" class="form-control"
                                accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                            <small class="form-help">Opcional · máximo 5 MB.</small>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="form-section form-section--pendientes">
            <div class="form-section__header">
                <div>
                    <h4>Nuevos pendientes</h4>
                    <p>Si algo quedó por continuar, agrégalo para que sea visible en la pestaña Pendientes.</p>
                </div>

                <button type="button" class="button button--secondary button--small" id="agregarPendienteAdicional">
                    + Agregar pendiente
                </button>
            </div>

            <div id="pendientesAdicionalesContainer" class="items-dinamicos">
                @foreach (old('pendientes', []) as $indice => $pendienteAnterior)
                    <div class="item-dinamico pendiente-form-item">
                        <div class="item-dinamico__header">
                            <strong class="item-titulo">Pendiente {{ $indice + 1 }}</strong>
                            <button type="button" class="item-eliminar" data-eliminar-item>Eliminar</button>
                        </div>

                        <input type="hidden" data-campo="id">

                        <div class="form-group">
                            <label class="form-label">¿Qué quedó pendiente? *</label>
                            <input type="text" data-campo="descripcion"
                                name="pendientes[{{ $indice }}][descripcion]"
                                value="{{ $pendienteAnterior['descripcion'] ?? '' }}" class="form-control"
                                maxlength="500" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Detalle</label>
                            <textarea data-campo="detalle" name="pendientes[{{ $indice }}][detalle]"
                                class="form-textarea" rows="3" maxlength="2000">{{ $pendienteAnterior['detalle'] ?? '' }}</textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Ruta o ubicación de archivos</label>
                            <input type="text" data-campo="ruta_archivos"
                                name="pendientes[{{ $indice }}][ruta_archivos]"
                                value="{{ $pendienteAnterior['ruta_archivos'] ?? '' }}" class="form-control"
                                maxlength="500">
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="sin-pendientes" id="sinPendientesAdicionales">
                No has agregado nuevos pendientes.
            </div>
        </div>

        @if ($reporteHoy->estado === 'REVISADO')
            <p class="reporte-revision-aviso">
                Al guardar nuevos avances, el reporte volverá a estado pendiente para que pueda revisarse otra vez.
            </p>
        @endif

        <div class="modal__actions">
            <button type="button" class="button button--secondary" data-modal-close>Cancelar</button>
            <button type="submit" class="button button--primary">Agregar al reporte de hoy</button>
        </div>
    </form>
</x-modal>
