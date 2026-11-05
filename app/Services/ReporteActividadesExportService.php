<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

class ReporteActividadesExportService
{
    private const COLUMNAS = ['Fecha', 'Área', 'Trabajador', 'Reporte', 'Estado', 'Actividad'];

    public function descargar(string $formato, Builder $reportes, ?string $desde, ?string $hasta)
    {
        return app(ReporteTablaExportService::class)->descargar(
            $formato,
            'reporte-actividades',
            'Reporte de actividades',
            self::COLUMNAS,
            ['A' => 14, 'B' => 28, 'C' => 28, 'D' => 12, 'E' => 16, 'F' => 70],
            $this->filas($reportes),
            $desde,
            $hasta,
        );
    }

    private function filas(Builder $reportes): iterable
    {
        foreach ($reportes->withOnly(['trabajador.area', 'actividades'])->lazy() as $reporte) {
            foreach ($reporte->actividades as $actividad) {
                yield [
                    $reporte->fecha_presentacion->format('d/m/Y'),
                    $reporte->trabajador?->area?->nombre ?? '',
                    trim(($reporte->trabajador?->nombres ?? '').' '.($reporte->trabajador?->apellidos ?? '')),
                    $reporte->id,
                    $reporte->estado === 'PENDIENTE' ? 'Por revisar' : $reporte->estado,
                    $actividad->descripcion,
                ];
            }
        }
    }
}
