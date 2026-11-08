<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actividades_reporte', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporte_avance_id')->constrained('reportes_avance')->cascadeOnDelete();
            $table->string('descripcion', 500);
            $table->string('archivo', 500)->nullable();
            $table->timestamps();

            $table->index('reporte_avance_id', 'ix_actividades_reporte');

            $table->foreignId('asignacion_campo_id')->nullable()
                ->constrained('asignaciones')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividades_reporte');
    }
};
