<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('reportes_avance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trabajador_id')->constrained('trabajadores')->restrictOnDelete();
            $table->date('periodo_inicio');
            $table->date('periodo_fin');
            $table->date('fecha_presentacion');
            $table->text('descripcion');
            $table->decimal('porcentaje_avance', 5, 2)->nullable();
            $table->string('archivo', 500)->nullable();
            $table->enum('estado', ['PENDIENTE','REVISADO','OBSERVADO'])->default('PENDIENTE');
            $table->foreignId('revisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('comentario_revision', 500)->nullable();
            $table->dateTime('fecha_revision')->nullable();
            $table->timestamps();
            $table->index(['trabajador_id', 'periodo_inicio', 'periodo_fin'], 'ix_reportes_trabajador_periodo');
            $table->index('estado', 'ix_reportes_estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes_avance');
    }
};
