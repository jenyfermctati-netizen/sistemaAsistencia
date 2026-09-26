<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('vacaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trabajador_id')->constrained('trabajadores')->restrictOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->unsignedSmallInteger('dias_solicitados');
            $table->enum('estado', ['PENDIENTE','APROBADA','PROGRAMADA','EN_CURSO','GOZADA','RECHAZADA','CANCELADA'])->default('PENDIENTE');
            $table->string('observacion', 500)->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('revisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('comentario_revision', 500)->nullable();
            $table->dateTime('fecha_revision')->nullable();
            $table->timestamps();
            $table->index(['trabajador_id', 'fecha_inicio', 'fecha_fin', 'estado'],'ix_vacaciones_trabajador_fechas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacaciones');
    }
};
