@if ($puedeGestionar && $pendiente->estado_revision === 'POR_REVISAR')
    <div class="pendiente-revision-acciones" aria-label="Revisar pendiente">
        <form method="POST" action="{{ route('reportesAvance.pendientes.revisar', $pendiente) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="decision" value="APROBADO">
            <button type="submit" class="pendiente-accion pendiente-accion--aprobar"
                title="Aprobar como completado" aria-label="Aprobar como completado">
                ✓
            </button>
        </form>

        <form method="POST" action="{{ route('reportesAvance.pendientes.revisar', $pendiente) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="decision" value="RECHAZADO">
            <button type="submit" class="pendiente-accion pendiente-accion--rechazar"
                title="Marcar como no completado" aria-label="Marcar como no completado">
                ✕
            </button>
        </form>

        <button type="button" class="pendiente-accion pendiente-accion--observar"
            data-observar-pendiente
            data-url="{{ route('reportesAvance.pendientes.revisar', $pendiente) }}"
            data-pendiente="{{ $pendiente->descripcion }}"
            title="Registrar una observación" aria-label="Registrar una observación">
            !
        </button>
    </div>
@endif
