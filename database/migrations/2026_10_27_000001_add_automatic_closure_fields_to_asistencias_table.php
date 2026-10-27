<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->boolean('retorno_refrigerio_estimado')
                ->default(false)
                ->after('hora_retorno_refrigerio');
            $table->boolean('salida_estimada')
                ->default(false)
                ->after('hora_salida');
            $table->timestamp('cerrada_en')
                ->nullable()
                ->after('procesado_en');
        });
    }

    public function down(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropColumn([
                'retorno_refrigerio_estimado',
                'salida_estimada',
                'cerrada_en',
            ]);
        });
    }
};
