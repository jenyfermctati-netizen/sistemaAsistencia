<?php

namespace App\Http\Controllers;

use App\Models\DispositivoBiometrico;
use App\Models\Marcacion;
use App\Models\Trabajador;
use App\Services\AsistenciaService;
use App\Services\BiometricoSyncService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BiometricoController extends Controller
{
    public function __construct(
        private readonly AsistenciaService $asistenciaService,
        private readonly BiometricoSyncService $biometricoSyncService,
    ) {}

    public function cdata(Request $request)
    {
        $sn = $this->validarDispositivo($request);
        $this->registrarConexion($sn, $request->ip());

        $table = strtoupper(trim((string) $request->query('table', '')));
        $body = trim($request->getContent());

        Log::info('SENSEFACE CDATA', [
            'sn' => $sn,
            'table' => $table,
            'ip' => $request->ip(),
            'body' => $body,
        ]);

        if ($request->isMethod('GET')) {
            return $this->texto($this->opcionesDispositivo($sn));
        }

        if ($table === 'ATTLOG') {
            return $this->procesarAttlog($sn, $body);
        }

        if ($table === 'USERINFO') {
            $this->procesarUsuariosDispositivo($sn, $body);

            return $this->texto('OK');
        }

        if ($table === 'OPERLOG') {
            Log::info('SENSEFACE OPERLOG', [
                'sn' => $sn,
                'body' => $body,
            ]);

            return $this->texto('OK');
        }

        Log::debug('SENSEFACE CDATA SIN PROCESADOR', [
            'sn' => $sn,
            'table' => $table,
            'body' => $body,
        ]);

        return $this->texto('OK');
    }

    public function getRequest(Request $request)
    {
        $sn = $this->validarDispositivo($request);
        $this->registrarConexion($sn, $request->ip());

        $dispositivo = DispositivoBiometrico::where('serial', $sn)->first();

        if (!$dispositivo)
            return $this->texto('OK');

        $comando = $this->biometricoSyncService->siguienteComando($dispositivo);

        if (!$comando)
            return $this->texto('OK');

        Log::info('SENSEFACE COMANDO ENVIADO', [
            'sn' => $sn,
            'id' => $comando->id,
            'tipo' => $comando->tipo,
            'comando' => $comando->comando,
        ]);

        return $this->texto(
            "C:{$comando->id}:{$comando->comando}\n"
        );
    }

    public function deviceCmd(Request $request)
    {
        $sn = $this->validarDispositivo($request);
        $this->registrarConexion($sn, $request->ip());

        $dispositivo = DispositivoBiometrico::where('serial', $sn)->first();

        if (!$dispositivo)
            return $this->texto('OK');

        $contenido = trim($request->getContent());

        Log::info('SENSEFACE DEVICECMD', [
            'sn' => $sn,
            'query' => $request->query(),
            'body' => $contenido,
        ]);

        $lineas = $contenido !== ''
            ? preg_split('/\r\n|\r|\n/', $contenido)
            : [''];

        foreach ($lineas as $linea) {
            parse_str($linea, $datos);

            $id = (int) (
                $datos['ID']
                    ?? $datos['CmdID']
                    ?? $request->query('ID')
                    ?? $request->query('CmdID')
                    ?? 0
            );

            $codigo = (int) (
                $datos['Return']
                    ?? $request->query('Return')
                    ?? -999
            );

            if ($id > 0) {
                $this->biometricoSyncService->registrarRespuesta(
                    $dispositivo,
                    $id,
                    $codigo,
                    $linea !== '' ? $linea : http_build_query($request->query())
                );
            }
        }

        return $this->texto('OK');
    }

    public function registry(Request $request)
    {
        $sn = $this->validarDispositivo($request);

        $this->registrarConexion($sn, $request->ip());

        Log::info('SENSEFACE REGISTRY', [
            'sn' => $sn,
            'ip' => $request->ip(),
            'body' => $request->getContent(),
        ]);

        return $this->texto('OK');
    }

    public function ping(Request $request)
    {
        $this->validarDispositivo($request);

        return $this->texto('OK');
    }

    private function procesarAttlog(string $sn, string $body)
    {
        if ($body === '') {
            return $this->texto('OK');
        }

        $procesadas = 0;

        foreach (preg_split('/\r\n|\r|\n/', $body) as $linea) {
            $linea = trim($linea);

            if ($linea === '') {
                continue;
            }

            try {
                $datos = $this->parsearAttlog($linea);

                if (!$datos) {
                    Log::warning('ATTLOG formato no reconocido', [
                        'sn' => $sn,
                        'linea' => $linea,
                    ]);

                    continue;
                }

                $trabajador = Trabajador::where(
                    'codigo_biometrico',
                    $datos['pin']
                )->first();

                if (!$trabajador) {
                    Log::warning('ATTLOG trabajador no encontrado', [
                        'sn' => $sn,
                        'pin' => $datos['pin'],
                        'fecha_hora' => $datos['fecha_hora'],
                    ]);

                    continue;
                }

                $fechaHora = Carbon::parse($datos['fecha_hora']);

                $eventoExternoId = hash(
                    'sha256',
                    $sn . '|'
                        . $datos['pin'] . '|'
                        . $fechaHora->format('Y-m-d H:i:s') . '|'
                        . $datos['estado'] . '|'
                        . ($datos['verificacion'] ?? '')
                );

                $duplicada = Marcacion::where(
                    'evento_externo_id',
                    $eventoExternoId
                )->first();

                if ($duplicada) {
                    Log::info('ATTLOG duplicado ignorado', [
                        'trabajador_id' => $trabajador->id,
                        'fecha_hora' => $fechaHora->format('Y-m-d H:i:s'),
                    ]);

                    $this->asistenciaService->procesarTrabajador(
                        $trabajador,
                        $fechaHora->copy()->startOfDay()
                    );

                    $procesadas++;
                    continue;
                }

                $lecturaCercana = Marcacion::where(
                    'trabajador_id',
                    $trabajador->id
                )
                    ->where('origen', 'BIOMETRICO')
                    ->where('anulada', false)
                    ->whereBetween('fecha_hora', [
                        $fechaHora->copy()->subSeconds(30),
                        $fechaHora->copy()->addSeconds(30),
                    ])
                    ->exists();

                if ($lecturaCercana) {
                    Log::info('ATTLOG lectura repetida ignorada', [
                        'trabajador_id' => $trabajador->id,
                        'fecha_hora' => $fechaHora->format('Y-m-d H:i:s'),
                    ]);

                    $procesadas++;
                    continue;
                }

                $tipo = $this->determinarTipo(
                    $trabajador->id,
                    $fechaHora
                );

                Marcacion::create([
                    'trabajador_id' => $trabajador->id,
                    'fecha_hora' => $fechaHora,
                    'tipo' => $tipo,
                    'origen' => 'BIOMETRICO',
                    'dispositivo' => "SenseFace 2A - {$sn}",
                    'evento_externo_id' => $eventoExternoId,
                    'registrado_por' => null,
                    'motivo_manual' => null,
                    'anulada' => false,
                ]);

                $this->registrarUltimaMarcacion($sn, $fechaHora);

                $this->asistenciaService->procesarTrabajador(
                    $trabajador,
                    $fechaHora->copy()->startOfDay()
                );

                $procesadas++;

                Log::info('ATTLOG registrado', [
                    'sn' => $sn,
                    'pin' => $datos['pin'],
                    'trabajador_id' => $trabajador->id,
                    'fecha_hora' => $fechaHora->format('Y-m-d H:i:s'),
                    'tipo' => $tipo,
                    'estado_dispositivo' => $datos['estado'],
                    'verificacion' => $datos['verificacion'],
                ]);
            } catch (\Throwable $e) {
                Log::error('Error procesando ATTLOG', [
                    'sn' => $sn,
                    'linea' => $linea,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->texto("OK: {$procesadas}");
    }

    private function parsearAttlog(string $linea): ?array
    {
        $campos = explode("\t", $linea);

        if (
            count($campos) >= 2 &&
            preg_match('/^\d{4}-\d{2}-\d{2}/', trim($campos[1]))
        ) {
            return [
                'pin' => trim($campos[0]),
                'fecha_hora' => trim($campos[1]),
                'estado' => isset($campos[2])
                    ? trim($campos[2])
                    : '0',
                'verificacion' => isset($campos[3])
                    ? trim($campos[3])
                    : null,
            ];
        }

        if (
            preg_match(
                '/(?:PIN=)?([^\t\s]+).*?(?:DateTime=)?(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/i',
                $linea,
                $m
            )
        ) {
            preg_match(
                '/Status=(\d+)/i',
                $linea,
                $estado
            );

            preg_match(
                '/(?:Verified|VerifyType)=(\d+)/i',
                $linea,
                $verificacion
            );

            return [
                'pin' => $m[1],
                'fecha_hora' => $m[2],
                'estado' => $estado[1] ?? '0',
                'verificacion' => $verificacion[1] ?? null,
            ];
        }

        return null;
    }

    private function determinarTipo(int $trabajadorId, Carbon $fechaHora): string
    {
        $tieneMarcacion = Marcacion::where(
            'trabajador_id',
            $trabajadorId
        )
            ->whereDate(
                'fecha_hora',
                $fechaHora->toDateString()
            )
            ->where('anulada', false)
            ->exists();

        return $tieneMarcacion
            ? 'SALIDA'
            : 'ENTRADA';
    }

    private function validarDispositivo(Request $request): string
    {
        $sn = trim((string) $request->query('SN'));
        $serialPermitido = trim(
            (string) config('services.senseface.serial')
        );

        if (
            $serialPermitido === '' ||
            $sn === '' ||
            !hash_equals($serialPermitido, $sn)
        ) {
            Log::warning('SENSEFACE dispositivo no autorizado', [
                'sn' => $sn ?: null,
                'ip' => $request->ip(),
            ]);

            abort(403, 'Dispositivo no autorizado.');
        }

        return $sn;
    }

    private function opciones(string $sn)
    {
        $contenido = implode("\r\n", [
            "GET OPTION FROM: {$sn}",
            'ATTLOGStamp=None',
            'OPERLOGStamp=9999',
            'TransTimes=00:00;23:59',
            'TransInterval=1',
            'TransFlag=TransData AttLog OpLog EnrollUser ChgUser',
            'Realtime=1',
            'Encrypt=None',
            'ServerVer=1.0',
            '',
        ]);

        return $this->texto($contenido);
    }

    private function texto(string $contenido)
    {
        return response($contenido, 200)
            ->header(
                'Content-Type',
                'text/plain; charset=UTF-8'
            )
            ->header('Server', 'zkfpid/2.0');
    }

    private function registrarConexion(string $sn, ?string $ip): void
    {
        DispositivoBiometrico::updateOrCreate(
            ['serial' => $sn],
            [
                'nombre' => 'SenseFace Principal',
                'modelo' => 'SenseFace 2A',
                'ultima_ip' => $ip,
                'ultima_conexion' => now(),
            ]
        );
    }

    private function registrarUltimaMarcacion(string $sn, Carbon $fechaHora): void
    {
        DispositivoBiometrico::where('serial', $sn)->update([
            'ultima_marcacion' => $fechaHora,
        ]);
    }

    private function procesarUsuariosDispositivo(string $sn, string $body): void
    {
        $dispositivo = DispositivoBiometrico::where('serial', $sn)->first();

        if (!$dispositivo || trim($body) === '')
            return;

        foreach (preg_split('/\r\n|\r|\n/', $body) as $linea) {
            $linea = trim($linea);

            if ($linea === '')
                continue;

            $usuario = $this->parsearUsuarioBiometrico($linea);

            if (!$usuario)
                continue;

            $this->biometricoSyncService->registrarUsuario(
                $dispositivo,
                $usuario['pin'],
                $usuario['nombre'],
                $usuario['privilegio'],
                $usuario['tarjeta']
            );

            Log::info('SENSEFACE USUARIO RECIBIDO', [
                'sn' => $sn,
                'pin' => $usuario['pin'],
                'nombre' => $usuario['nombre'],
            ]);
        }
    }

    private function parsearUsuarioBiometrico(string $linea): ?array
    {
        $linea = trim($linea);

        if (!preg_match('/^USER(?:\s|\t)/i', $linea)) {
            return null;
        }

        if (!preg_match('/\bPIN=([^\s\t]+)/i', $linea, $pin)) {
            return null;
        }

        preg_match(
            '/\bName=(.*?)(?=\s+(?:Pri|Privilege|Passwd|Card|Grp|TZ|Verify)=|$)/i',
            $linea,
            $nombre
        );

        preg_match(
            '/\b(?:Pri|Privilege)=([^\s\t]+)/i',
            $linea,
            $privilegio
        );

        preg_match(
            '/\bCard=([^\s\t]+)/i',
            $linea,
            $tarjeta
        );

        return [
            'pin' => trim($pin[1]),
            'nombre' => isset($nombre[1])
                ? trim($nombre[1])
                : null,
            'privilegio' => $privilegio[1] ?? null,
            'tarjeta' => $tarjeta[1] ?? null,
        ];
    }
}
