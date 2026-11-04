@extends('layouts.app')

@section('title','Marcaciones')
@section('page-title','Marcaciones')
@section('page-subtitle','Registro y consulta de marcaciones del personal')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/marcaciones.css') }}">
<style>
/* Filtro compacto: los botones aplican los periodos inmediatamente */
.marcaciones-page--listado .mc-filtros-nuevos { padding: 18px 24px 20px; }
.mc-filtros-rapidos { display:flex; align-items:center; flex-wrap:wrap; gap:10px; }
.mc-filtros-rapidos__titulo { color:#526d80; font-size:13px; font-weight:800; letter-spacing:.025em; margin-right:6px; }
.mc-periodo-btn { appearance:none; border:1px solid #cee0e9; background:#fff; color:#284b61; border-radius:999px; padding:12px 20px; font:inherit; font-size:13px; font-weight:700; line-height:1.3; cursor:pointer; transition:background .15s,border-color .15s,color .15s; }
.mc-periodo-btn:hover,.mc-periodo-btn:focus-visible { border-color:#75c4d6; color:#087e99; }
.mc-periodo-btn.is-active { border-color:#7bd1e4; background:#e5f7fa; color:#0086a3; }
@media(max-width:640px) { .marcaciones-page--listado .mc-filtros-nuevos { padding:16px; } .mc-filtros-rapidos__titulo { width:100%; } .mc-periodo-btn { padding:10px 15px; } }


/* ================================================================
   Filtro rápido responsive: listado de marcaciones.
   Pantalla amplia: una fila. Tablet: envoltura compacta.
   Móvil: cuatro botones en cuadrícula de dos columnas.
   ================================================================ */
.marcaciones-page--listado .mc-filtros-nuevos {
    padding: 14px 18px;
    min-width: 0;
}
.marcaciones-page--listado .mc-filtros-rapidos {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    width: 100%;
    min-width: 0;
}
.marcaciones-page--listado .mc-filtros-rapidos__titulo {
    margin-right: 8px;
    color: var(--mc-muted);
    font-size: 11px;
    letter-spacing: .045em;
}
.marcaciones-page--listado .mc-periodo-btn {
    min-height: 38px;
    padding: 8px 14px;
    border-radius: 999px;
    font-size: 12px;
    line-height: 1.25;
    white-space: nowrap;
    flex: 0 0 auto;
}
@media (max-width: 760px) {
    .marcaciones-page--listado .mc-filtros-nuevos { padding: 12px 14px; }
    .marcaciones-page--listado .mc-filtros-rapidos { gap: 7px; }
    .marcaciones-page--listado .mc-filtros-rapidos__titulo {
        flex: 0 0 100%;
        width: 100%;
        margin-right: 0;
    }
    .marcaciones-page--listado .mc-periodo-btn {
        min-height: 36px;
        padding: 7px 12px;
    }
    .marcaciones-page--listado .mc-panel-intro {
        gap: 10px;
        padding: 12px 14px;
    }
    .marcaciones-page--listado .mc-panel-intro__icon {
        width: 36px;
        height: 36px;
        flex-basis: 36px;
        border-radius: 10px;
        font-size: 19px;
    }
    .marcaciones-page--listado .mc-panel-intro h3 { font-size: 15px; }
    .marcaciones-page--listado .mc-panel-intro p { font-size: 11px; }
    .marcaciones-page--listado .mc-panel-intro__count {
        margin-left: 0;
        padding: 6px 9px;
    }
    .marcaciones-page--listado .mc-panel-intro__count strong { font-size: 17px; }
}
@media (max-width: 480px) {
    .marcaciones-page--listado .mc-filtros-nuevos { padding: 11px 12px; }
    .marcaciones-page--listado .mc-filtros-rapidos {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 7px;
    }
    .marcaciones-page--listado .mc-filtros-rapidos__titulo {
        grid-column: 1 / -1;
        width: auto;
        margin: 0 0 2px;
    }
    .marcaciones-page--listado .mc-periodo-btn {
        width: 100%;
        min-width: 0;
        min-height: 38px;
        padding: 7px 6px;
        font-size: 12px;
        white-space: normal;
        text-align: center;
    }
    .marcaciones-page--listado .mc-panel-intro {
        display: grid;
        grid-template-columns: 34px minmax(0, 1fr) auto;
        align-items: center;
        gap: 8px;
        padding: 10px 12px;
    }
    .marcaciones-page--listado .mc-panel-intro__icon {
        width: 34px;
        height: 34px;
        flex-basis: auto;
    }
    .marcaciones-page--listado .mc-panel-intro__copy { min-width: 0; }
    .marcaciones-page--listado .mc-panel-intro h3 { font-size: 13px; }
    .marcaciones-page--listado .mc-panel-intro p { font-size: 10px; line-height: 1.25; }
    .marcaciones-page--listado .mc-panel-intro__count {
        width: auto;
        min-width: 0;
        margin: 0;
        padding: 5px 7px;
        justify-items: center;
    }
    .marcaciones-page--listado .mc-panel-intro__count strong { font-size: 15px; }
    .marcaciones-page--listado .mc-panel-intro__count span { font-size: 9px; }
}

</style>

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

            {{-- Las fechas se envían ocultas para que el controlador filtre la tabla. --}}
            <form method="GET" action="{{ route('marcaciones.index') }}" class="marcaciones-filtros mc-filtros-nuevos" id="mcFiltrosForm">
                @php
                    $periodoSeleccionado = request('periodo', 'hoy');
                    $fechaHoy = now()->toDateString();
                    $fechaAyer = now()->subDay()->toDateString();
                    $fechaInicioSemana = now()->subDays(6)->toDateString();
                    $fechaDesdeInicial = match ($periodoSeleccionado) {
                        'todo' => '',
                        'ayer' => $fechaAyer,
                        '7dias' => $fechaInicioSemana,
                        default => $fechaHoy,
                    };
                    $fechaHastaInicial = match ($periodoSeleccionado) {
                        'todo' => '',
                        'ayer' => $fechaAyer,
                        default => $fechaHoy,
                    };
                @endphp
                <input type="hidden" name="periodo" value="{{ $periodoSeleccionado }}" data-mc-periodo>
                <input type="hidden" name="fecha_desde" value="{{ request('fecha_desde', $fechaDesdeInicial) }}">
                <input type="hidden" name="fecha_hasta" value="{{ request('fecha_hasta', $fechaHastaInicial) }}">

                <div class="mc-filtros-rapidos" role="group" aria-label="Periodo rápido">
                    <span class="mc-filtros-rapidos__titulo">PERIODO RÁPIDO</span>
                    <button type="button" class="mc-periodo-btn {{ $periodoSeleccionado === 'todo' ? 'is-active' : '' }}" data-mc-periodo-btn="todo" aria-pressed="{{ $periodoSeleccionado === 'todo' ? 'true' : 'false' }}">Todo</button>
                    <button type="button" class="mc-periodo-btn {{ $periodoSeleccionado === 'hoy' ? 'is-active' : '' }}" data-mc-periodo-btn="hoy" aria-pressed="{{ $periodoSeleccionado === 'hoy' ? 'true' : 'false' }}">Hoy</button>
                    <button type="button" class="mc-periodo-btn {{ $periodoSeleccionado === 'ayer' ? 'is-active' : '' }}" data-mc-periodo-btn="ayer" aria-pressed="{{ $periodoSeleccionado === 'ayer' ? 'true' : 'false' }}">Ayer</button>
                    <button type="button" class="mc-periodo-btn {{ $periodoSeleccionado === '7dias' ? 'is-active' : '' }}" data-mc-periodo-btn="7dias" aria-pressed="{{ $periodoSeleccionado === '7dias' ? 'true' : 'false' }}">Últimos 7 días</button>
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
                                        {{ match($marcacion->origen) {
                                            'BIOMETRICO' => 'Biométrico',
                                            'WEB_CAMPO' => 'Web campo',
                                            default => 'Manual',
                                        } }}
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
<script>
(() => {
    if (window.__mcPeriodosBound) return;
    window.__mcPeriodosBound = true;
    const fechaLocal = (date) => [date.getFullYear(), String(date.getMonth() + 1).padStart(2, '0'), String(date.getDate()).padStart(2, '0')].join('-');

    // Al entrar por primera vez, incluir las fechas de hoy en la URL para
    // que Laravel filtre también el resultado de la tabla, no solo el botón.
    if (!window.location.search) {
        const hoy = fechaLocal(new Date());
        const url = new URL(window.location.href);
        url.searchParams.set('periodo', 'hoy');
        url.searchParams.set('fecha_desde', hoy);
        url.searchParams.set('fecha_hasta', hoy);
        window.location.replace(url.toString());
        return;
    }

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-mc-periodo-btn]');
        if (!button) return;
        const form = button.closest('form');
        if (!form) return;
        const periodo = button.dataset.mcPeriodoBtn;
        const desde = form.querySelector('[name="fecha_desde"]');
        const hasta = form.querySelector('[name="fecha_hasta"]');
        const hoy = new Date();
        const inicio = new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate());
        if (periodo === 'ayer') inicio.setDate(inicio.getDate() - 1);
        if (periodo === '7dias') inicio.setDate(inicio.getDate() - 6);
        desde.value = periodo === 'todo' ? '' : fechaLocal(inicio);
        hasta.value = periodo === 'todo' ? '' : fechaLocal(periodo === 'ayer' ? inicio : hoy);
        form.querySelector('[data-mc-periodo]').value = periodo;
        form.querySelectorAll('[data-mc-periodo-btn]').forEach((b) => {
            const activo = b === button;
            b.classList.toggle('is-active', activo);
            b.setAttribute('aria-pressed', String(activo));
        });
        // Aplicar inmediatamente el periodo seleccionado usando el GET existente.
        // requestSubmit también dispara los listeners de envío del sistema.
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    });
})();
</script>

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
