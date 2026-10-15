<x-modal id="modalDetalleSolicitud"
    title="Detalle de solicitud"
    size="large">

    <div class="solicitud-detail-layout">

        {{-- INFORMACIÓN --}}
        <section class="solicitud-detail-info">

            <div class="solicitud-detail-person">

                <div class="solicitud-detail-avatar"
                    id="detalleSolicitudAvatar">
                    ?
                </div>

                <div>
                    <h3 id="detalleSolicitudTrabajador">
                        -
                    </h3>

                    <span id="detalleSolicitudDni">
                        -
                    </span>

                    <small id="detalleSolicitudArea">
                        -
                    </small>
                </div>

            </div>

            <div class="solicitud-detail-badges">

                <span id="detalleSolicitudTipo"
                    class="solicitud-tipo">
                    -
                </span>

                <span id="detalleSolicitudEstado"
                    class="solicitud-estado">
                    -
                </span>

            </div>

            <div class="solicitud-detail-grid">

                <div class="solicitud-detail-field">
                    <span>Fecha de inicio</span>
                    <strong id="detalleSolicitudInicio">-</strong>
                </div>

                <div class="solicitud-detail-field">
                    <span>Fecha de fin</span>
                    <strong id="detalleSolicitudFin">-</strong>
                </div>

                <div class="solicitud-detail-field">
                    <span>Hora de inicio</span>
                    <strong id="detalleSolicitudHoraInicio">-</strong>
                </div>

                <div class="solicitud-detail-field">
                    <span>Hora de fin</span>
                    <strong id="detalleSolicitudHoraFin">-</strong>
                </div>

            </div>

            <div class="solicitud-detail-motivo">

                <span>
                    Motivo de la solicitud
                </span>

                <p id="detalleSolicitudMotivo">
                    -
                </p>

            </div>

            {{-- REVISIÓN --}}
            <div class="solicitud-detail-review"
                id="detalleSolicitudRevision"
                hidden>

                <div>

                    <span>
                        Revisada por
                    </span>

                    <strong id="detalleSolicitudRevisadoPor">
                        -
                    </strong>

                </div>

                <div>

                    <span>
                        Fecha de revisión
                    </span>

                    <strong id="detalleSolicitudFechaRevision">
                        -
                    </strong>

                </div>

                <div class="solicitud-detail-review__comment">

                    <span>
                        Comentario
                    </span>

                    <p id="detalleSolicitudComentario">
                        -
                    </p>

                </div>

            </div>

            {{-- ACCIONES ADMINISTRADOR --}}
            @if($esAdministrador)

                <div class="solicitud-detail-actions"
                    id="detalleAdminActions"
                    hidden>

                    <button type="button"
                        class="button button--primary"
                        id="detalleSolicitudRevisar">
                        Revisar solicitud
                    </button>

                </div>

            @else

                {{-- ACCIONES TRABAJADOR --}}
                <div class="solicitud-detail-actions"
                    id="detalleWorkerActions"
                    hidden>

                    <button type="button"
                        class="button button--secondary"
                        id="detalleSolicitudEditar">
                        Editar
                    </button>

                    <button type="button"
                        class="button button--danger"
                        id="detalleSolicitudCancelar">
                        Cancelar solicitud
                    </button>

                </div>

            @endif

        </section>

        {{-- DOCUMENTO --}}
        <aside class="solicitud-document">

            <header class="solicitud-document__header">

                <div>
                    <strong>Documento adjunto</strong>
                    <span>Sustento de la solicitud</span>
                </div>

            </header>

            {{-- SIN DOCUMENTO --}}
            <div class="solicitud-document__empty"
                id="solicitudDocumentoVacio">

                <div class="solicitud-document__empty-icon">
                    ⧉
                </div>

                <strong>
                    Sin documento adjunto
                </strong>

                <span>
                    Esta solicitud no tiene un archivo de sustento.
                </span>

            </div>

            {{-- DOCUMENTO --}}
            <div class="solicitud-document__viewer"
                id="solicitudDocumentoViewer"
                hidden>

                <div class="solicitud-document__preview">

                    <iframe id="solicitudDocumentoFrame"
                        title="Vista previa del documento"
                        loading="lazy"
                        hidden>
                    </iframe>

                    <img id="solicitudDocumentoImagen"
                        alt="Sustento de la solicitud"
                        hidden>

                </div>

                <div class="solicitud-document__actions">

                    <a href="#"
                        id="solicitudDocumentoAbrir"
                        target="_blank"
                        rel="noopener"
                        class="button button--secondary">
                        Ver aparte
                    </a>

                    <a href="#"
                        id="solicitudDocumentoDescargar"
                        download
                        class="button button--primary">
                        Descargar
                    </a>

                </div>

            </div>

        </aside>

    </div>

</x-modal>