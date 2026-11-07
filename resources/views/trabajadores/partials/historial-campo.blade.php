@php($sinAprobar = $sinAprobar ?? false)
<section class="worker-log" @unless($sinAprobar) id="campoHistorial" data-campo-panel="historial" hidden @endunless aria-labelledby="{{ $sinAprobar ? 'campoSinAprobarTitulo' : 'historialCampoTitulo' }}">
    @unless($sinAprobar)
    <div class="worker-log__stats" aria-label="Resumen de todo tu historial">
        <div><span>Marcaciones registradas</span><strong>{{ $resumenCampo['total'] }}</strong></div>
        <div><span>Evidencias enviadas</span><strong>{{ $resumenCampo['evidencias'] }}</strong></div>
        <div><span>Pendientes de revisión</span><strong>{{ $resumenCampo['pendientes'] }}</strong></div>
    </div>
    @endunless
    <header class="worker-log__heading">
        <div><span class="campo-kicker">{{ $sinAprobar ? 'POR REVISAR' : 'HISTORIAL APROBADO' }}</span><h3 id="{{ $sinAprobar ? 'campoSinAprobarTitulo' : 'historialCampoTitulo' }}">{{ $sinAprobar ? 'Marcaciones pendientes' : 'Marcaciones aprobadas' }}</h3><p>{{ $sinAprobar ? 'Pendientes y rechazadas: consulta el estado y comentario de cada envío.' : 'Solo marcaciones aprobadas. Selecciona una fecha para ver los detalles.' }}</p></div>
        <span class="worker-log__total">{{ $diasMarcaciones->total() }} {{ $diasMarcaciones->total() === 1 ? 'día registrado' : 'días registrados' }}</span>
    </header>

    @forelse($marcacionesPorFecha as $fecha => $marcas)
        <details class="worker-day worker-day--expandable" name="{{ $sinAprobar ? 'sin-aprobar-por-dia' : 'marcaciones-por-dia' }}">
            <summary class="worker-day__header">
                <div><span>{{ \Carbon\Carbon::parse($fecha)->locale('es')->translatedFormat('l') }}</span><h4>{{ \Carbon\Carbon::parse($fecha)->locale('es')->translatedFormat('d M Y') }}</h4></div>
                <div class="worker-day__counts"><span>{{ $marcas->count() }} {{ $marcas->count() === 1 ? 'marcación' : 'marcaciones' }}</span></div>
            </summary>
            <ol class="worker-day__list">
                @foreach($marcas->sortBy('fecha_hora') as $marca)
                    <li class="worker-mark" data-worker-mark="{{ $marca->id }}">
                        <div class="worker-mark__time"><time datetime="{{ $marca->fecha_hora->toIso8601String() }}">{{ $marca->fecha_hora->format('H:i') }}</time><span>{{ ucfirst(strtolower($marca->tipo)) }}</span></div>
                        <div class="worker-mark__body">
                            <div class="worker-mark__title"><strong>{{ $marca->asignacion?->actividad ?: 'Actividad sin descripción' }}</strong><span class="campo-mini-mark campo-mini-mark--{{ strtolower($marca->estado) }}">{{ ucfirst(strtolower($marca->estado)) }}</span></div>
                            <p>{{ $marca->asignacion?->lugar ?: 'Lugar no especificado' }}</p>
                            @if($marca->observacion_revision)
                                <p class="worker-mark__review"><strong>Comentario de revisión:</strong> {{ $marca->observacion_revision }}</p>
                            @endif
                            @if($marca->marcacion?->anulada)
                                <p class="worker-mark__review">La marcación asociada fue anulada.</p>
                            @endif
                        </div>
                        @if($marca->evidencia_path)
                            <a class="worker-mark__photo" href="{{ route('marcaciones-campo.evidencia', $marca) }}" data-campo-photo aria-label="Ver evidencia de {{ strtolower($marca->tipo) }} del {{ $marca->fecha_hora->format('d/m/Y H:i') }}">
                                <img src="{{ route('marcaciones-campo.evidencia', $marca) }}" alt="Foto enviada a las {{ $marca->fecha_hora->format('H:i') }}" loading="lazy" width="80" height="64"><span>Ver evidencia</span>
                            </a>
                        @else
                            <span class="worker-mark__no-photo">Sin evidencia</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </details>
    @empty
        <div class="worker-log__empty"><x-ui-icon name="calendar" /><h4>{{ $sinAprobar ? 'No tienes marcaciones sin aprobar' : 'Aún no tienes marcaciones aprobadas' }}</h4><p>{{ $sinAprobar ? 'Los nuevos envíos aparecerán aquí mientras se revisan.' : 'Cuando el administrador apruebe una marcación, aparecerá aquí.' }}</p></div>
    @endforelse

    @if($diasMarcaciones->hasPages())
        <div>{{ $diasMarcaciones->appends(['vista' => $sinAprobar ? 'avances' : 'historial'])->links('components.pagination') }}</div>
    @endif
</section>
