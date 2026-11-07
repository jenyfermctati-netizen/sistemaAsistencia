<section id="campoAvances" data-campo-panel="avances" hidden class="worker-log" aria-labelledby="campoSinAprobarTitulo">
    @include('trabajadores.partials.historial-campo', ['sinAprobar' => true, 'diasMarcaciones' => $diasSinAprobar, 'marcacionesPorFecha' => $marcacionesSinAprobar])
</section>
