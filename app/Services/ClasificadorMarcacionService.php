<?php

namespace App\Services;

use App\Models\HorarioDia;
use App\Models\Marcacion;
use App\Models\Trabajador;
use App\Models\TrabajadorHorario;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ClasificadorMarcacionService
{
    public const ENTRADA_MANANA = 'ENTRADA_MANANA';

    public const SALIDA_REFRIGERIO = 'SALIDA_REFRIGERIO';

    public const RETORNO_REFRIGERIO = 'RETORNO_REFRIGERIO';

    public const SALIDA_FINAL = 'SALIDA_FINAL';

    /**
     * @return array<string, Marcacion>
     */
    public function clasificar(Collection $marcaciones, ?HorarioDia $diaHorario): array
    {
        $marcas = $marcaciones
            ->filter(fn (Marcacion $marcacion) => ! $marcacion->anulada)
            ->sortBy('fecha_hora')
            ->values();

        if ($marcas->isEmpty()) {
            return [];
        }

        if (! $this->esHorarioPartido($diaHorario)) {
            return array_filter([
                self::ENTRADA_MANANA => $marcas->first(),
                self::SALIDA_FINAL => $marcas->count() >= 2
                    ? $marcas->last()
                    : null,
            ]);
        }

        $marcas = $marcas->take(4)->values();
        $fecha = Carbon::parse($marcas->first()->fecha_hora)->toDateString();
        $momentos = [
            self::ENTRADA_MANANA => Carbon::parse($fecha.' '.$diaHorario->hora_entrada),
            self::SALIDA_REFRIGERIO => Carbon::parse($fecha.' '.$diaHorario->hora_salida_refrigerio),
            self::RETORNO_REFRIGERIO => Carbon::parse($fecha.' '.$diaHorario->hora_retorno_refrigerio),
            self::SALIDA_FINAL => Carbon::parse($fecha.' '.$diaHorario->hora_salida),
        ];
        $nombresMomentos = array_keys($momentos);
        $horasMomentos = array_values($momentos);
        $mejorSeleccion = null;
        $mejorCosto = PHP_INT_MAX;

        foreach ($this->combinaciones(count($marcas)) as $seleccion) {
            $costo = 0;

            foreach ($seleccion as $indiceMarca => $indiceMomento) {
                $costo += abs(
                    Carbon::parse($marcas[$indiceMarca]->fecha_hora)
                        ->diffInSeconds($horasMomentos[$indiceMomento], false)
                );
            }

            if ($costo < $mejorCosto) {
                $mejorCosto = $costo;
                $mejorSeleccion = $seleccion;
            }
        }

        $resultado = [];

        foreach ($mejorSeleccion ?? [] as $indiceMarca => $indiceMomento) {
            $resultado[$nombresMomentos[$indiceMomento]] = $marcas[$indiceMarca];
        }

        return $resultado;
    }

    /**
     * @return array{tipo: string, momento: string, etiqueta: string, total_esperado: int}|null
     */
    public function clasificarNuevaMarcacion(Trabajador $trabajador, Carbon $fechaHora): ?array
    {
        $diaHorario = $this->obtenerDiaHorario($trabajador, $fechaHora);
        $marcaciones = Marcacion::where('trabajador_id', $trabajador->id)
            ->whereDate('fecha_hora', $fechaHora->toDateString())
            ->where('anulada', false)
            ->orderBy('fecha_hora')
            ->get();
        $totalEsperado = $this->esHorarioPartido($diaHorario) ? 4 : 2;

        if ($marcaciones->count() >= $totalEsperado) {
            return null;
        }

        $nuevaMarcacion = new Marcacion([
            'trabajador_id' => $trabajador->id,
            'fecha_hora' => $fechaHora,
            'anulada' => false,
        ]);

        if (! $this->esHorarioPartido($diaHorario)) {
            $esEntrada = $marcaciones->count() % 2 === 0;

            return [
                'tipo' => $esEntrada ? 'ENTRADA' : 'SALIDA',
                'momento' => $esEntrada ? self::ENTRADA_MANANA : self::SALIDA_FINAL,
                'etiqueta' => $esEntrada ? 'entrada' : 'salida',
                'total_esperado' => 2,
            ];
        }

        $clasificacion = $this->clasificar(
            $marcaciones->push($nuevaMarcacion),
            $diaHorario
        );
        $momento = collect($clasificacion)
            ->search(fn (Marcacion $marcacion) => $marcacion === $nuevaMarcacion);

        if (! is_string($momento)) {
            return null;
        }

        return [
            'tipo' => in_array($momento, [self::ENTRADA_MANANA, self::RETORNO_REFRIGERIO], true)
                ? 'ENTRADA'
                : 'SALIDA',
            'momento' => $momento,
            'etiqueta' => $this->etiqueta($momento),
            'total_esperado' => 4,
        ];
    }

    public function esHorarioPartido(?HorarioDia $diaHorario): bool
    {
        return filled($diaHorario?->hora_salida_refrigerio)
            && filled($diaHorario?->hora_retorno_refrigerio);
    }

    private function obtenerDiaHorario(Trabajador $trabajador, Carbon $fechaHora): ?HorarioDia
    {
        $fecha = $fechaHora->toDateString();
        $asignacion = TrabajadorHorario::with('horario.dias')
            ->where('trabajador_id', $trabajador->id)
            ->where('estado', true)
            ->whereDate('fecha_inicio', '<=', $fecha)
            ->where(function ($query) use ($fecha) {
                $query->whereNull('fecha_fin')
                    ->orWhereDate('fecha_fin', '>=', $fecha);
            })
            ->orderByDesc('fecha_inicio')
            ->first();

        return $asignacion?->horario?->dias?->firstWhere(
            'dia_semana',
            $fechaHora->isoWeekday()
        );
    }

    /**
     * @return array<int, array<int, int>>
     */
    private function combinaciones(int $cantidad): array
    {
        $resultado = [];

        for ($mascara = 1; $mascara < 16; $mascara++) {
            $seleccion = [];

            for ($indice = 0; $indice < 4; $indice++) {
                if (($mascara & (1 << $indice)) !== 0) {
                    $seleccion[] = $indice;
                }
            }

            if (count($seleccion) === $cantidad) {
                $resultado[] = $seleccion;
            }
        }

        return $resultado;
    }

    private function etiqueta(string $momento): string
    {
        return match ($momento) {
            self::ENTRADA_MANANA => 'entrada de la mañana',
            self::SALIDA_REFRIGERIO => 'salida a refrigerio',
            self::RETORNO_REFRIGERIO => 'retorno / entrada de la tarde',
            self::SALIDA_FINAL => 'salida final',
        };
    }
}
