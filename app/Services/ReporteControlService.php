<?php

namespace App\Services;

use App\Models\Area;
use App\Models\PendienteAvance;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReporteControlService
{
    public function generar(
        User $usuario,
        CarbonInterface $desde,
        CarbonInterface $hasta,
        ?int $areaSolicitada = null
    ): array {
        $alcance = $this->alcance($usuario);
        $areaId = $alcance['esVistaGeneral'] ? $areaSolicitada : $alcance['areaId'];

        $areas = Area::query()
            ->where('estado', true)
            ->when($areaId, fn (Builder $query) => $query->whereKey($areaId))
            ->with(['trabajadores' => function ($query) use ($desde, $hasta) {
                $query
                    ->where('estado', 'ACTIVO')
                    ->with([
                        'asistencias' => fn ($asistencias) => $asistencias
                            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
                            ->with([
                                'marcacionEntrada',
                                'marcacionSalidaRefrigerio',
                                'marcacionRetornoRefrigerio',
                                'marcacionSalida',
                            ])
                            ->orderBy('fecha'),
                        'reportesAvance' => fn ($reportes) => $reportes
                            ->whereBetween('fecha_presentacion', [$desde->toDateString(), $hasta->toDateString()])
                            ->with(['actividades', 'pendientes'])
                            ->orderBy('fecha_presentacion'),
                    ])
                    ->orderBy('apellidos')
                    ->orderBy('nombres');
            }])
            ->orderBy('nombre')
            ->get();

        $pendientes = PendienteAvance::query()
            ->with([
                'area',
                'reporte.trabajador.area',
                'creadoPor.trabajador',
                'resueltoPor',
                'revisadoPor',
            ])
            ->where(function (Builder $query) use ($desde, $hasta) {
                $query
                    ->whereBetween('created_at', [$desde->copy()->startOfDay(), $hasta->copy()->endOfDay()])
                    ->orWhereBetween('fecha_resolucion', [$desde->copy()->startOfDay(), $hasta->copy()->endOfDay()])
                    ->orWhereBetween('fecha_revision', [$desde->copy()->startOfDay(), $hasta->copy()->endOfDay()]);
            })
            ->when($areaId, function (Builder $query) use ($areaId) {
                $query->where(function (Builder $area) use ($areaId) {
                    $area
                        ->where('area_id', $areaId)
                        ->orWhere(function (Builder $legado) use ($areaId) {
                            $legado
                                ->whereNull('area_id')
                                ->whereHas('reporte.trabajador', fn (Builder $trabajador) => $trabajador->where('area_id', $areaId));
                        });
                });
            })
            ->orderBy('created_at')
            ->get();

        $areasReporte = $areas->map(function (Area $area) use ($pendientes) {
            $pendientesArea = $pendientes
                ->filter(fn (PendienteAvance $pendiente) => $this->areaPendienteId($pendiente) === $area->id)
                ->values();

            $trabajadores = $area->trabajadores->map(function ($trabajador) use ($pendientesArea) {
                $reportes = $trabajador->reportesAvance;
                $actividades = $reportes
                    ->flatMap(fn ($reporte) => $reporte->actividades->map(function ($actividad) use ($reporte) {
                        $actividad->setRelation('reporte', $reporte);

                        return $actividad;
                    }))
                    ->values();
                $pendientesTrabajador = $pendientesArea
                    ->filter(fn (PendienteAvance $pendiente) => $pendiente->reporte?->trabajador_id === $trabajador->id
                        || $pendiente->resuelto_por === $trabajador->id)
                    ->unique('id')
                    ->values();

                return [
                    'trabajador' => $trabajador,
                    'asistencias' => $trabajador->asistencias,
                    'reportes' => $reportes,
                    'actividades' => $actividades,
                    'pendientes' => $pendientesTrabajador,
                    'metricas' => $this->metricasTrabajador(
                        $trabajador->asistencias,
                        $reportes,
                        $actividades,
                        $pendientesTrabajador
                    ),
                ];
            })->values();

            return [
                'area' => $area,
                'trabajadores' => $trabajadores,
                'pendientes' => $pendientesArea,
                'metricas' => $this->metricasArea($trabajadores, $pendientesArea),
            ];
        })->values();

        $alcanceLabel = $alcance['esVistaGeneral'] && $areaId
            ? ($areas->first()?->nombre ?? 'Área sin información activa')
            : $alcance['alcanceLabel'];

        return array_merge($alcance, [
            'desde' => $desde->copy()->startOfDay(),
            'hasta' => $hasta->copy()->startOfDay(),
            'areasDisponibles' => $alcance['esVistaGeneral']
                ? Area::where('estado', true)->orderBy('nombre')->get()
                : collect(),
            'areaSeleccionada' => $areaId,
            'alcanceLabel' => $alcanceLabel,
            'areasReporte' => $areasReporte,
            'metricas' => $this->metricasGlobales($areasReporte),
        ]);
    }

    public function alcance(User $usuario): array
    {
        $usuario->loadMissing(['rol', 'trabajador.area']);
        $rol = strtoupper(trim((string) $usuario->rol?->nombre));

        if (in_array($rol, ['ADMINISTRADOR', 'GERENTE'], true)) {
            return [
                'rol' => $rol,
                'esAdministrador' => $rol === 'ADMINISTRADOR',
                'esVistaGeneral' => true,
                'esResumido' => false,
                'areaId' => null,
                'alcanceLabel' => 'Todas las áreas',
            ];
        }

        $esEncargado = $rol === 'TRABAJADOR'
            && (bool) $usuario->trabajador?->esEncargadoDeSuArea();
        $area = $usuario->trabajador?->area;

        abort_unless($esEncargado && $area?->estado, 403);

        return [
            'rol' => $rol,
            'esAdministrador' => false,
            'esVistaGeneral' => false,
            'esResumido' => true,
            'areaId' => $area->id,
            'alcanceLabel' => $area->nombre,
        ];
    }

    private function metricasTrabajador(
        Collection $asistencias,
        Collection $reportes,
        Collection $actividades,
        Collection $pendientes
    ): array {
        return [
            'dias_registrados' => $asistencias->count(),
            'dias_completos' => $asistencias->filter(function ($asistencia) {
                $marcasPrincipales = filled($asistencia->hora_entrada)
                    && filled($asistencia->hora_salida);
                $requiereRefrigerio = filled($asistencia->hora_programada_salida_refrigerio)
                    || filled($asistencia->hora_programada_retorno_refrigerio);

                return $marcasPrincipales && (! $requiereRefrigerio
                    || (filled($asistencia->hora_salida_refrigerio)
                        && filled($asistencia->hora_retorno_refrigerio)));
            })->count(),
            'tardanzas' => $asistencias->where('estado', 'TARDANZA')->count(),
            'faltas' => $asistencias->whereIn('estado', ['FALTA', 'SIN_REGISTRO'])->count(),
            'minutos_trabajados' => (int) $asistencias->sum('minutos_trabajados'),
            'reportes' => $reportes->count(),
            'actividades' => $actividades->count(),
            'pendientes_abiertos' => $pendientes
                ->filter(fn ($pendiente) => $pendiente->estado !== 'RESUELTO')
                ->count(),
            'finalizadas' => $pendientes->whereNotNull('fecha_resolucion')->count(),
            'aprobadas' => $pendientes
                ->filter(fn ($pendiente) => $pendiente->estado === 'RESUELTO'
                    && $pendiente->estado_revision === 'APROBADO')
                ->count(),
        ];
    }

    private function metricasArea(Collection $trabajadores, Collection $pendientes): array
    {
        return [
            'trabajadores' => $trabajadores->count(),
            'dias_registrados' => $trabajadores->sum('metricas.dias_registrados'),
            'tardanzas' => $trabajadores->sum('metricas.tardanzas'),
            'faltas' => $trabajadores->sum('metricas.faltas'),
            'minutos_trabajados' => $trabajadores->sum('metricas.minutos_trabajados'),
            'reportes' => $trabajadores->sum('metricas.reportes'),
            'actividades' => $trabajadores->sum('metricas.actividades'),
            'pendientes_abiertos' => $pendientes
                ->filter(fn ($pendiente) => $pendiente->estado !== 'RESUELTO')
                ->count(),
            'finalizadas' => $pendientes->whereNotNull('fecha_resolucion')->count(),
            'aprobadas' => $pendientes
                ->filter(fn ($pendiente) => $pendiente->estado === 'RESUELTO'
                    && $pendiente->estado_revision === 'APROBADO')
                ->count(),
        ];
    }

    private function metricasGlobales(Collection $areas): array
    {
        $campos = [
            'trabajadores',
            'dias_registrados',
            'tardanzas',
            'faltas',
            'minutos_trabajados',
            'reportes',
            'actividades',
            'pendientes_abiertos',
            'finalizadas',
            'aprobadas',
        ];

        return collect($campos)
            ->mapWithKeys(fn (string $campo) => [$campo => (int) $areas->sum("metricas.{$campo}")])
            ->all();
    }

    private function areaPendienteId(PendienteAvance $pendiente): ?int
    {
        return $pendiente->area_id ?? $pendiente->reporte?->trabajador?->area_id;
    }
}
