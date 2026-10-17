<?php

namespace App\Observers;

use App\Models\Marcacion;
use App\Models\Trabajador;
use App\Services\AsistenciaService;
use Carbon\Carbon;

class MarcacionObserver
{
    public function __construct(private readonly AsistenciaService $asistenciaService)
    {
    }

    public function created(Marcacion $marcacion): void
    {
        $this->recalcular(
            (int) $marcacion->trabajador_id,
            Carbon::parse($marcacion->fecha_hora)
        );
    }

    public function updated(Marcacion $marcacion): void
    {
        if (!$marcacion->wasChanged(['trabajador_id', 'fecha_hora', 'tipo', 'anulada'])) {
            return;
        }

        $this->recalcular(
            (int) $marcacion->trabajador_id,
            Carbon::parse($marcacion->fecha_hora)
        );

        $trabajadorAnterior = (int) $marcacion->getOriginal('trabajador_id');
        $fechaAnterior = Carbon::parse($marcacion->getOriginal('fecha_hora'));

        if (
            $trabajadorAnterior !== (int) $marcacion->trabajador_id
            || !$fechaAnterior->isSameDay(Carbon::parse($marcacion->fecha_hora))
        ) {
            $this->recalcular($trabajadorAnterior, $fechaAnterior);
        }
    }

    private function recalcular(int $trabajadorId, Carbon $fecha): void
    {
        $trabajador = Trabajador::whereKey($trabajadorId)
            ->where('estado', 'ACTIVO')
            ->first();

        if ($trabajador) {
            $this->asistenciaService->procesarTrabajador($trabajador, $fecha);
        }
    }
}
