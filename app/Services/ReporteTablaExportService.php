<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReporteTablaExportService
{
    public function descargar(string $formato, string $nombre, string $titulo, array $columnas, array $anchos, iterable $filas, ?string $desde, ?string $hasta)
    {
        $periodo = match (true) {
            $desde && $desde === $hasta => Carbon::parse($desde)->format('d/m/Y'),
            $desde && $hasta => Carbon::parse($desde)->format('d/m/Y').' al '.Carbon::parse($hasta)->format('d/m/Y'),
            (bool) $desde => 'Desde '.Carbon::parse($desde)->format('d/m/Y'),
            (bool) $hasta => 'Hasta '.Carbon::parse($hasta)->format('d/m/Y'),
            default => 'Todas las fechas',
        };

        $nombre .= '-'.today()->toDateString().'.'.$formato;

        if ($formato === 'pdf') {
            return Pdf::loadView('reportesControl.tabla-pdf', [
                'columnas' => $columnas,
                'titulo' => $titulo,
                'filas' => $filas,
                'periodo' => $periodo,
            ])->setPaper('a4', 'landscape')->download($nombre);
        }

        return response()->streamDownload(function () use ($filas, $periodo, $titulo, $columnas, $anchos) {
            $libro = new Spreadsheet;
            $hoja = $libro->getActiveSheet()->setTitle('Reporte');
            $hoja->setCellValue('A1', $titulo);
            $hoja->setCellValue('A2', 'Período');
            $hoja->setCellValue('B2', $periodo);
            $hoja->fromArray($columnas, null, 'A4');
            $ultimaColumna = Coordinate::stringFromColumnIndex(count($columnas));
            $hoja->getStyle('A4:'.$ultimaColumna.'4')->getFont()->setBold(true);
            $hoja->freezePane('A5');

            $numero = 5;
            foreach ($filas as $fila) {
                foreach ($fila as $columna => $valor) {
                    $celda = Coordinate::stringFromColumnIndex($columna + 1).$numero;
                    $hoja->setCellValueExplicit($celda, (string) $valor, DataType::TYPE_STRING);
                }
                $numero++;
            }

            foreach ($anchos as $columna => $ancho) {
                $hoja->getColumnDimension($columna)->setWidth($ancho);
            }
            $hoja->getStyle('A5:'.$ultimaColumna.max(5, $numero - 1))->getAlignment()->setWrapText(true);
            $hoja->setAutoFilter('A4:'.$ultimaColumna.max(4, $numero - 1));

            try {
                (new Xlsx($libro))->save('php://output');
            } finally {
                $libro->disconnectWorksheets();
            }
        }, $nombre, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
