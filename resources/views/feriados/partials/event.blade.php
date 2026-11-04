@php
    $tipo = strtolower($feriado->tipo);
    $aplicacion = $feriado->area?->nombre ?? 'Todas las áreas';
    $accionEstado = $feriado->estado ? 'Desactivar' : 'Activar';
@endphp

<div class="holiday-event holiday-event--{{ $tipo }} {{ $feriado->estado ? '' : 'is-inactive' }}">
    <button type="button" class="holiday-event__main"
        aria-label="Editar {{ $feriado->nombre }}. {{ ucfirst($tipo) }}, {{ $aplicacion }}"
        title="Editar: {{ $feriado->nombre }}" data-editar-feriado
        data-url="{{ route('feriados.update', $feriado) }}"
        data-fecha="{{ $feriado->fecha?->format('Y-m-d') }}"
        data-nombre="{{ $feriado->nombre }}" data-tipo="{{ $feriado->tipo }}"
        data-area="{{ $feriado->area_id ?? '' }}"
        data-descripcion="{{ $feriado->descripcion }}">
        <span class="holiday-event__copy">
            <strong class="holiday-event__name">{{ $feriado->nombre }}</strong>
            <span class="holiday-event__meta">
                {{ ucfirst($tipo) }} · {{ $aplicacion }}
            </span>
        </span>
        <x-ui-icon name="edit" />
    </button>

    <button type="button" class="holiday-event__state"
        aria-label="{{ $accionEstado }} feriado {{ $feriado->nombre }}"
        data-tooltip="{{ $accionEstado }}" data-cambiar-estado-feriado
        data-url="{{ route('feriados.estado', $feriado) }}"
        data-nombre="{{ $feriado->nombre }}"
        data-activo="{{ $feriado->estado ? '1' : '0' }}">
        <x-ui-icon name="power" />
    </button>
</div>
