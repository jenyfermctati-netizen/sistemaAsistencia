@extends('layouts.app')

@section('title', 'Feriados')
@section('page-title', 'Feriados')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/feriados.css') }}">
@endpush

@section('content')
    @php
        $filtrosNavegacion = request()->only(['buscar', 'tipo', 'area_id', 'estado']);
        $urlMesAnterior = route('feriados.index', array_merge($filtrosNavegacion, [
            'anio' => $mesAnterior->year,
            'mes' => $mesAnterior->month,
        ]));
        $urlMesSiguiente = route('feriados.index', array_merge($filtrosNavegacion, [
            'anio' => $mesSiguiente->year,
            'mes' => $mesSiguiente->month,
        ]));
        $urlHoy = route('feriados.index', array_merge($filtrosNavegacion, [
            'anio' => now()->year,
            'mes' => now()->month,
        ]));
        $cantidadFiltros = collect($filtrosNavegacion)->filter(fn ($valor) => filled($valor))->count();
    @endphp

    <div class="page-header feriados-hero">
        <div class="feriados-hero__copy">
            <span class="feriados-hero__eyebrow">CALENDARIO LABORAL</span>
            <h2 class="page-header__title">Gestión de feriados</h2>
            <p class="page-header__description">
                Visualiza y administra los días que modifican la jornada de cada equipo.
            </p>
        </div>

        <div class="page-header__actions feriados-hero__actions">
            <form method="POST" action="{{ route('feriados.sincronizar') }}" class="sincronizar-feriados">
                @csrf
                <input type="hidden" name="mes" value="{{ $mes }}">
                <div class="sincronizar-feriados__campo">
                    <label for="anioSincronizacion">Año nacional</label>
                    <input type="number" id="anioSincronizacion" name="anio" value="{{ $anio }}"
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

    <section class="holiday-controls" aria-label="Navegación y filtros del calendario">
        <div class="holiday-period">
            <div class="holiday-period__identity">
                <span class="holiday-period__icon"><x-ui-icon name="calendar" /></span>
                <div>
                    <span class="holiday-period__eyebrow">PERIODO VISIBLE</span>
                    <h3>{{ ucfirst($periodo->locale('es')->translatedFormat('F Y')) }}</h3>
                </div>
            </div>

            <nav class="holiday-period__navigation" aria-label="Cambiar mes">
                <a href="{{ $urlMesAnterior }}" class="holiday-nav-button" aria-label="Ver mes anterior"
                    data-tooltip="Mes anterior">
                    <x-ui-icon name="arrow-left" />
                </a>
                <a href="{{ $urlHoy }}" class="holiday-today-button">Hoy</a>
                <a href="{{ $urlMesSiguiente }}" class="holiday-nav-button" aria-label="Ver mes siguiente"
                    data-tooltip="Mes siguiente">
                    <x-ui-icon name="chevron-right" />
                </a>
            </nav>
        </div>

        <form method="GET" action="{{ route('feriados.index') }}" class="feriados-filtros"
            data-ux-has-active-filters="{{ $cantidadFiltros > 0 ? 'true' : 'false' }}">
            <label class="filter-field">
                <span class="filter-field__label">Mes</span>
                <select name="mes" class="form-select">
                    @foreach ($meses as $numero => $nombreMes)
                        <option value="{{ $numero }}" @selected($mes === $numero)>{{ ucfirst($nombreMes) }}</option>
                    @endforeach
                </select>
            </label>

            <label class="filter-field">
                <span class="filter-field__label">Año</span>
                <input type="number" name="anio" value="{{ $anio }}" class="form-control" min="2020"
                    max="2100" inputmode="numeric">
            </label>

            <label class="filter-field holiday-filter-search">
                <span class="filter-field__label">Buscar</span>
                <input type="search" name="buscar" value="{{ request('buscar') }}" class="form-control"
                    placeholder="Nombre o descripción">
            </label>

            <label class="filter-field">
                <span class="filter-field__label">Tipo</span>
                <select name="tipo" class="form-select">
                    <option value="">Todos</option>
                    <option value="NACIONAL" @selected(request('tipo') === 'NACIONAL')>Nacional</option>
                    <option value="REGIONAL" @selected(request('tipo') === 'REGIONAL')>Regional</option>
                    <option value="LOCAL" @selected(request('tipo') === 'LOCAL')>Local</option>
                    <option value="INSTITUCIONAL" @selected(request('tipo') === 'INSTITUCIONAL')>Institucional</option>
                </select>
            </label>

            <label class="filter-field holiday-filter-area">
                <span class="filter-field__label">Aplicación</span>
                <select name="area_id" class="form-select">
                    <option value="">Todas las áreas</option>
                    <option value="GENERAL" @selected(request('area_id') === 'GENERAL')>General / toda la empresa</option>
                    @foreach ($areas as $area)
                        <option value="{{ $area->id }}" @selected(request('area_id') == $area->id)>{{ $area->nombre }}</option>
                    @endforeach
                </select>
            </label>

            <label class="filter-field">
                <span class="filter-field__label">Estado</span>
                <select name="estado" class="form-select">
                    <option value="">Todos</option>
                    <option value="ACTIVO" @selected(request('estado') === 'ACTIVO')>Activos</option>
                    <option value="INACTIVO" @selected(request('estado') === 'INACTIVO')>Inactivos</option>
                </select>
            </label>

            <div class="holiday-filter-actions">
                <button type="submit" class="button button--primary"><x-ui-icon name="filter" /> Aplicar</button>
                <a href="{{ route('feriados.index', ['anio' => $anio, 'mes' => $mes]) }}"
                    class="button button--secondary" aria-label="Limpiar filtros" data-tooltip="Limpiar filtros">
                    <x-ui-icon name="reset" />
                    <span>Limpiar{{ $cantidadFiltros ? " ({$cantidadFiltros})" : '' }}</span>
                </a>
            </div>
        </form>
    </section>

    <section class="holiday-summary" aria-label="Resumen del mes">
        <article class="holiday-summary-card holiday-summary-card--total">
            <span class="holiday-summary-card__label">Feriados visibles</span>
            <strong>{{ $resumen['total'] }}</strong>
            <span>en {{ ucfirst($periodo->locale('es')->translatedFormat('F')) }}</span>
        </article>
        <article class="holiday-summary-card holiday-summary-card--active">
            <span class="holiday-summary-card__label">Activos</span>
            <strong>{{ $resumen['activos'] }}</strong>
            <span>afectan el cálculo</span>
        </article>
        <article class="holiday-summary-card holiday-summary-card--national">
            <span class="holiday-summary-card__label">Nacionales</span>
            <strong>{{ $resumen['nacionales'] }}</strong>
            <span>calendario del país</span>
        </article>
        <article class="holiday-summary-card holiday-summary-card--general">
            <span class="holiday-summary-card__label">Toda la empresa</span>
            <strong>{{ $resumen['generales'] }}</strong>
            <span>sin área específica</span>
        </article>
    </section>

    <section class="holiday-calendar-card" aria-labelledby="holidayCalendarTitle">
        <header class="holiday-calendar-card__header">
            <div>
                <span class="holiday-calendar-card__eyebrow">VISTA MENSUAL</span>
                <h3 id="holidayCalendarTitle">{{ ucfirst($periodo->locale('es')->translatedFormat('F Y')) }}</h3>
                <p>Selecciona un feriado para editarlo o usa <strong>+</strong> para registrar uno en ese día.</p>
            </div>

            <div class="holiday-legend" aria-label="Leyenda de tipos">
                <span><i class="holiday-legend__dot holiday-legend__dot--nacional"></i>Nacional</span>
                <span><i class="holiday-legend__dot holiday-legend__dot--regional"></i>Regional</span>
                <span><i class="holiday-legend__dot holiday-legend__dot--local"></i>Local</span>
                <span><i class="holiday-legend__dot holiday-legend__dot--institucional"></i>Institucional</span>
            </div>
        </header>

        @if ($feriados->isEmpty())
            <div class="holiday-calendar-empty" role="status">
                <span class="holiday-calendar-empty__icon"><x-ui-icon name="calendar" /></span>
                <span>
                    <strong>No hay feriados que coincidan con este periodo y filtros.</strong>
                    Puedes elegir otro mes, limpiar los filtros o registrar un nuevo feriado.
                </span>
            </div>
        @endif

        <div class="holiday-calendar-desktop">
            <div class="holiday-weekdays" role="row">
                @foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $diaSemana)
                    <span role="columnheader">{{ $diaSemana }}</span>
                @endforeach
            </div>

            <div class="holiday-calendar-grid" role="grid" aria-label="Calendario de {{ $periodo->locale('es')->translatedFormat('F Y') }}">
                @foreach ($calendario as $dia)
                    @php
                        $fechaDia = \Carbon\Carbon::parse($dia['date'])->locale('es');
                        $etiquetaDia = ucfirst($fechaDia->translatedFormat('l d \d\e F \d\e Y'));
                    @endphp
                    <article
                        class="holiday-calendar-day {{ $dia['is_current_month'] ? '' : 'is-outside' }} {{ $dia['is_today'] ? 'is-today' : '' }} {{ $dia['holidays']->isNotEmpty() ? 'has-events' : '' }}"
                        role="gridcell" aria-label="{{ $etiquetaDia }}, {{ $dia['holidays']->count() }} feriados">
                        <header class="holiday-calendar-day__header">
                            <span class="holiday-calendar-day__number">
                                {{ $dia['day'] }}
                                @if (!$dia['is_current_month'])
                                    <small>{{ $dia['month_short'] }}</small>
                                @endif
                            </span>
                            @if ($dia['is_today'])
                                <span class="holiday-calendar-day__today">Hoy</span>
                            @endif
                            @if ($dia['is_current_month'])
                                <button type="button" class="holiday-calendar-day__add"
                                    aria-label="Agregar feriado el {{ $etiquetaDia }}" data-tooltip="Agregar feriado"
                                    data-crear-feriado-fecha="{{ $dia['date'] }}">
                                    <x-ui-icon name="plus" />
                                </button>
                            @endif
                        </header>

                        <div class="holiday-calendar-day__events">
                            @foreach ($dia['holidays'] as $feriado)
                                @include('feriados.partials.event', ['feriado' => $feriado])
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="holiday-agenda" aria-labelledby="holidayAgendaTitle">
        <header class="holiday-agenda__header">
            <span class="holiday-calendar-card__eyebrow">AGENDA DEL MES</span>
            <h3 id="holidayAgendaTitle">{{ ucfirst($periodo->locale('es')->translatedFormat('F Y')) }}</h3>
            <p>Feriados ordenados por fecha para una lectura más cómoda en pantallas pequeñas.</p>
        </header>

        <div class="holiday-agenda__days">
            @forelse ($feriados->groupBy(fn ($feriado) => $feriado->fecha->toDateString()) as $fecha => $feriadosDia)
                @php $fechaAgenda = $feriadosDia->first()->fecha->locale('es'); @endphp
                <article class="holiday-agenda-day">
                    <div class="holiday-agenda-day__date">
                        <strong>{{ $fechaAgenda->format('d') }}</strong>
                        <span>{{ strtoupper($fechaAgenda->translatedFormat('M')) }}</span>
                        <small>{{ ucfirst($fechaAgenda->translatedFormat('D')) }}</small>
                    </div>
                    <div class="holiday-agenda-day__content">
                        @foreach ($feriadosDia as $feriado)
                            @include('feriados.partials.event', ['feriado' => $feriado])
                        @endforeach
                    </div>
                </article>
            @empty
                <div class="holiday-agenda-empty">
                    <span class="holiday-agenda-empty__icon"><x-ui-icon name="calendar" /></span>
                    <strong>Este mes aún no tiene feriados visibles</strong>
                    <span>Registra uno con el botón “Nuevo feriado”.</span>
                </div>
            @endforelse
        </div>
    </section>

    {{-- MODAL CREAR --}}
    <x-modal id="modalCrearFeriado" title="Nuevo feriado o día no laborable">
        <form method="POST" action="{{ route('feriados.store') }}" class="form" data-form-crear-feriado
            data-reopen-on-error="{{ $errors->any() && old('_form') === 'crear' ? '1' : '0' }}">
            @csrf
            <input type="hidden" name="_form" value="crear">
            <input type="hidden" name="return_anio" value="{{ $anio }}">
            <input type="hidden" name="return_mes" value="{{ $mes }}">

            <div class="form-grid form-grid--2">
                <div class="form-group">
                    <label for="crearFeriadoFecha" class="form-label">Fecha *</label>
                    <input type="date" id="crearFeriadoFecha" name="fecha"
                        value="{{ old('fecha', $fechaSugerida) }}" class="form-control" required>
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
            <input type="hidden" name="return_anio" value="{{ $anio }}">
            <input type="hidden" name="return_mes" value="{{ $mes }}">

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
            <input type="hidden" name="return_anio" value="{{ $anio }}">
            <input type="hidden" name="return_mes" value="{{ $mes }}">

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
