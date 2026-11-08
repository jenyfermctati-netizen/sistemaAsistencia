<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trabajador_id')
                ->constrained('trabajadores')
                ->restrictOnDelete();
            $table->foreignId('horario_id')
                ->nullable()
                ->constrained('horarios')
                ->nullOnDelete();
            $table->date('fecha');
            $table->enum('tipo_control_aplicado', ['OBLIGATORIO','REFERENCIAL'])->nullable();
            $table->time('hora_programada_entrada')->nullable();
            $table->time('hora_programada_salida')->nullable();
            $table->unsignedSmallInteger('tolerancia_aplicada')->default(0);
            $table->foreignId('marcacion_entrada_id')->nullable()
                ->constrained('marcaciones')
                ->nullOnDelete();
            $table->foreignId('marcacion_salida_id')
                ->nullable()
                ->constrained('marcaciones')
                ->nullOnDelete();
            $table->time('hora_entrada')->nullable();
            $table->time('hora_salida')->nullable();
            $table->unsignedInteger('minutos_trabajados')->default(0);
            $table->unsignedInteger('minutos_tardanza')->default(0);
            $table->enum('estado', ['PRESENTE','TARDANZA','FALTA','FJ','TJ','JUSTIFICADO','CAMPO','VACACIONES','PERMISO','FERIADO','NO_LABORABLE','SIN_REGISTRO']);
            $table->string('observacion', 500)->nullable();
            $table->timestamp('procesado_en')->useCurrent();
            $table->timestamps();
            $table->unique(['trabajador_id', 'fecha'], 'uq_asistencias_trabajador_fecha');
            $table->index(['fecha', 'estado'], 'ix_asistencias_fecha_estado');

            $table->time('hora_programada_salida_refrigerio')->nullable();
            $table->time('hora_programada_retorno_refrigerio')->nullable();
            $table->foreignId('marcacion_salida_refrigerio_id')
                ->nullable()
                ->constrained('marcaciones')
                ->nullOnDelete();
            $table->foreignId('marcacion_retorno_refrigerio_id')
                ->nullable()
                ->constrained('marcaciones')
                ->nullOnDelete();
            $table->time('hora_salida_refrigerio')->nullable();
            $table->time('hora_retorno_refrigerio')->nullable();

            $table->boolean('retorno_refrigerio_estimado')
                ->default(false);
            $table->boolean('salida_estimada')
                ->default(false);
            $table->timestamp('cerrada_en')
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};
