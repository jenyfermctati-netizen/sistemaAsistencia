<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
class ConsultaDniController extends Controller
{
    public function consultar(Request $request)
    {
        $request->validate([
            'dni' => ['required', 'regex:/^[0-9]{8}$/'],
        ]);

        $token = config('services.decolecta.token');
        $url = config('services.decolecta.url');

        if (blank($token)) {
            return response()->json([
                'success' => false,
                'message' => 'No está configurado el token de Decolecta.'
            ], 503);
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(15)
                ->get(
                    rtrim($url, '/') . '/v1/reniec/dni',
                    ['numero' => $request->dni]
                );

            if (in_array($response->status(), [401, 403])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Decolecta rechazó la autorización. Verifica tu token y permisos.'
                ], 502);
            }

            if ($response->status() === 429) {
                return response()->json([
                    'success' => false,
                    'message' => 'Se alcanzó el límite de consultas de Decolecta.'
                ], 429);
            }

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo completar la consulta. Código HTTP: ' . $response->status()
                ], 502);
            }

            $persona = $response->json();

            $nombres = $persona['first_name'] ?? '';
            $paterno = $persona['first_last_name'] ?? '';
            $materno = $persona['second_last_name'] ?? '';

            if (!$nombres || (!$paterno && !$materno)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron nombres y apellidos para este DNI.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'nombres' => $nombres,
                    'apellidos' => trim($paterno . ' ' . $materno),
                    'dni' => $persona['document_number'] ?? $request->dni,
                ]
            ]);

        } catch (ConnectionException $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo conectar con Decolecta.'
            ], 503);
        }
    }
}
