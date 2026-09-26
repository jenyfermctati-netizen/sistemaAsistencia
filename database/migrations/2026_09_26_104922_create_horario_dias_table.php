<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horario_dias', function (Blueprint $table) {
            $table->id();

            $table->foreignId('horario_id')
                ->constrained('horarios')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('dia_semana');
            $table->boolean('es_laborable')->default(true);
            $table->time('hora_entrada')->nullable();
            $table->time('hora_salida')->nullable();
            $table->unsignedSmallInteger('tolerancia_minutos')->default(0);
            $table->timestamps();
            $table->unique(['horario_id', 'dia_semana'], 'uq_horario_dias');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horario_dias');
    }
};
