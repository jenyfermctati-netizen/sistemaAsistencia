<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('asignaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trabajador_id')
                ->constrained('trabajadores')
                ->restrictOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('lugar', 250)->nullable();
            $table->text('actividad');
            $table->enum('estado', ['PROGRAMADA','ACTIVA','FINALIZADA','CANCELADA'])->default('PROGRAMADA');
            $table->foreignId('autorizado_por')->constrained('users')->restrictOnDelete();
            $table->string('observacion', 500)->nullable();
            $table->timestamps();
            $table->index(['trabajador_id', 'fecha_inicio', 'fecha_fin', 'estado'], 'ix_campo_trabajador_fechas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asignaciones');
    }
};
