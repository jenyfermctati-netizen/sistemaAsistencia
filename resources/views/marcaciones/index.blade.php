@extends('layouts.app')

@section('title','Marcaciones')
@section('page-title','Marcaciones')
@section('page-subtitle','Registro y consulta de marcaciones del personal')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/marcaciones.css') }}">
@endpush

@section('content')
<div class="marcaciones-page marcaciones-page--listado">

    <header class="marcaciones-header">
        <div>
            <h2>Marcaciones</h2>
            <p>Consulta los registros del biométrico y las marcaciones realizadas manualmente.</p>
        </div>

        <a href="{{ route('marcaciones.manual') }}" class="button button--primary">
            <x-ui-icon name="plus" /> <span>Registrar asistencia manual</span>
        </a>
    </header>

    @if(session('success'))
        <div class="marcaciones-alert marcaciones-alert--success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="marcaciones-alert marcaciones-alert--error">{{ session('error') }}</div>
    @endif

    <div data-live-region="marcaciones" data-live-poll="5000">
        <section class="marcaciones-panel">

            <div class="mc-panel-intro">
                <div class="mc-panel-intro__icon" aria-hidden="true">✓</div>
                <div class="mc-panel-intro__copy">
                    <h3>Historial de asistencia</h3>
                    <p>Busca, filtra y consulta cada registro del personal.</p>
                </div>
                <div class="mc-panel-intro__count" aria-label="Total de registros encontrados">
                    <strong>{{ $marcaciones->total() }}</strong>
                    <span>Registros encontrados</span>
                </div>
            </div>

            <form method="GET" action="{{ route('marcaciones.index') }}" class="marcaciones-filtros">
                <label class="mc-field marcaciones-filtros__buscar">
                    <span class="mc-field__label">Buscar trabajador</span>
                    <input type="text" name="buscar" value="{{ request('buscar') }}" class="form-control" placeholder="DNI, trabajador o biométrico...">
                </label>

                <details class="marcaciones-filtros__avanzados" data-mc-details="filtros"
                    @if(request()->filled('fecha_desde') || request()->filled('fecha_hasta') || request()->filled('tipo') || request()->filled('origen') || request()->filled('estado')) open @endif>
                    <summary class="marcaciones-filtros__toggle">Más filtros <span class="mc-chevron" aria-hidden="true"></span></summary>
                    <div class="marcaciones-filtros__grid">
                        <label class="mc-field">
                            <span class="mc-field__label">Desde</span>
                            <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}" class="form-control">
                        </label>
                        <label class="mc-field">
                            <span class="mc-field__label">Hasta</span>
                            <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}" class="form-control">
                        </label>
                        <label class="mc-field">
                            <span class="mc-field__label">Tipo</span>
                            <select name="tipo" class="form-select">
                                <option value="">Todos los tipos</option>
                                <option value="ENTRADA" @selected(request('tipo')==='ENTRADA')>Entrada</option>
                                <option value="SALIDA" @selected(request('tipo')==='SALIDA')>Salida</option>
                            </select>
                        </label>
                        <label class="mc-field">
                            <span class="mc-field__label">Origen</span>
                            <select name="origen" class="form-select">
                                <option value="">Todos los orígenes</option>
                                <option value="BIOMETRICO" @selected(request('origen')==='BIOMETRICO')>Biométrico</option>
                                <option value="MANUAL" @selected(request('origen')==='MANUAL')>Manual</option>
                            </select>
                        </label>
                        <label class="mc-field">
                            <span class="mc-field__label">Estado</span>
                            <select name="estado" class="form-select">
                                <option value="">Todos los estados</option>
                                <option value="ACTIVA" @selected(request('estado')==='ACTIVA')>Activas</option>
                                <option value="ANULADA" @selected(request('estado')==='ANULADA')>Anuladas</option>
                            </select>
                        </label>
                    </div>
                </details>

                @if(request()->filled('fecha_desde') || request()->filled('fecha_hasta') || request()->filled('tipo') || request()->filled('origen') || request()->filled('estado'))
                    <div class="marcaciones-filtros__resumen" aria-label="Filtros aplicados">
                        @if(request()->filled('fecha_desde'))<span>Desde: {{ request('fecha_desde') }}</span>@endif
                        @if(request()->filled('fecha_hasta'))<span>Hasta: {{ request('fecha_hasta') }}</span>@endif
                        @if(request()->filled('tipo'))<span>Tipo: {{ request('tipo') }}</span>@endif
                        @if(request()->filled('origen'))<span>Origen: {{ request('origen') }}</span>@endif
                        @if(request()->filled('estado'))<span>Estado: {{ request('estado') }}</span>@endif
                    </div>
                @endif

                <div class="marcaciones-filtros__actions">
                    <button type="submit" class="button button--primary"><x-ui-icon name="filter" /> Filtrar</button>
                    <a href="{{ route('marcaciones.index') }}" class="button button--secondary"><x-ui-icon name="reset" /> Restablecer</a>
                </div>
            </form>

            <div class="table-container">
                <table class="marcaciones-table">
                    <thead>
                        <tr>
                            <th scope="col">Trabajador</th>
                            <th scope="col">Fecha y hora</th>
                            <th scope="col" class="mc-col-preservada" aria-hidden="true">Hora</th>
                            <th scope="col">Tipo</th>
                            <th scope="col">Origen</th>
                            <th scope="col" class="mc-col-preservada" aria-hidden="true">Dispositivo / Motivo</th>
                            <th scope="col">Estado</th>
                            <th scope="col"><span class="mc-sr-only">Detalle y acciones</span></th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($marcaciones as $marcacion)
                            <tr class="marcacion-row {{ $marcacion->anulada ? 'row-anulada' : '' }}">
                                <td class="marcacion-cell--trabajador" data-label="Trabajador">
                                    <div class="trabajador-info">
                                        <div class="trabajador-info__avatar">
                                            {{ mb_strtoupper(mb_substr($marcacion->trabajador?->nombres ?? 'T',0,1)) }}
                                        </div>
                                        <div>
                                            <strong>{{ $marcacion->trabajador?->nombres }} {{ $marcacion->trabajador?->apellidos }}</strong>
                                            <span>DNI: {{ $marcacion->trabajador?->dni ?? '-' }}</span>
                                            @if($marcacion->trabajador?->area)
                                                <small class="mc-worker-area">Área: {{ $marcacion->trabajador->area->nombre }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- La hora visual usa CSS para conservar el texto de fecha que pueda leer el JS actual. --}}
                                <td class="marcacion-cell--fecha" data-label="Fecha y hora" data-hora-visible="{{ $marcacion->fecha_hora?->format('H:i:s') }}" aria-label="{{ $marcacion->fecha_hora?->format('d/m/Y H:i:s') }}"><span class="marcacion-fecha-hora__fecha">{{ $marcacion->fecha_hora?->format('d/m/Y') }}</span></td>
                                {{-- Se conserva la posición de las ocho celdas para los selectores del JavaScript existente. --}}
                                <td class="mc-col-preservada" aria-hidden="true"><strong class="marcacion-hora">{{ $marcacion->fecha_hora?->format('H:i:s') }}</strong></td>

                                <td class="marcacion-cell--tipo" data-label="Tipo">
                                    <span class="badge {{ $marcacion->tipo==='ENTRADA' ? 'badge--success' : 'badge--info' }}">
                                        {{ ucfirst(strtolower($marcacion->tipo)) }}
                                    </span>
                                </td>

                                <td class="marcacion-cell--origen" data-label="Origen">
                                    <span class="badge {{ $marcacion->origen==='BIOMETRICO' ? 'badge--primary' : 'badge--warning' }}">
                                        {{ $marcacion->origen==='BIOMETRICO' ? 'Biométrico' : 'Manual' }}
                                    </span>
                                </td>

                                <td class="mc-col-preservada" aria-hidden="true">
                                    @if($marcacion->origen==='BIOMETRICO')
                                        <strong>{{ $marcacion->dispositivo ?? 'Biométrico' }}</strong>
                                        @if($marcacion->evento_externo_id)
                                            <span class="table-secondary-text">Evento: {{ $marcacion->evento_externo_id }}</span>
                                        @endif
                                    @else
                                        <span>{{ $marcacion->motivo_manual ?? '-' }}</span>
                                        @if($marcacion->registradoPor)
                                            <span class="table-secondary-text">Registrado por: {{ $marcacion->registradoPor->name }}</span>
                                        @endif
                                    @endif
                                </td>

                                <td class="marcacion-cell--estado" data-label="Estado">
                                    @if(!$marcacion->anulada)
                                        <span class="badge badge--success">Activa</span>
                                    @else
                                        <span class="badge badge--danger">Anulada</span>
                                        @if($marcacion->motivo_anulacion)
                                            <span class="table-secondary-text">{{ $marcacion->motivo_anulacion }}</span>
                                        @endif
                                    @endif
                                </td>

                                <td class="marcacion-cell--acciones">
                                    <details class="marcacion-detalle" data-mc-details="marcacion-{{ $marcacion->getKey() }}">
                                        <summary class="marcacion-detalle__toggle">
                                            <x-ui-icon name="eye" /><span>Ver detalle</span><span class="mc-chevron" aria-hidden="true"></span>
                                        </summary>
                                        <div class="marcacion-detalle__panel">
                                            <div class="marcacion-detalle__heading">Detalle de la marcación</div>
                                            <dl class="marcacion-detalle__datos">
                                                <div class="mc-detalle-area">
                                                    <dt>Área</dt>
                                                    <dd>{{ $marcacion->trabajador?->area?->nombre ?? 'Sin área' }}</dd>
                                                </div>
                                                @if($marcacion->origen==='BIOMETRICO')
                                                    <div><dt>Dispositivo</dt><dd>{{ $marcacion->dispositivo ?? 'Biométrico' }}</dd></div>
                                                    @if($marcacion->evento_externo_id)
                                                        <div><dt>Evento</dt><dd>{{ $marcacion->evento_externo_id }}</dd></div>
                                                    @endif
                                                @else
                                                    <div><dt>Motivo</dt><dd>{{ $marcacion->motivo_manual ?? '-' }}</dd></div>
                                                    @if($marcacion->registradoPor)
                                                        <div><dt>Registrado por</dt><dd>{{ $marcacion->registradoPor->name }}</dd></div>
                                                    @endif
                                                @endif
                                                @if($marcacion->anulada && $marcacion->motivo_anulacion)
                                                    <div><dt>Motivo de anulación</dt><dd>{{ $marcacion->motivo_anulacion }}</dd></div>
                                                @endif
                                            </dl>
                                            <div class="marcacion-detalle__actions">
                                    @if(!$marcacion->anulada)
                                        <button type="button" class="button button--danger button--small"
                                            data-anular-marcacion
                                            data-url="{{ route('marcaciones.anular',$marcacion) }}"
                                            data-trabajador="{{ $marcacion->trabajador?->nombres }} {{ $marcacion->trabajador?->apellidos }}"
                                            data-fecha="{{ $marcacion->fecha_hora?->format('d/m/Y H:i') }}">
                                            Anular
                                        </button>
                                    @else
                                        <span class="table-secondary-text">—</span>
                                    @endif
                                            </div>
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr class="marcacion-row--empty">
                                <td colspan="8" class="table-empty">
                                    <div class="empty-state">
                                        <strong>No se encontraron marcaciones</strong>
                                        <span>Prueba modificando los filtros.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="pagination-container">
            {{ $marcaciones->onEachSide(1)->links('components.pagination') }}
        </div>
    </div>

    <x-modal id="modalAnularMarcacion" title="Anular marcación" size="small">
        <form method="POST" id="formAnularMarcacion" class="form" data-live-form data-live-target="marcaciones">
            @csrf
            @method('PATCH')

            <div id="anularMarcacionInfo" class="anulacion-info"></div>

            <div class="form-group">
                <label class="form-label">Motivo de anulación *</label>
                <textarea name="motivo_anulacion" class="form-textarea" rows="4" maxlength="500"
                    placeholder="Indica por qué se anula esta marcación..." required></textarea>
            </div>

            <div class="modal__actions">
                <button type="button" class="button button--secondary" data-modal-close>Cancelar</button>
                <button type="submit" class="button button--danger">Anular marcación</button>
            </div>
        </form>
    </x-modal>

</div>
@endsection

@push('scripts')
<script src="{{ asset('js/pages/marcaciones.js') }}"></script>
<script>
/* Estado de presentación: conserva los desplegables durante el refresco existente.
   No envía peticiones ni interviene en formularios, rutas, horarios o anulaciones. */
(() => {
    const initMarcacionesPresentation = () => {
        const page = document.querySelector('.marcaciones-page--listado');
        if (!page || page.__mcPresentationBound) return;
        page.__mcPresentationBound = true;

        const detailsState = new Map();
        const knownDetails = new WeakSet();
        const remember = (details) => {
            knownDetails.add(details);
            detailsState.set(details.dataset.mcDetails, details.open);
        };
        page.querySelectorAll('details[data-mc-details]').forEach(remember);

        const closeRecordDetails = (except = null) => {
            page.querySelectorAll('.marcacion-detalle[open]').forEach((details) => {
                if (details === except) return;
                details.open = false;
                remember(details);
            });
        };

        page.addEventListener('toggle', (event) => {
            const details = event.target;
            if (!(details instanceof HTMLDetailsElement) || !details.dataset.mcDetails || !page.contains(details)) return;
            if (details.open && details.classList.contains('marcacion-detalle')) closeRecordDetails(details);
            remember(details);
        }, true);

        document.addEventListener('click', (event) => {
            if (!page.isConnected || !(event.target instanceof Node)) return;
            const active = page.querySelector('.marcacion-detalle[open]');
            if (active && !active.contains(event.target)) closeRecordDetails();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape' || !page.isConnected) return;
            const active = page.querySelector('.marcacion-detalle[open]');
            if (!active) return;
            const focusWasInside = active.contains(document.activeElement);
            closeRecordDetails();
            if (focusWasInside) active.querySelector('summary')?.focus();
        });

        new MutationObserver(() => {
            page.querySelectorAll('details[data-mc-details]').forEach((details) => {
                if (knownDetails.has(details)) return;
                knownDetails.add(details);
                const key = details.dataset.mcDetails;
                if (detailsState.has(key)) {
                    const wasOpen = detailsState.get(key);
                    if (details.open !== wasOpen) details.open = wasOpen;
                } else {
                    remember(details);
                }
            });
        }).observe(page, { childList: true, subtree: true });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMarcacionesPresentation, { once: true });
    } else {
        initMarcacionesPresentation();
    }
})();
</script>
@endpush
