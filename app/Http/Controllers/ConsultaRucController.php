<?php

namespace App\Http\Controllers;

use App\Rules\RucPeru;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ConsultaRucController extends Controller
{
    public function consultar(Request $request)
    {
        $data = $request->validate([
            'ruc' => ['required', 'regex:/^[0-9]{11}$/', new RucPeru],
        ]);

        $token = config('services.decolecta.token');
        $url = config('services.decolecta.url');

        if (blank($token)) {
            return response()->json([
                'success' => false,
                'message' => 'No está configurado el token de Decolecta.',
            ], 503);
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(15)
                ->get(rtrim($url, '/').'/v1/sunat/ruc', ['numero' => $data['ruc']]);

            if (in_array($response->status(), [401, 403])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Decolecta rechazó la autorización. Verifica tu token y permisos.',
                ], 502);
            }

            if ($response->status() === 429) {
                return response()->json([
                    'success' => false,
                    'message' => 'Se alcanzó el límite de consultas de Decolecta.',
                ], 429);
            }

            if (! $response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron datos para ese RUC. Puedes completar los campos manualmente.',
                ], $response->status() === 404 ? 404 : 502);
            }

            $empresa = $response->json();
            $razonSocial = trim((string) ($empresa['razon_social'] ?? ''));

            if ($razonSocial === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'La consulta no devolvió la razón social. Puedes completar los campos manualmente.',
                ], 404);
            }

            $direccion = trim((string) ($empresa['direccion'] ?? $empresa['dirección'] ?? ''));
            $ubicacion = collect([
                $empresa['distrito'] ?? null,
                $empresa['provincia'] ?? null,
                $empresa['departamento'] ?? null,
            ])->filter(fn ($parte) => filled($parte))->unique()->implode(', ');

            return response()->json([
                'success' => true,
                'data' => [
                    'razon_social' => $razonSocial,
                    'direccion' => trim(implode(', ', array_filter([$direccion, $ubicacion]))),
                    'estado' => $empresa['estado'] ?? null,
                    'condicion' => $empresa['condicion'] ?? null,
                ],
            ]);
        } catch (ConnectionException) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo conectar con Decolecta. Puedes completar los campos manualmente.',
            ], 503);
        }
    }
}
