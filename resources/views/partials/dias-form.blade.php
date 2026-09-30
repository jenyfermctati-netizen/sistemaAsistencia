@php
    $diasSemana = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo',
    ];
@endphp

<div class="dias-semana">
    <div class="dia-row dia-row--header">
        <div>Día</div>
        <div>Laborable</div>
        <div>Entrada</div>
        <div>Salida</div>
        <div>Tolerancia</div>
    </div>

    @foreach($diasSemana as $numero => $nombre)
        @php
            $laborableDefault = $numero <= 6;
            $entradaDefault = $numero <= 6 ? '08:00' : '';
            $salidaDefault = $numero <= 5 ? '17:00' : ($numero === 6 ? '13:00' : '');
        @endphp

        <div class="dia-row" data-dia-row>
            <div class="dia-row__nombre">
                <strong>{{ $nombre }}</strong>
            </div>

            <div>
                <input type="hidden" name="dias[{{ $numero }}][es_laborable]" value="0">
                <label class="switch-control">
                    <input
                        type="checkbox"
                        id="{{ $prefix }}DiaLaborable{{ $numero }}"
                        name="dias[{{ $numero }}][es_laborable]"
                        value="1"
                        data-dia-laborable
                        @checked($laborableDefault)
                    >
                    <span>Sí</span>
                </label>
            </div>

            <div>
                <input
                    type="time"
                    name="dias[{{ $numero }}][hora_entrada]"
                    value="{{ $entradaDefault }}"
                    class="form-control"
                    data-dia-entrada
                >
            </div>

            <div>
                <input
                    type="time"
                    name="dias[{{ $numero }}][hora_salida]"
                    value="{{ $salidaDefault }}"
                    class="form-control"
                    data-dia-salida
                >
            </div>

            <div class="tolerancia-input">
                <input
                    type="number"
                    name="dias[{{ $numero }}][tolerancia_minutos]"
                    value="0"
                    min="0"
                    max="180"
                    class="form-control"
                    data-dia-tolerancia
                >
                <span>min</span>
            </div>
        </div>
    @endforeach
</div>