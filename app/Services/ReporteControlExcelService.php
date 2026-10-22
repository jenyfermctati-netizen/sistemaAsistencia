<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteControlExcelService
{
    private const COLOR_PRIMARIO = '0F766E';

    private const COLOR_SECUNDARIO = 'DFF3EF';

    private const COLOR_TEXTO = '1F2937';

    public function descargar(array $datos): StreamedResponse
    {
        $spreadsheet = $this->crear($datos);
        $nombre = sprintf(
            'reporte-control-%s-%s.xlsx',
            $datos['desde']->format('Y-m-d'),
            $datos['hasta']->format('Y-m-d')
        );

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $nombre, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function crear(array $datos): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator(config('app.name'))
            ->setTitle('Reporte consolidado de control')
            ->setSubject('Asistencias, avances y pendientes');

        $resumen = $spreadsheet->getActiveSheet();
        $resumen->setTitle('Resumen');
        $this->crearResumen($resumen, $datos);

        if ($datos['esVistaGeneral']) {
            $this->crearAsistencias($spreadsheet->createSheet(), $datos);
            $this->crearActividades($spreadsheet->createSheet(), $datos);
            $this->crearPendientes($spreadsheet->createSheet(), $datos);
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private function crearResumen(Worksheet $sheet, array $datos): void
    {
        $sheet->setShowGridlines(false);
        $sheet->setCellValue('A1', 'Reporte consolidado de control');
        $sheet->setCellValue('A2', 'Periodo');
        $sheet->setCellValue('B2', $datos['desde']->format('d/m/Y').' al '.$datos['hasta']->format('d/m/Y'));
        $sheet->setCellValue('A3', 'Alcance');
        $sheet->setCellValue('B3', $datos['alcanceLabel']);

        $metricas = [
            'Trabajadores' => $datos['metricas']['trabajadores'],
            'Días registrados' => $datos['metricas']['dias_registrados'],
            'Horas trabajadas' => $this->horas($datos['metricas']['minutos_trabajados']),
            'Tardanzas' => $datos['metricas']['tardanzas'],
            'Faltas' => $datos['metricas']['faltas'],
            'Reportes' => $datos['metricas']['reportes'],
            'Actividades' => $datos['metricas']['actividades'],
            'Pendientes abiertos' => $datos['metricas']['pendientes_abiertos'],
            'Finalizadas' => $datos['metricas']['finalizadas'],
            'Aprobadas' => $datos['metricas']['aprobadas'],
        ];

        $sheet->fromArray(array_keys($metricas), null, 'A5');
        $sheet->fromArray(array_values($metricas), null, 'A6');

        $cabeceras = [
            'Área',
            'Trabajador',
            'DNI',
            'Días registrados',
            'Días completos',
            'Tardanzas',
            'Faltas',
            'Horas trabajadas',
            'Reportes',
            'Actividades',
            'Pendientes abiertos',
            'Finalizadas',
            'Aprobadas',
        ];
        $sheet->fromArray($cabeceras, null, 'A9');

        $fila = 10;
        foreach ($datos['areasReporte'] as $areaData) {
            foreach ($areaData['trabajadores'] as $trabajadorData) {
                $trabajador = $trabajadorData['trabajador'];
                $m = $trabajadorData['metricas'];
                $sheet->fromArray([
                    $areaData['area']->nombre,
                    trim($trabajador->nombres.' '.$trabajador->apellidos),
                    $trabajador->dni,
                    $m['dias_registrados'],
                    $m['dias_completos'],
                    $m['tardanzas'],
                    $m['faltas'],
                    $this->horas($m['minutos_trabajados']),
                    $m['reportes'],
                    $m['actividades'],
                    $m['pendientes_abiertos'],
                    $m['finalizadas'],
                    $m['aprobadas'],
                ], null, "A{$fila}");
                $sheet->setCellValueExplicit("C{$fila}", (string) $trabajador->dni, DataType::TYPE_STRING);
                $fila++;
            }
        }

        $this->estiloTitulo($sheet, 'A1:M1');
        $this->estiloCabecera($sheet, 'A5:J5');
        $this->estiloCabecera($sheet, 'A9:M9');
        $this->estiloTabla($sheet, 'A9:M'.max(9, $fila - 1));
        $sheet->freezePane('A10');
        if ($fila > 10) {
            $sheet->setAutoFilter('A9:M'.($fila - 1));
        }
        $this->anchos($sheet, [18, 28, 14, 16, 15, 12, 10, 17, 12, 12, 19, 13, 12]);
    }

    private function crearAsistencias(Worksheet $sheet, array $datos): void
    {
        $sheet->setTitle('Asistencias');
        $sheet->setShowGridlines(false);
        $cabeceras = [
            'Fecha', 'Área', 'Trabajador', 'DNI', 'Entrada mañana', 'Salida refrigerio',
            'Retorno / entrada tarde', 'Salida final', 'Horas trabajadas', 'Min. tardanza',
            'Estado', 'Origen',
        ];
        $sheet->fromArray($cabeceras, null, 'A1');

        $fila = 2;
        foreach ($datos['areasReporte'] as $areaData) {
            foreach ($areaData['trabajadores'] as $trabajadorData) {
                $trabajador = $trabajadorData['trabajador'];
                foreach ($trabajadorData['asistencias'] as $asistencia) {
                    $sheet->fromArray([
                        ExcelDate::dateTimeToExcel($asistencia->fecha),
                        $areaData['area']->nombre,
                        trim($trabajador->nombres.' '.$trabajador->apellidos),
                        $trabajador->dni,
                        $this->hora($asistencia->hora_entrada),
                        $this->hora($asistencia->hora_salida_refrigerio),
                        $this->hora($asistencia->hora_retorno_refrigerio),
                        $this->hora($asistencia->hora_salida),
                        $this->horas((int) $asistencia->minutos_trabajados),
                        (int) $asistencia->minutos_tardanza,
                        $this->estado($asistencia->estado),
                        $this->estado($asistencia->origen_marcacion ?? 'SIN MARCACIÓN'),
                    ], null, "A{$fila}");
                    $sheet->setCellValueExplicit("D{$fila}", (string) $trabajador->dni, DataType::TYPE_STRING);
                    $fila++;
                }
            }
        }

        $this->finalizarDetalle($sheet, $cabeceras, $fila, [13, 20, 28, 14, 15, 17, 19, 14, 17, 14, 17, 14]);
        if ($fila > 2) {
            $sheet->getStyle('A2:A'.($fila - 1))->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        }
    }

    private function crearActividades(Worksheet $sheet, array $datos): void
    {
        $sheet->setTitle('Actividades');
        $sheet->setShowGridlines(false);
        $cabeceras = ['Fecha', 'Área', 'Trabajador', 'DNI', 'Actividad', 'Estado del reporte'];
        $sheet->fromArray($cabeceras, null, 'A1');

        $fila = 2;
        foreach ($datos['areasReporte'] as $areaData) {
            foreach ($areaData['trabajadores'] as $trabajadorData) {
                $trabajador = $trabajadorData['trabajador'];
                foreach ($trabajadorData['actividades'] as $actividad) {
                    $sheet->fromArray([
                        ExcelDate::dateTimeToExcel($actividad->reporte->fecha_presentacion),
                        $areaData['area']->nombre,
                        trim($trabajador->nombres.' '.$trabajador->apellidos),
                        $trabajador->dni,
                        $actividad->descripcion,
                        $this->estado($actividad->reporte->estado),
                    ], null, "A{$fila}");
                    $sheet->setCellValueExplicit("D{$fila}", (string) $trabajador->dni, DataType::TYPE_STRING);
                    $fila++;
                }
            }
        }

        $this->finalizarDetalle($sheet, $cabeceras, $fila, [13, 20, 28, 14, 55, 20]);
        if ($fila > 2) {
            $sheet->getStyle('A2:A'.($fila - 1))->getNumberFormat()->setFormatCode('dd/mm/yyyy');
            $sheet->getStyle('E2:E'.($fila - 1))->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        }
    }

    private function crearPendientes(Worksheet $sheet, array $datos): void
    {
        $sheet->setTitle('Pendientes');
        $sheet->setShowGridlines(false);
        $cabeceras = [
            'Fecha registro', 'Área', 'Pendiente', 'Registrado por', 'Finalizado por',
            'Fecha finalización', 'Estado', 'Revisión', 'Observación',
        ];
        $sheet->fromArray($cabeceras, null, 'A1');

        $fila = 2;
        foreach ($datos['areasReporte'] as $areaData) {
            foreach ($areaData['pendientes'] as $pendiente) {
                $registradoPor = $pendiente->reporte?->trabajador
                    ? trim($pendiente->reporte->trabajador->nombres.' '.$pendiente->reporte->trabajador->apellidos)
                    : ($pendiente->creadoPor?->name ?? 'Administración');
                $finalizadoPor = $pendiente->resueltoPor
                    ? trim($pendiente->resueltoPor->nombres.' '.$pendiente->resueltoPor->apellidos)
                    : '';

                $sheet->fromArray([
                    ExcelDate::dateTimeToExcel($pendiente->created_at),
                    $areaData['area']->nombre,
                    $pendiente->descripcion,
                    $registradoPor,
                    $finalizadoPor,
                    $pendiente->fecha_resolucion
                        ? ExcelDate::dateTimeToExcel($pendiente->fecha_resolucion)
                        : null,
                    $this->estado($pendiente->estado),
                    $this->estado($pendiente->estado_revision ?? 'SIN REVISIÓN'),
                    $pendiente->observacion_revision,
                ], null, "A{$fila}");
                $fila++;
            }
        }

        $this->finalizarDetalle($sheet, $cabeceras, $fila, [16, 20, 48, 28, 28, 18, 16, 18, 45]);
        if ($fila > 2) {
            $sheet->getStyle('A2:A'.($fila - 1))->getNumberFormat()->setFormatCode('dd/mm/yyyy');
            $sheet->getStyle('F2:F'.($fila - 1))->getNumberFormat()->setFormatCode('dd/mm/yyyy hh:mm');
            $sheet->getStyle('C2:C'.($fila - 1))->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getStyle('I2:I'.($fila - 1))->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        }
    }

    private function finalizarDetalle(Worksheet $sheet, array $cabeceras, int $fila, array $anchos): void
    {
        $ultimaColumna = $sheet->getHighestColumn();
        $this->estiloCabecera($sheet, "A1:{$ultimaColumna}1");
        $this->estiloTabla($sheet, "A1:{$ultimaColumna}".max(1, $fila - 1));
        $sheet->freezePane('A2');
        if ($fila > 2) {
            $sheet->setAutoFilter("A1:{$ultimaColumna}".($fila - 1));
        }
        $this->anchos($sheet, $anchos);
        $sheet->getStyle("A1:{$ultimaColumna}".max(1, $fila - 1))
            ->getFont()->setName('Arial')->setSize(10);
    }

    private function estiloTitulo(Worksheet $sheet, string $rango): void
    {
        $sheet->getStyle($rango)->applyFromArray([
            'font' => [
                'name' => 'Arial',
                'size' => 16,
                'bold' => true,
                'color' => ['rgb' => self::COLOR_PRIMARIO],
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(26);
    }

    private function estiloCabecera(Worksheet $sheet, string $rango): void
    {
        $sheet->getStyle($rango)->applyFromArray([
            'font' => ['name' => 'Arial', 'bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLOR_PRIMARIO]],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
    }

    private function estiloTabla(Worksheet $sheet, string $rango): void
    {
        $sheet->getStyle($rango)->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 10, 'color' => ['rgb' => self::COLOR_TEXTO]],
            'borders' => [
                'bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'D1D5DB']],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
    }

    private function anchos(Worksheet $sheet, array $anchos): void
    {
        foreach ($anchos as $indice => $ancho) {
            $sheet->getColumnDimensionByColumn($indice + 1)->setWidth($ancho);
        }
    }

    private function horas(int $minutos): string
    {
        return intdiv($minutos, 60).' h '.($minutos % 60).' min';
    }

    private function hora(?string $hora): string
    {
        return $hora ? substr($hora, 0, 5) : '';
    }

    private function estado(?string $estado): string
    {
        return ucfirst(strtolower(str_replace('_', ' ', (string) $estado)));
    }
}
