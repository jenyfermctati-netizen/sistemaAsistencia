<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PrivatizarArchivos extends Command
{
    protected $signature = 'empresas:privatizar-archivos';

    protected $description = 'Traslada los adjuntos históricos del disco público al privado, verificando su contenido';

    public function handle(): int
    {
        $publico = Storage::disk('public');
        $privado = Storage::disk('local');
        $rutas = collect();
        foreach (['solicitudes', 'actividades_reporte', 'reportes_avance'] as $tabla) {
            $rutas = $rutas->merge(DB::table($tabla)->whereNotNull('archivo')->pluck('archivo'));
        }
        // También retira adjuntos huérfanos que podrían seguir expuestos por /storage.
        foreach (['solicitudes', 'reportes-avance'] as $directorio) {
            $rutas = $rutas->merge($publico->allFiles($directorio));
        }

        $movidos = 0;
        $faltantes = 0;
        foreach ($rutas->filter()->unique() as $ruta) {
            if (! $publico->exists($ruta)) {
                if (! $privado->exists($ruta)) {
                    $this->error('Archivo no encontrado: '.$ruta);
                    $faltantes++;
                }

                continue;
            }
            if (! $privado->exists($ruta)) {
                $stream = $publico->readStream($ruta);
                if (! is_resource($stream)) {
                    throw new RuntimeException('No se pudo leer '.$ruta);
                }
                try {
                    if (! $privado->put($ruta, $stream)) {
                        throw new RuntimeException('No se pudo guardar '.$ruta);
                    }
                } finally {
                    fclose($stream);
                }
            }
            $hashes = [];
            foreach ([$publico, $privado] as $disco) {
                $stream = $disco->readStream($ruta);
                if (! is_resource($stream)) {
                    throw new RuntimeException('No se pudo verificar '.$ruta);
                }
                try {
                    $hash = hash_init('sha256');
                    hash_update_stream($hash, $stream);
                    $hashes[] = hash_final($hash);
                } finally {
                    fclose($stream);
                }
            }
            if (! hash_equals($hashes[0], $hashes[1])) {
                throw new RuntimeException('Los contenidos difieren; se conserva el original público: '.$ruta);
            }
            if (! $publico->delete($ruta)) {
                throw new RuntimeException('No se pudo retirar la copia pública: '.$ruta);
            }
            $movidos++;
        }
        $this->info("Adjuntos trasladados: {$movidos}. Archivos faltantes: {$faltantes}.");

        return $faltantes ? self::FAILURE : self::SUCCESS;
    }
}
