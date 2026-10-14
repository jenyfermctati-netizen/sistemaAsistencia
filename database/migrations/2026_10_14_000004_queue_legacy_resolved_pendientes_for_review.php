<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('pendientes_avance')
            ->where('estado', 'RESUELTO')
            ->where('estado_revision', 'APROBADO')
            ->whereNull('revisado_por')
            ->update([
                'estado' => 'PENDIENTE',
                'estado_revision' => 'POR_REVISAR',
            ]);
    }

    public function down(): void
    {
        DB::table('pendientes_avance')
            ->where('estado', 'PENDIENTE')
            ->where('estado_revision', 'POR_REVISAR')
            ->whereNull('revisado_por')
            ->whereNotNull('resuelto_por')
            ->update([
                'estado' => 'RESUELTO',
                'estado_revision' => 'APROBADO',
            ]);
    }
};
