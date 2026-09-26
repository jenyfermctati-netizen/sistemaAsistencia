<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trabajador_id')->constrained('trabajadores')->restrictOnDelete();
            $table->foreignId('asistencia_id')->nullable()->constrained('asistencias')->nullOnDelete();
            $table->enum('tipo', ['TARDANZA','FALTA','PERMISO','OMISION_MARCACION','OTRO',]);
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            $table->text('motivo');
            $table->string('archivo', 500)->nullable();
            $table->enum('estado', ['PENDIENTE','APROBADA','RECHAZADA','CANCELADA'])->default('PENDIENTE');
            $table->foreignId('revisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('comentario_revision', 500)->nullable();
            $table->dateTime('fecha_revision')->nullable();
            $table->timestamps();
            $table->index(['trabajador_id', 'estado'], 'ix_solicitudes_trabajador');
            $table->index(['estado', 'tipo'], 'ix_solicitudes_estado_tipo');
        });
    }

 
    public function down(): void
    {
        Schema::dropIfExists('solicitudes');
    }
};
