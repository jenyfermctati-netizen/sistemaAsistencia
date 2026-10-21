<?php

namespace App\Http\Controllers;

use App\Services\ReporteControlExcelService;
use App\Services\ReporteControlService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ReporteControlController extends Controller
{
    public function __construct(
        private readonly ReporteControlService $reporteService,
        private readonly ReporteControlExcelService $excelService
    ) {}

    public function pdf(Request $request)
    {
        $datos = $this->datos($request);
        $nombre = $this->nombreArchivo($datos, 'pdf');

        return Pdf::loadView('reportesControl.pdf', $datos)
            ->setPaper('a4', 'landscape')
            ->download($nombre);
    }

    public function excel(Request $request)
    {
        return $this->excelService->descargar($this->datos($request));
    }

    private function datos(Request $request): array
    {
        $filtros = $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
        ]);

        $desde = isset($filtros['desde'])
            ? Carbon::parse($filtros['desde'])->startOfDay()
            : today()->startOfWeek();
        $hasta = isset($filtros['hasta'])
            ? Carbon::parse($filtros['hasta'])->startOfDay()
            : today()->endOfWeek()->startOfDay();

        if ($desde->diffInDays($hasta) > 31) {
            throw ValidationException::withMessages([
                'hasta' => 'El periodo del reporte no puede superar 31 días.',
            ]);
        }

        return $this->reporteService->generar(
            Auth::user(),
            $desde,
            $hasta,
            isset($filtros['area_id']) ? (int) $filtros['area_id'] : null
        );
    }

    private function nombreArchivo(array $datos, string $extension): string
    {
        return sprintf(
            'reporte-control-%s-%s.%s',
            $datos['desde']->format('Y-m-d'),
            $datos['hasta']->format('Y-m-d'),
            $extension
        );
    }
}
