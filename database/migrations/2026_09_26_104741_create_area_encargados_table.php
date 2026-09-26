<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('area_encargados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained('areas')->cascadeOnDelete();
            $table->foreignId('trabajador_id')->constrained('trabajadores')->restrictOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->boolean('estado')->default(true);
            $table->timestamps();
            $table->index(['area_id', 'estado', 'fecha_inicio', 'fecha_fin'], 'ix_area_encargados_area_estado');
            $table->index(['trabajador_id', 'estado'], 'ix_area_encargados_trabajador');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('area_encargados');
    }
};
