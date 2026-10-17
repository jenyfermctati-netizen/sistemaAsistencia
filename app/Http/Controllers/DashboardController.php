<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Asistencia;
use App\Models\Marcacion;
use App\Models\PendienteAvance;
use App\Models\ReporteAvance;
use App\Models\Solicitud;
use App\Models\Trabajador;
use App\Models\Vacaciones;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        /** @var User $usuario */
        $usuario = Auth::user()->loadMissing(['rol', 'trabajador.area']);
        $rol = strtoupper(trim((string) $usuario->rol?->nombre));

        return match ($rol) {
            'ADMINISTRADOR' => view('dashboard.index', $this->datosAdministrador($usuario)),
            'GERENTE' => view('dashboard.index', $this->datosGerente($usuario)),
            'TRABAJADOR' => view('dashboard.index', $this->datosTrabajador($usuario)),
            default => abort(403, 'Tu cuenta no tiene un rol habilitado para acceder al inicio.'),
        };
    }

    private function datosAdministrador(User $usuario): array
    {
        $hoy = today();
        $registrosHoy = Asistencia::query()
            ->whereDate('fecha', $hoy)
            ->whereHas('trabajador', fn (Builder $query) =>
                $query->where('estado', 'ACTIVO'));

        return [
            'dashboardView' => 'dashboard.administrador',
            'pageTitle' => 'Panel administrativo',
            'pageSubtitle' => 'Vista general del sistema',
            'heroEyebrow' => 'CONTROL GENERAL',
            'heroTitle' => 'Hola, '.$usuario->name,
            'heroDescription' => 'Supervisa la operación, los equipos y las tareas que requieren atención.',
            'scopeLabel' => 'Todas las áreas',
            'metrics' => [
                'trabajadores' => Trabajador::where('estado', 'ACTIVO')->count(),
                'areas' => Area::where('estado', true)->count(),
                'asistencias' => (clone $registrosHoy)
                    ->whereIn('estado', ['PRESENTE', 'TARDANZA', 'CAMPO'])
                    ->count(),
                'puntuales' => (clone $registrosHoy)->where('estado', 'PRESENTE')->count(),
                'tardanzas' => (clone $registrosHoy)->where('estado', 'TARDANZA')->count(),
                'marcaciones' => Marcacion::query()
                    ->whereDate('fecha_hora', $hoy)
                    ->where('anulada', false)
                    ->whereHas('trabajador', fn (Builder $query) =>
                        $query->where('estado', 'ACTIVO'))
                    ->count(),
            ],
            'alerts' => [
                'solicitudes' => Solicitud::where('estado', 'PENDIENTE')->count(),
                'vacaciones' => Vacaciones::where('estado', 'PENDIENTE')->count(),
                'reportes' => ReporteAvance::where('estado', 'PENDIENTE')->count(),
                'pendientes' => PendienteAvance::where(function (Builder $query) {
                    $query->where('estado', 'PENDIENTE')
                        ->orWhere('estado_revision', 'POR_REVISAR');
                })->count(),
            ],
            'areasResumen' => $this->resumenAreas(),
            'asistenciasRecientes' => Asistencia::with([
                'trabajador.area',
                'marcacionEntrada',
                'marcacionSalida',
            ])
                ->whereDate('fecha', $hoy)
                ->whereHas('trabajador', fn (Builder $query) =>
                    $query->where('estado', 'ACTIVO'))
                ->latest('procesado_en')
                ->limit(6)
                ->get(),
        ];
    }

    private function datosGerente(User $usuario): array
    {
        $hoy = today();
        $area = $usuario->trabajador?->area;

        if (!$area?->estado) {
            $area = null;
        }

        $areaId = $area?->id;

        $trabajadores = Trabajador::query()->where('estado', 'ACTIVO');
        $registrosAsistencia = Asistencia::query()
            ->whereDate('fecha', $hoy)
            ->whereHas('trabajador', fn (Builder $query) =>
                $query->where('estado', 'ACTIVO'));
        $solicitudes = Solicitud::query()->where('estado', 'PENDIENTE');
        $reportes = ReporteAvance::query()->where('estado', 'PENDIENTE');

        if ($areaId) {
            $trabajadores->where('area_id', $areaId);
            $this->filtrarPorAreaTrabajador($registrosAsistencia, $areaId);
            $this->filtrarPorAreaTrabajador($solicitudes, $areaId);
            $solicitudes->where('trabajador_id', '!=', $usuario->trabajador_id);
            $this->filtrarPorAreaTrabajador($reportes, $areaId);
            $reportes->where('trabajador_id', '!=', $usuario->trabajador_id);
        } else {
            $this->sinResultados($trabajadores, $registrosAsistencia, $solicitudes, $reportes);
        }

        $pendientes = PendienteAvance::query()->where(function (Builder $query) {
            $query->where('estado', 'PENDIENTE')
                ->orWhere('estado_revision', 'POR_REVISAR');
        });

        if ($areaId) {
            $this->filtrarPendientesPorArea($pendientes, $areaId);
        } else {
            $pendientes->whereRaw('1 = 0');
        }

        $asistenciasRecientes = Asistencia::with([
            'trabajador.area',
            'marcacionEntrada',
            'marcacionSalida',
        ])
            ->whereDate('fecha', $hoy)
            ->whereHas('trabajador', fn (Builder $query) =>
                $query->where('estado', 'ACTIVO'));

        if ($areaId) {
            $this->filtrarPorAreaTrabajador($asistenciasRecientes, $areaId);
        } else {
            $asistenciasRecientes->whereRaw('1 = 0');
        }

        return [
            'dashboardView' => 'dashboard.gerente',
            'pageTitle' => 'Panel de gerencia',
            'pageSubtitle' => $area ? 'Seguimiento de '.$area->nombre : 'Área pendiente de asignación',
            'heroEyebrow' => 'SUPERVISIÓN OPERATIVA',
            'heroTitle' => 'Hola, '.$usuario->name,
            'heroDescription' => $area
                ? 'Revisa la asistencia, avances y solicitudes del equipo de '.$area->nombre.'.'
                : 'Tu cuenta necesita un trabajador y un área vinculados para habilitar la supervisión.',
            'scopeLabel' => $area?->nombre ?? 'Sin área vinculada',
            'areaVinculada' => $area,
            'metrics' => [
                'trabajadores' => (clone $trabajadores)->count(),
                'asistencias' => (clone $registrosAsistencia)
                    ->whereIn('estado', ['PRESENTE', 'TARDANZA', 'CAMPO'])
                    ->count(),
                'puntuales' => (clone $registrosAsistencia)->where('estado', 'PRESENTE')->count(),
                'incidencias' => (clone $registrosAsistencia)
                    ->whereIn('estado', ['TARDANZA', 'FALTA', 'SIN_REGISTRO'])
                    ->count(),
            ],
            'alerts' => [
                'solicitudes' => (clone $solicitudes)->count(),
                'reportes' => (clone $reportes)->count(),
                'pendientes' => $pendientes->count(),
            ],
            'areasResumen' => $areaId ? $this->resumenAreas($areaId) : collect(),
            'asistenciasRecientes' => $asistenciasRecientes
                ->latest('procesado_en')
                ->limit(7)
                ->get(),
        ];
    }

    private function datosTrabajador(User $usuario): array
    {
        $trabajador = $usuario->trabajador;
        $hoy = today();

        $datos = [
            'dashboardView' => 'dashboard.trabajador',
            'pageTitle' => 'Mi inicio',
            'pageSubtitle' => 'Resumen de tu jornada',
            'heroEyebrow' => 'ESPACIO PERSONAL',
            'heroTitle' => 'Hola, '.$usuario->name,
            'heroDescription' => 'Consulta tu jornada, solicitudes y avances desde un solo lugar.',
            'scopeLabel' => $trabajador?->area?->nombre ?? 'Sin área asignada',
            'trabajador' => $trabajador,
            'asistenciaHoy' => null,
            'metrics' => [
                'presentes' => 0,
                'tardanzas' => 0,
                'incidencias' => 0,
                'horas' => '0 h',
            ],
            'solicitudesPendientes' => 0,
            'vacacionActiva' => null,
            'reporteHoy' => null,
            'pendientesArea' => 0,
            'asistenciasRecientes' => collect(),
            'puedeSolicitarVacaciones' => false,
            'periodoLabel' => $hoy->locale('es')->translatedFormat('F Y'),
        ];

        if (!$trabajador) {
            return $datos;
        }

        $asistenciasMes = Asistencia::where('trabajador_id', $trabajador->id)
            ->whereYear('fecha', $hoy->year)
            ->whereMonth('fecha', $hoy->month);

        $minutosTrabajados = (clone $asistenciasMes)->sum('minutos_trabajados');
        $horas = intdiv((int) $minutosTrabajados, 60);
        $minutos = (int) $minutosTrabajados % 60;

        $datos['asistenciaHoy'] = Asistencia::where('trabajador_id', $trabajador->id)
            ->whereDate('fecha', $hoy)
            ->first();
        $datos['metrics'] = [
            'presentes' => (clone $asistenciasMes)->whereIn('estado', ['PRESENTE', 'TARDANZA', 'CAMPO'])->count(),
            'tardanzas' => (clone $asistenciasMes)->where('estado', 'TARDANZA')->count(),
            'incidencias' => (clone $asistenciasMes)->whereIn('estado', ['FALTA', 'SIN_REGISTRO'])->count(),
            'horas' => $horas.' h'.($minutos ? ' '.$minutos.' min' : ''),
        ];
        $datos['solicitudesPendientes'] = Solicitud::where('trabajador_id', $trabajador->id)
            ->where('estado', 'PENDIENTE')
            ->count();
        $datos['vacacionActiva'] = Vacaciones::where('trabajador_id', $trabajador->id)
            ->where(function (Builder $query) use ($hoy) {
                $query->where('estado', 'PENDIENTE')
                    ->orWhere(function (Builder $vigente) use ($hoy) {
                        $vigente->whereIn('estado', ['APROBADA', 'PROGRAMADA', 'EN_CURSO'])
                            ->whereDate('fecha_fin', '>=', $hoy);
                    });
            })
            ->orderByRaw("CASE WHEN estado = 'EN_CURSO' THEN 1 WHEN estado = 'PENDIENTE' THEN 2 ELSE 3 END")
            ->orderBy('fecha_inicio')
            ->first();
        $datos['reporteHoy'] = ReporteAvance::where('trabajador_id', $trabajador->id)
            ->whereDate('fecha_presentacion', $hoy)
            ->first();
        if ($trabajador->area_id) {
            $datos['pendientesArea'] = PendienteAvance::where('estado', 'PENDIENTE')
                ->where(function (Builder $query) use ($trabajador) {
                    $query->where('area_id', $trabajador->area_id)
                        ->orWhere(function (Builder $legado) use ($trabajador) {
                            $legado->whereNull('area_id')
                                ->whereHas('reporte.trabajador', fn (Builder $trabajadorQuery) =>
                                    $trabajadorQuery->where('area_id', $trabajador->area_id));
                        });
                })
                ->count();
        }

        $datos['asistenciasRecientes'] = Asistencia::with([
                'marcacionEntrada',
                'marcacionSalida',
            ])
            ->where('trabajador_id', $trabajador->id)
            ->whereYear('fecha', $hoy->year)
            ->whereMonth('fecha', $hoy->month)
            ->latest('fecha')
            ->latest('procesado_en')
            ->limit(6)
            ->get();
        $datos['puedeSolicitarVacaciones'] = $trabajador->tipo_vinculo === 'CONTRATADO';

        return $datos;
    }

    private function resumenAreas(?int $areaId = null)
    {
        $areas = Area::query()
            ->where('estado', true)
            ->when($areaId, fn (Builder $query) => $query->whereKey($areaId))
            ->withCount([
                'trabajadores as trabajadores_activos' => fn (Builder $query) =>
                    $query->where('estado', 'ACTIVO'),
            ])
            ->orderBy('nombre')
            ->get();

        $estadosPorArea = Asistencia::query()
            ->join('trabajadores', 'trabajadores.id', '=', 'asistencias.trabajador_id')
            ->whereDate('asistencias.fecha', today())
            ->where('trabajadores.estado', 'ACTIVO')
            ->when($areaId, fn (Builder $query) => $query->where('trabajadores.area_id', $areaId))
            ->select('trabajadores.area_id', 'asistencias.estado', DB::raw('COUNT(*) as total'))
            ->groupBy('trabajadores.area_id', 'asistencias.estado')
            ->get()
            ->groupBy('area_id');

        return $areas->map(function (Area $area) use ($estadosPorArea) {
            $estados = $estadosPorArea->get($area->id, collect());
            $porEstado = $estados->pluck('total', 'estado');
            $procesadas = (int) $estados->sum('total');
            $activos = (int) $area->trabajadores_activos;
            $asistieron = (int) (
                ($porEstado['PRESENTE'] ?? 0)
                + ($porEstado['TARDANZA'] ?? 0)
                + ($porEstado['CAMPO'] ?? 0)
            );
            $ausencias = (int) (
                ($porEstado['FALTA'] ?? 0)
                + ($porEstado['SIN_REGISTRO'] ?? 0)
            );
            $justificadas = (int) (
                ($porEstado['JUSTIFICADO'] ?? 0)
                + ($porEstado['PERMISO'] ?? 0)
                + ($porEstado['VACACIONES'] ?? 0)
                + ($porEstado['FERIADO'] ?? 0)
                + ($porEstado['NO_LABORABLE'] ?? 0)
            );
            $esperados = max(0, $activos - $justificadas);

            $area->setAttribute('asistencias_hoy', $asistieron);
            $area->setAttribute('ausencias_hoy', $ausencias);
            $area->setAttribute('tardanzas_hoy', (int) ($porEstado['TARDANZA'] ?? 0));
            $area->setAttribute('sin_asistencia_hoy', $ausencias);
            $area->setAttribute('justificadas_hoy', $justificadas);
            $area->setAttribute('procesadas_hoy', $procesadas);
            $area->setAttribute('sin_procesar', max(0, $activos - $procesadas));
            $area->setAttribute('incidencias_hoy', (int) (
                ($porEstado['TARDANZA'] ?? 0)
                + ($porEstado['FALTA'] ?? 0)
                + ($porEstado['SIN_REGISTRO'] ?? 0)
            ));
            $area->setAttribute('cobertura', $esperados > 0
                ? min(100, (int) round(($asistieron / $esperados) * 100))
                : ($activos > 0 && $justificadas >= $activos ? 100 : 0));

            return $area;
        });
    }

    private function filtrarPorAreaTrabajador(Builder $query, int $areaId): void
    {
        $query->whereHas('trabajador', fn (Builder $trabajador) =>
            $trabajador->where('area_id', $areaId));
    }

    private function filtrarPendientesPorArea(Builder $query, int $areaId): void
    {
        $query->where(function (Builder $area) use ($areaId) {
            $area->where('area_id', $areaId)
                ->orWhere(function (Builder $legado) use ($areaId) {
                    $legado->whereNull('area_id')
                        ->whereHas('reporte.trabajador', fn (Builder $trabajador) =>
                            $trabajador->where('area_id', $areaId));
                });
        });
    }

    private function sinResultados(Builder ...$consultas): void
    {
        foreach ($consultas as $consulta) {
            $consulta->whereRaw('1 = 0');
        }
    }
}
