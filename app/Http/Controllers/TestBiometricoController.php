<?php

namespace App\Http\Controllers;

use App\Models\Marcacion;
use App\Models\Trabajador;
use App\Services\AsistenciaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TestBiometricoController extends Controller
{
    public function __construct(
        private readonly AsistenciaService $asistenciaService
    ) {}

    public function marcarAsistenciaTest(Request $request)
    {
        // 1. Validar el payload de Postman
        $request->validate([
            'user_id' => 'required|exists:trabajadores,id', // Validamos que el trabajador exista
            'timestamp' => 'nullable|date_format:Y-m-d H:i:s'
        ]);

        $trabajadorId = $request->input('user_id');
        
        // 2. Determinar la fecha y hora a registrar
        $fechaHoraMarcacion = $request->filled('timestamp') 
            ? Carbon::parse($request->input('timestamp'))
            : now();
            
        $fechaHoyInicio = $fechaHoraMarcacion->copy()->startOfDay();
        $fechaHoyFin = $fechaHoraMarcacion->copy()->endOfDay();

        // 3. Buscar la última marcación del trabajador en ese día
        $ultimaMarcacion = Marcacion::where('trabajador_id', $trabajadorId)
            ->whereBetween('fecha_hora', [$fechaHoyInicio, $fechaHoyFin])
            ->where('anulada', false) // Ignoramos las anuladas
            ->orderBy('fecha_hora', 'desc')
            ->first();

        // 4. Lógica de Alternancia Automática
        // Tus enums son en mayúsculas según tu migración[cite: 2]
        $tipoMarcacion = 'ENTRADA'; 

        if ($ultimaMarcacion && $ultimaMarcacion->tipo === 'ENTRADA') {
            $tipoMarcacion = 'SALIDA';
        }

        // 5. Crear la Marcación simulando que viene del biométrico
        $marcacion = Marcacion::create([
            'trabajador_id' => $trabajadorId,
            'fecha_hora'    => $fechaHoraMarcacion,
            'tipo'          => $tipoMarcacion,
            'origen'        => 'BIOMETRICO', // Usamos el enum correcto[cite: 2]
            'dispositivo'   => 'POSTMAN_TEST', // Para que lo identifiques en BD
            'anulada'       => false,
        ]);

        // 6. Reprocesar la Asistencia para que aparezca en el Dashboard
        // Instanciamos al trabajador y usamos tu servicio existente
        $trabajador = Trabajador::find($trabajadorId);
        $this->asistenciaService->procesarTrabajador(
            $trabajador,
            $fechaHoyInicio
        );

        Log::info("Test Postman - Trabajador: {$trabajadorId} | Tipo: {$tipoMarcacion} | Hora: {$fechaHoraMarcacion}");

        return response()->json([
            'success' => true,
            'message' => "Marcación de {$tipoMarcacion} registrada y asistencia recalculada exitosamente.",
            'data' => [
                'trabajador_id' => $marcacion->trabajador_id,
                'fecha_hora'    => $marcacion->fecha_hora->toDateTimeString(),
                'tipo'          => $marcacion->tipo,
                'origen'        => $marcacion->origen
            ]
        ], 201);
    }
}
