<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Trabajador;
use App\Models\TrabajadorHorario;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReporteAsistenciaMensualService
{
    private const DIAS = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];

    public function descargar(string $formato, Carbon $desde, Carbon $hasta, array $filtros = [], ?int $areaId = null)
    {
        $datos = $this->preparar($desde, $hasta, $filtros, $areaId);
        $nombre = 'asistencia-mensual-'.$desde->format('Y-m-d').'-'.$hasta->format('Y-m-d');
        if ($datos['individual']) {
            $nombre = 'asistencia-'.str($datos['meses'][0]['empleados'][0]['nombre'])->slug().'-'.$desde->format('Y-m');
        }

        if ($formato === 'pdf') {
            $vista = $datos['individual'] ? 'asistencias.reporte-individual-pdf' : 'asistencias.reporte-mensual-pdf';

            return \Barryvdh\DomPDF\Facade\Pdf::loadView($vista, $datos)
                ->setPaper('a4', 'landscape')
                ->download($nombre.'.pdf');
        }

        $libro = $datos['individual'] ? $this->crearExcelIndividual($datos) : $this->crearExcel($datos);

        return response()->streamDownload(function () use ($libro) {
            try {
                (new Xlsx($libro))->save('php://output');
            } finally {
                $libro->disconnectWorksheets();
            }
        }, $nombre.'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function preparar(Carbon $desde, Carbon $hasta, array $filtros, ?int $areaId): array
    {
        $reporteIndividualSolicitado = (bool) ($filtros['individual'] ?? false)
            && filled($filtros['buscar'] ?? null);
        $trabajadores = Trabajador::with('area')
            ->when($areaId !== null, fn ($query) => $query->where('area_id', $areaId))
            ->when($reporteIndividualSolicitado, function ($query) use ($filtros) {
                $buscar = trim((string) $filtros['buscar']);
                $query->where(fn ($worker) => $worker->where('dni', 'like', "%{$buscar}%")
                    ->orWhere(fn ($names) => $names->nameContainsAllTerms($buscar)));
            })
            ->orderBy('apellidos')->orderBy('nombres')->get();
        $individual = $reporteIndividualSolicitado && $trabajadores->count() === 1;

        $ids = $trabajadores->modelKeys();
        $desdeConsulta = $desde->copy()->startOfWeek(Carbon::MONDAY);
        $hastaConsulta = $hasta->copy()->endOfWeek(Carbon::SUNDAY);
        $asistenciasQuery = Asistencia::with('horario')
            ->whereIn('trabajador_id', $ids)
            ->whereBetween('fecha', [$desdeConsulta->toDateString(), $hastaConsulta->toDateString()]);

        if (! empty($filtros['tipo_control'])) {
            $asistenciasQuery->where('tipo_control_aplicado', $filtros['tipo_control']);
        }
        if (! empty($filtros['estado'])) {
            match ($filtros['estado']) {
                'INCIDENCIAS' => $asistenciasQuery->whereIn('estado', ['TARDANZA', 'FALTA', 'SIN_REGISTRO']),
                'EXCEPCIONES' => $asistenciasQuery->whereIn('estado', ['JUSTIFICADO', 'FJ', 'TJ', 'CAMPO', 'VACACIONES', 'PERMISO', 'FERIADO', 'NO_LABORABLE']),
                default => $asistenciasQuery->where('estado', $filtros['estado']),
            };
        }

        $asistencias = $asistenciasQuery->get()->keyBy(fn (Asistencia $asistencia) => $asistencia->trabajador_id.'|'.$asistencia->fecha->toDateString());
        $asignaciones = TrabajadorHorario::with('horario.dias')
            ->whereIn('trabajador_id', $ids)
            ->whereDate('fecha_inicio', '<=', $hasta->copy()->endOfMonth()->toDateString())
            ->where(fn ($query) => $query->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', $desde->copy()->startOfMonth()->toDateString()))
            ->orderBy('fecha_inicio')->get()->groupBy('trabajador_id');

        $meses = [];
        $cursor = $desde->copy()->startOfMonth();
        $ultimoMes = $hasta->copy()->startOfMonth();
        while ($cursor->lte($ultimoMes)) {
            $mesInicio = $cursor->copy()->startOfMonth();
            $mesFin = $cursor->copy()->endOfMonth();
            $dias = collect(CarbonPeriod::create($mesInicio, $mesFin))->map(fn (Carbon $fecha) => $fecha->copy());
            $primeraSemana = $mesInicio->copy()->startOfWeek(Carbon::MONDAY);
            $semanas = [];
            $semanaCursor = $primeraSemana->copy();
            while ($semanaCursor->lte($mesFin)) {
                $semanas[] = [
                    'inicio' => $semanaCursor->copy(),
                    'dias' => collect(range(0, 6))->map(fn ($offset) => $semanaCursor->copy()->addDays($offset)),
                ];
                $semanaCursor->addWeek();
            }

            $empleadosMes = $trabajadores->map(function (Trabajador $trabajador) use ($dias, $semanas, $asistencias, $asignaciones, $desde, $hasta, $mesInicio, $mesFin) {
                $horariosPorDia = [];
                $asistenciasEmpleado = $asistencias->filter(fn (Asistencia $asistencia) => (int) $asistencia->trabajador_id === (int) $trabajador->id);
                $horarios = $asignaciones->get($trabajador->id, collect());
                foreach ($dias as $fecha) {
                    $asignacion = $horarios->filter(fn (TrabajadorHorario $item) => $item->fecha_inicio?->lte($fecha)
                        && (! $item->fecha_fin || $item->fecha_fin->gte($fecha)))
                        ->sortByDesc('fecha_inicio')->first();
                    $diaHorario = $asignacion?->horario?->dias?->firstWhere('dia_semana', $fecha->isoWeekday());
                    $horariosPorDia[$fecha->toDateString()] = [
                        'texto' => $this->textoHorario($diaHorario),
                        'horario' => $asignacion?->horario?->nombre,
                        'tipo' => $asignacion?->tipo_control,
                        'hora_tarde' => $diaHorario?->hora_retorno_refrigerio,
                        'es_laborable' => (bool) $diaHorario?->es_laborable,
                    ];
                }

                $diasLaborables = [];
                foreach (self::DIAS as $numero => $nombre) {
                    $opciones = collect($horariosPorDia)->filter(fn ($item, $fecha) => Carbon::parse($fecha)->isoWeekday() === $numero)
                        ->pluck('texto')->filter(fn ($texto) => $texto !== '—')->unique()->values();
                    $diasLaborables[$nombre] = $opciones->isEmpty() ? '—' : $opciones->implode(' / ');
                }

                $nombresHorario = collect($horariosPorDia)->pluck('horario')->filter()->unique()->values();
                $tiposControl = collect($horariosPorDia)->pluck('tipo')->filter()->unique()->values();
                $diasData = [];
                $semanasTotal = [];
                $totalMes = 0;

                foreach ($dias as $fecha) {
                    $dentroPeriodo = $fecha->betweenIncluded($desde, $hasta);
                    $asistencia = $dentroPeriodo ? $asistencias->get($trabajador->id.'|'.$fecha->toDateString()) : null;
                    $minutos = (int) ($asistencia?->minutos_trabajados ?? 0);
                    $totalMes += $minutos;
                    $horarioDia = $horariosPorDia[$fecha->toDateString()];
                    $marcas = $this->marcasDelDia($asistencia, $horarioDia['hora_tarde']);
                    $diasData[$fecha->toDateString()] = [
                        'numero' => $fecha->day,
                        'fecha' => $fecha->format('d/m'),
                        'fecha_iso' => $fecha->toDateString(),
                        'dia' => self::DIAS[$fecha->isoWeekday()],
                        'horario' => $horarioDia['texto'],
                        'trabajado' => $asistencia ? $this->formatoHoras($minutos) : ($dentroPeriodo ? 'Sin procesar' : 'Fuera del período'),
                        'estado' => $asistencia ? $this->etiquetaEstado($asistencia->estado) : '',
                        'minutos' => $minutos,
                        'horas_decimal' => round($minutos / 60, 1),
                        'horas_reporte' => round($minutos / 60, 2),
                        'entrada_manana' => $marcas['entrada_manana'],
                        'salida_manana' => $marcas['salida_manana'],
                        'entrada_tarde' => $marcas['entrada_tarde'],
                        'salida_tarde' => $marcas['salida_tarde'],
                        'entrada_manana_general' => $marcas['entrada_manana_general'],
                        'salida_manana_general' => $marcas['salida_manana_general'],
                        'entrada_tarde_general' => $marcas['entrada_tarde_general'],
                        'salida_tarde_general' => $marcas['salida_tarde_general'],
                        'pasaje' => $trabajador->tipo_vinculo === 'LOCADOR' ? $marcas['pasaje'] : null,
                        'tiene_asistencia' => $asistencia !== null,
                        'es_laborable' => $horarioDia['es_laborable'],
                        'dentro_periodo' => $dentroPeriodo,
                    ];
                }

                foreach ($semanas as $semana) {
                    $inicioSemana = $semana['inicio']->copy()->startOfDay();
                    $finSemana = $inicioSemana->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
                    $semanasTotal[$inicioSemana->toDateString()] = (int) $asistenciasEmpleado
                        ->filter(fn (Asistencia $asistencia) => $asistencia->fecha->betweenIncluded($inicioSemana, $finSemana))
                        ->sum('minutos_trabajados');
                }

                $calendario = [];
                foreach ($semanas as $semana) {
                    $calendario[] = collect($semana['dias'])->map(function (Carbon $fecha) use ($diasData, $mesInicio, $mesFin) {
                        if ($fecha->lt($mesInicio) || $fecha->gt($mesFin)) return null;
                        return $diasData[$fecha->toDateString()];
                    })->all();
                }

                $detalleSemanas = collect($diasData)
                    ->filter(fn ($dia) => $dia['dentro_periodo'] && ($dia['es_laborable'] || $dia['minutos'] > 0 || $dia['entrada_manana'] !== '—' || $dia['entrada_tarde'] !== '—'))
                    ->groupBy(fn ($dia) => Carbon::parse($dia['fecha_iso'])->startOfWeek(Carbon::MONDAY)->toDateString())
                    ->map(function ($registros, $inicio) {
                        return [
                            'numero' => Carbon::parse($inicio)->isoWeek(),
                            'label' => Carbon::parse($inicio)->format('d/m'),
                            'dias' => $registros->values(),
                            'minutos' => (int) $registros->sum('minutos'),
                            'pasaje' => (float) $registros->sum('pasaje'),
                        ];
                    })->values();

                $semanasPeriodo = collect($semanasTotal)
                    ->map(fn ($minutos, $inicio) => ['label' => 'Semana del '.Carbon::parse($inicio)->format('d/m'), 'horas' => $this->formatoHoras($minutos), 'minutos' => $minutos])->values();

                return [
                    'nombre' => trim($trabajador->nombres.' '.$trabajador->apellidos),
                    'dni' => $trabajador->dni,
                    'area' => $trabajador->area?->nombre ?? '—',
                    'horario' => $nombresHorario->isEmpty() ? 'Sin horario asignado' : $nombresHorario->implode(', '),
                    'tipo_control' => $tiposControl->isEmpty() ? '—' : $tiposControl->implode(', '),
                    'tipo_vinculo' => $trabajador->tipo_vinculo,
                    'dias_horario' => $diasLaborables,
                    'semanas' => $semanasPeriodo,
                    'total_minutos' => $totalMes,
                    'total_horas' => $this->formatoHoras($totalMes),
                    'dias' => $diasData,
                    'calendario' => $calendario,
                    'detalle_semanas' => $detalleSemanas,
                    'total_pasaje' => (float) collect($diasData)->sum('pasaje'),
                ];
            });

            $totalesSemanas = collect(range(0, max(0, count($semanas) - 1)))->map(function (int $indice) use ($empleadosMes) {
                $minutos = (int) $empleadosMes->sum(fn ($empleado) => $empleado['semanas'][$indice]['minutos'] ?? 0);
                return ['label' => $empleadosMes->first()['semanas'][$indice]['label'] ?? '', 'minutos' => $minutos, 'horas' => $this->formatoHoras($minutos)];
            });
            $totalGeneralMinutos = (int) $empleadosMes->sum('total_minutos');
            $totalGeneralPasaje = (float) $empleadosMes->sum('total_pasaje');
            $hayLocadores = $empleadosMes->contains(fn ($empleado) => $empleado['tipo_vinculo'] === 'LOCADOR');
            $registrosGenerales = $empleadosMes->flatMap(function ($empleado) {
                return collect($empleado['dias'])
                    ->filter(fn ($dia) => $dia['dentro_periodo'])
                    ->map(fn ($dia) => [
                        'nombre' => $empleado['nombre'],
                        'fecha' => Carbon::parse($dia['fecha_iso']),
                        'entrada_manana' => $dia['entrada_manana_general'],
                        'salida_manana' => $dia['salida_manana_general'],
                        'entrada_tarde' => $dia['entrada_tarde_general'],
                        'salida_tarde' => $dia['salida_tarde_general'],
                        'horas' => $dia['horas_reporte'],
                        'pasaje' => $dia['pasaje'],
                    ]);
            })->sort(function ($a, $b) {
                $porTrabajador = strcasecmp($a['nombre'], $b['nombre']);
                return $porTrabajador !== 0
                    ? $porTrabajador
                    : $a['fecha']->getTimestamp() <=> $b['fecha']->getTimestamp();
            })->values();

            $meses[] = [
                'titulo' => $cursor->locale('es')->translatedFormat('F Y'),
                'desde' => $mesInicio->max($desde)->copy(),
                'hasta' => $mesFin->min($hasta)->copy(),
                'dias' => $dias,
                'semanas' => count($semanas),
                'empleados' => $empleadosMes,
                'totales_semanas' => $totalesSemanas,
                'total_general_minutos' => $totalGeneralMinutos,
                'total_general_horas' => $this->formatoHoras($totalGeneralMinutos),
                'total_general_pasaje' => $totalGeneralPasaje,
                'hay_locadores' => $hayLocadores,
                'registros_generales' => $registrosGenerales,
            ];
            $cursor->addMonth();
        }

        return [
            'meses' => $meses,
            'individual' => $individual,
            'desde' => $desde,
            'hasta' => $hasta,
            'filtroPeriodo' => $desde->format('d/m/Y').' al '.$hasta->format('d/m/Y'),
            'diasSemana' => array_values(self::DIAS),
        ];
    }

    private function textoHorario($dia): string
    {
        if (! $dia) return 'Sin horario';
        if (! $dia->es_laborable) return 'Descanso';
        if (! $dia->hora_entrada && ! $dia->hora_salida) return '—';
        return substr((string) $dia->hora_entrada, 0, 5).'–'.substr((string) $dia->hora_salida, 0, 5);
    }

    private function marcasDelDia(?Asistencia $asistencia, ?string $horaInicioTarde): array
    {
        $resultado = [
            'entrada_manana' => '—',
            'salida_manana' => '—',
            'entrada_tarde' => '—',
            'salida_tarde' => '—',
            'entrada_manana_general' => '—',
            'salida_manana_general' => '—',
            'entrada_tarde_general' => '—',
            'salida_tarde_general' => '—',
            'pasaje' => 0,
        ];
        if (! $asistencia) return $resultado;

        $entrada = $this->hora($asistencia->hora_entrada);
        $salidaManana = $this->hora($asistencia->hora_salida_refrigerio);
        $entradaTarde = $this->hora($asistencia->hora_retorno_refrigerio);
        $salida = $this->hora($asistencia->hora_salida);
        $entradaCompleta = $this->horaConSegundos($asistencia->hora_entrada);
        $salidaMananaCompleta = $this->horaConSegundos($asistencia->hora_salida_refrigerio);
        $entradaTardeCompleta = $this->horaConSegundos($asistencia->hora_retorno_refrigerio);
        $salidaCompleta = $this->horaConSegundos($asistencia->hora_salida);

        if ($salidaManana !== '—' || $entradaTarde !== '—') {
            $resultado['entrada_manana'] = $entrada;
            $resultado['salida_manana'] = $salidaManana;
            $resultado['entrada_tarde'] = $entradaTarde;
            $resultado['salida_tarde'] = $salida;
            $resultado['entrada_manana_general'] = $entradaCompleta;
            $resultado['salida_manana_general'] = $salidaMananaCompleta;
            $resultado['entrada_tarde_general'] = $entradaTardeCompleta;
            $resultado['salida_tarde_general'] = $salidaCompleta;
            $resultado['pasaje'] = ((int) ($entrada !== '—' || $salidaManana !== '—') + (int) ($entradaTarde !== '—' || $salida !== '—')) * 5;

            return $resultado;
        }

        $horaInicioTarde = $horaInicioTarde ? substr($horaInicioTarde, 0, 5) : null;
        $esTurnoTarde = $entrada !== '—' && $horaInicioTarde && $entrada >= $horaInicioTarde;
        if ($esTurnoTarde) {
            $resultado['entrada_tarde'] = $entrada;
            $resultado['salida_tarde'] = $salida;
            $resultado['entrada_tarde_general'] = $entradaCompleta;
            $resultado['salida_tarde_general'] = $salidaCompleta;
            $resultado['pasaje'] = (int) ($entrada !== '—' || $salida !== '—') * 5;
        } else {
            $resultado['entrada_manana'] = $entrada;
            $resultado['salida_manana'] = $salida;
            $resultado['entrada_manana_general'] = $entradaCompleta;
            $resultado['salida_manana_general'] = $salidaCompleta;
            $resultado['pasaje'] = (int) ($entrada !== '—' || $salida !== '—') * 5;
        }

        return $resultado;
    }

    private function hora(?string $hora): string
    {
        return $hora ? substr($hora, 0, 5) : '—';
    }

    private function horaConSegundos(?string $hora): string
    {
        if (! $hora) return '—';
        $hora = (string) $hora;
        return strlen($hora) >= 8 ? substr($hora, 0, 8) : substr($hora, 0, 5).':00';
    }

    private function formatoHoras(int $minutos): string
    {
        return intdiv($minutos, 60).' h '.str_pad((string) ($minutos % 60), 2, '0', STR_PAD_LEFT).' min';
    }

    private function etiquetaEstado(?string $estado): string
    {
        return match ($estado) {
            'PRESENTE' => 'Presente', 'TARDANZA' => 'Tardanza', 'FALTA' => 'Falta',
            'JUSTIFICADO' => 'Justificado', 'FJ' => 'Falta justificada', 'TJ' => 'Tardanza justificada',
            'CAMPO' => 'Campo', 'VACACIONES' => 'Vacaciones', 'PERMISO' => 'Permiso',
            'FERIADO' => 'Feriado', 'NO_LABORABLE' => 'No laborable', 'SIN_REGISTRO' => 'Sin registro',
            default => $estado ?? '—',
        };
    }

    private function crearExcel(array $datos): Spreadsheet
    {
        $libro = new Spreadsheet;
        foreach ($datos['meses'] as $indice => $mes) {
            $resumen = $indice === 0 ? $libro->getActiveSheet() : $libro->createSheet();
            $this->crearResumenExcel($resumen, $mes);
        }
        $libro->setActiveSheetIndex(0);
        return $libro;
    }

    private function crearExcelIndividual(array $datos): Spreadsheet
    {
        $libro = new Spreadsheet;
        foreach ($datos['meses'] as $indice => $mes) {
            $sheet = $indice === 0 ? $libro->getActiveSheet() : $libro->createSheet();
            $empleado = $mes['empleados']->first();
            $sheet->setTitle('Asistencia '.substr($mes['desde']->format('Y-m'), 0, 7));
            $sheet->setShowGridlines(false);
            $sheet->mergeCells('A1:G1');
            $sheet->setCellValue('A1', 'Reporte Detallado de Asistencia Semanal');
            $sheet->setCellValue('A3', 'Colaborador:');
            $sheet->setCellValue('B3', $empleado['nombre'] ?? '—');
            $sheet->setCellValue('A4', 'Período:');
            $sheet->setCellValue('B4', ucfirst($mes['titulo']));
            $headers = ['Fecha', 'Entrada Mañana', 'Salida Mañana', 'Entrada Tarde', 'Salida Tarde', 'Total Horas', 'Pasaje (S/)'];
            $sheet->fromArray($headers, null, 'A6');
            $fila = 7;
            foreach ($empleado['detalle_semanas'] as $semana) {
                $sheet->mergeCells("A{$fila}:G{$fila}");
                $sheet->setCellValue("A{$fila}", 'SEMANA '.$semana['numero']);
                $sheet->getStyle("A{$fila}:G{$fila}")->getFont()->setBold(true);
                $sheet->getStyle("A{$fila}:G{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DFF3EF');
                $fila++;
                foreach ($semana['dias'] as $dia) {
                    $fechaTexto = Carbon::parse($dia['fecha_iso'])->format('d/m/Y');
                    $sheet->fromArray([
                        $fechaTexto,
                        $dia['entrada_manana'],
                        $dia['salida_manana'],
                        $dia['entrada_tarde'],
                        $dia['salida_tarde'],
                        $this->formatoHorasCorto($dia['minutos']),
                        $empleado['tipo_vinculo'] === 'LOCADOR' ? number_format($dia['pasaje'], 2, '.', '') : '—',
                    ], null, 'A'.$fila);
                    $fila++;
                }
                $sheet->mergeCells("A{$fila}:E{$fila}");
                $sheet->setCellValue("A{$fila}", 'Subtotal semanal:');
                $sheet->setCellValue("F{$fila}", $this->formatoHorasCorto($semana['minutos']));
                $sheet->setCellValue("G{$fila}", $empleado['tipo_vinculo'] === 'LOCADOR' ? number_format($semana['pasaje'], 2, '.', '') : '—');
                $sheet->getStyle("A{$fila}:G{$fila}")->getFont()->setBold(true);
                $fila++;
            }

            $sheet->mergeCells("A{$fila}:E{$fila}");
            $sheet->setCellValue("A{$fila}", 'TOTAL DEL MES:');
            $sheet->setCellValue("F{$fila}", $empleado['total_horas'] ?? '0 h 00 min');
            $sheet->setCellValue("G{$fila}", $empleado['tipo_vinculo'] === 'LOCADOR' ? number_format($empleado['total_pasaje'], 2, '.', '') : '—');
            $sheet->getStyle("A{$fila}:G{$fila}")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle("A{$fila}:G{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('17324D');
            $fila += 3;
            $sheet->mergeCells("A{$fila}:C{$fila}");
            $sheet->mergeCells('E'.($fila).':G'.($fila));
            $sheet->setCellValue("A{$fila}", '____________________________');
            $sheet->setCellValue("E{$fila}", '____________________________');
            $fila++;
            $sheet->mergeCells("A{$fila}:C{$fila}");
            $sheet->mergeCells("E{$fila}:G{$fila}");
            $sheet->setCellValue("A{$fila}", 'Firma Supervisor');
            $sheet->setCellValue("E{$fila}", 'Firma Gerente de la Empresa');

            $sheet->getStyle('A1:G1')->getFont()->setBold(true)->setSize(17)->getColor()->setRGB('17324D');
            $sheet->getStyle('A6:G6')->getFont()->setBold(true);
            $sheet->getStyle('A6:G6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8F3F1');
            $sheet->getStyle('A6:G'.max(6, $fila))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D7E4E2');
            $sheet->getStyle('A6:G'.max(6, $fila))->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
            $sheet->getStyle('A'.$fila.':G'.$fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            foreach (['A' => 14, 'B' => 18, 'C' => 18, 'D' => 18, 'E' => 18, 'F' => 15, 'G' => 15] as $col => $width) $sheet->getColumnDimension($col)->setWidth($width);
            $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setFitToWidth(1)->setFitToHeight(0);
            $sheet->getPageMargins()->setTop(.35)->setBottom(.35)->setLeft(.3)->setRight(.3);
        }
        $libro->setActiveSheetIndex(0);
        return $libro;
    }

    private function formatoHorasCorto(int $minutos): string
    {
        $horas = $minutos / 60;
        return (floor($horas) === $horas ? (string) (int) $horas : number_format($horas, 1, '.', '')).'h';
    }

    private function crearResumenExcel(Worksheet $sheet, array $mes): void
    {
        $sheet->setTitle('Asistencia '.substr($mes['desde']->format('Y-m'), 0, 7));
        $sheet->setShowGridlines(false);
        $sheet->setCellValue('A1', 'Reporte general de asistencia — '.ucfirst($mes['titulo']));
        $sheet->setCellValue('A2', 'Período: '.$mes['desde']->format('d/m/Y').' al '.$mes['hasta']->format('d/m/Y'));
        $cabeceras = ['Colaborador', 'Fecha', 'Ingreso Mañana', 'Salida Mañana', 'Ingreso Tarde', 'Salida Tarde', 'Total Hrs', 'Pasajes (S/)'];
        $ultima = Coordinate::stringFromColumnIndex(count($cabeceras));
        $sheet->fromArray($cabeceras, null, 'A4');
        $registros = $mes['registros_generales'];
        $fila = 5;
        foreach ($registros as $registro) {
            $sheet->fromArray([
                $registro['nombre'].(! empty($registro['sin_asistencias']) ? ' (sin asistencias del mes)' : ''),
                $registro['fecha']?->format('d/m/Y') ?? '—',
                $registro['entrada_manana'],
                $registro['salida_manana'],
                $registro['entrada_tarde'],
                $registro['salida_tarde'],
                $registro['horas'],
                $registro['pasaje'] === null ? '—' : $registro['pasaje'],
            ], null, 'A'.$fila++);
        }
        if ($registros->isEmpty()) {
            $sheet->mergeCells('A5:'.$ultima.'5');
            $sheet->setCellValue('A5', 'No hay asistencias registradas en este período.');
            $fila = 6;
        }
        $sheet->mergeCells("A{$fila}:F{$fila}");
        $sheet->setCellValue("A{$fila}", 'TOTAL GENERAL DEL MES');
        $sheet->setCellValue("G{$fila}", round($mes['total_general_minutos'] / 60, 2));
        $sheet->setCellValue("H{$fila}", $mes['hay_locadores'] ? $mes['total_general_pasaje'] : '—');
        $sheet->getStyle("A{$fila}:H{$fila}")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle("A{$fila}:H{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('17324D');
        $sheet->mergeCells('A1:'.$ultima.'1');
        $sheet->getStyle('A1:'.$ultima.'1')->getFont()->setBold(true)->setSize(15)->getColor()->setRGB('17324D');
        $sheet->getStyle('A4:'.$ultima.'4')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A4:'.$ultima.'4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('343434');
        $sheet->getStyle('A4:'.$ultima.$fila)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('C7CDD1');
        $sheet->getStyle('A4:'.$ultima.$fila)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('G5:H'.$fila)->getNumberFormat()->setFormatCode('0.##');
        $sheet->freezePane('A5');
        foreach ([1 => 29, 2 => 14, 3 => 17, 4 => 17, 5 => 17, 6 => 17, 7 => 12, 8 => 14] as $col => $width) $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setWidth($width);
        $sheet->setAutoFilter('A4:'.$ultima.max(4, $fila - 1));
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(.35)->setBottom(.35)->setLeft(.3)->setRight(.3);
    }

    private function crearCalendarioExcel(Worksheet $sheet, array $mes): void
    {
        $sheet->setTitle('Calendario '.substr($mes['desde']->format('Y-m'), 0, 7));
        $sheet->setShowGridlines(false);
        $fila = 1;
        foreach ($mes['empleados'] as $empleado) {
            $sheet->mergeCells("A{$fila}:G{$fila}");
            $sheet->setCellValue("A{$fila}", $empleado['nombre'].' · DNI '.$empleado['dni'].' · '.$empleado['area']);
            $sheet->getStyle("A{$fila}:G{$fila}")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle("A{$fila}:G{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0F766E');
            $fila++;
            $sheet->mergeCells("A{$fila}:G{$fila}");
            $sheet->setCellValue("A{$fila}", 'Horario: '.$empleado['horario'].' · Tipo de control: '.$empleado['tipo_control'].' · Total: '.$empleado['total_horas']);
            $sheet->getStyle("A{$fila}:G{$fila}")->getAlignment()->setWrapText(true);
            $fila++;
            $sheet->fromArray(array_values(self::DIAS), null, "A{$fila}");
            $sheet->getStyle("A{$fila}:G{$fila}")->getFont()->setBold(true);
            $sheet->getStyle("A{$fila}:G{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DFF3EF');
            $fila++;
            foreach ($empleado['calendario'] as $semana) {
                foreach ($semana as $col => $dia) {
                    $celda = Coordinate::stringFromColumnIndex($col + 1).$fila;
                    if (! $dia) continue;
                    $contenido = $dia['numero'].' · '.$dia['horario'];
                    if ($dia['dentro_periodo']) $contenido .= "\nTrabajado: ".$dia['trabajado'].($dia['estado'] ? "\n".$dia['estado'] : '');
                    else $contenido .= "\nFuera del período";
                    $sheet->setCellValue($celda, $contenido);
                }
                $sheet->getRowDimension($fila)->setRowHeight(62);
                $sheet->getStyle("A{$fila}:G{$fila}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
                $sheet->getStyle("A{$fila}:G{$fila}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D7E4E2');
                $fila++;
            }
            $fila++;
        }
        foreach (range('A', 'G') as $col) $sheet->getColumnDimension($col)->setWidth(22);
    }
}
