<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $horariosActivos = DB::table('horarios')->where('estado', true)->pluck('id');

        if ($horariosActivos->isEmpty()) {
            return;
        }

        DB::table('horario_dias')
            ->whereIn('horario_id', $horariosActivos)
            ->where('dia_semana', 6)
            ->update([
                'es_laborable' => true,
                'hora_entrada' => '08:20:00',
                'hora_salida_refrigerio' => null,
                'hora_retorno_refrigerio' => null,
                'hora_salida' => '13:00:00',
                'tolerancia_minutos' => 20,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        $horariosActivos = DB::table('horarios')->where('estado', true)->pluck('id');

        if ($horariosActivos->isEmpty()) {
            return;
        }

        DB::table('horario_dias')
            ->whereIn('horario_id', $horariosActivos)
            ->where('dia_semana', 6)
            ->update([
                'hora_entrada' => '08:20:00',
                'hora_salida_refrigerio' => '13:00:00',
                'hora_retorno_refrigerio' => '14:00:00',
                'hora_salida' => '19:00:00',
                'tolerancia_minutos' => 20,
                'updated_at' => now(),
            ]);
    }
};
