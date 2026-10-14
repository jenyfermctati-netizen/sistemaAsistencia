<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $ids = DB::table('pendientes_avance as pendiente')
            ->join('reportes_avance as reporte', 'reporte.id', '=', 'pendiente.reporte_avance_id')
            ->where('pendiente.estado', 'PENDIENTE')
            ->where('pendiente.estado_revision', 'POR_REVISAR')
            ->whereNull('pendiente.revisado_por')
            ->where('reporte.estado', 'REVISADO')
            ->pluck('pendiente.id');

        if ($ids->isNotEmpty()) {
            DB::table('pendientes_avance')
                ->whereIn('id', $ids)
                ->update([
                    'estado' => 'RESUELTO',
                    'estado_revision' => 'APROBADO',
                ]);
        }
    }

    public function down(): void
    {
        // La corrección conserva pendientes históricos que ya estaban resueltos.
    }
};
