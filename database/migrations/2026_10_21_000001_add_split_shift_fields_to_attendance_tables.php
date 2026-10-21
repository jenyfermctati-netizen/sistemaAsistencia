<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('horario_dias', function (Blueprint $table) {
            $table->time('hora_salida_refrigerio')->nullable()->after('hora_entrada');
            $table->time('hora_retorno_refrigerio')->nullable()->after('hora_salida_refrigerio');
        });

        Schema::table('asistencias', function (Blueprint $table) {
            $table->time('hora_programada_salida_refrigerio')->nullable()->after('hora_programada_entrada');
            $table->time('hora_programada_retorno_refrigerio')->nullable()->after('hora_programada_salida_refrigerio');
            $table->foreignId('marcacion_salida_refrigerio_id')
                ->nullable()
                ->after('marcacion_entrada_id')
                ->constrained('marcaciones')
                ->nullOnDelete();
            $table->foreignId('marcacion_retorno_refrigerio_id')
                ->nullable()
                ->after('marcacion_salida_refrigerio_id')
                ->constrained('marcaciones')
                ->nullOnDelete();
            $table->time('hora_salida_refrigerio')->nullable()->after('hora_entrada');
            $table->time('hora_retorno_refrigerio')->nullable()->after('hora_salida_refrigerio');
        });

        $horariosActivos = DB::table('horarios')->where('estado', true)->pluck('id');

        if ($horariosActivos->isNotEmpty()) {
            DB::table('horario_dias')
                ->whereIn('horario_id', $horariosActivos)
                ->where('es_laborable', true)
                ->update([
                    'hora_entrada' => '08:20:00',
                    'hora_salida_refrigerio' => '13:00:00',
                    'hora_retorno_refrigerio' => '14:00:00',
                    'hora_salida' => '19:00:00',
                    'tolerancia_minutos' => 20,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropConstrainedForeignId('marcacion_salida_refrigerio_id');
            $table->dropConstrainedForeignId('marcacion_retorno_refrigerio_id');
            $table->dropColumn([
                'hora_programada_salida_refrigerio',
                'hora_programada_retorno_refrigerio',
                'hora_salida_refrigerio',
                'hora_retorno_refrigerio',
            ]);
        });

        Schema::table('horario_dias', function (Blueprint $table) {
            $table->dropColumn([
                'hora_salida_refrigerio',
                'hora_retorno_refrigerio',
            ]);
        });
    }
};
