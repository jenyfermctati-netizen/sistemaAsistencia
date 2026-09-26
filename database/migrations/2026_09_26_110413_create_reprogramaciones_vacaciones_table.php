<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('reprogramaciones_vacaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vacaciones_id')->constrained('vacaciones')->cascadeOnDelete();
            $table->date('fecha_inicio_anterior');
            $table->date('fecha_fin_anterior');
            $table->date('fecha_inicio_nueva');
            $table->date('fecha_fin_nueva');
            $table->unsignedSmallInteger('dias_nuevos');
            $table->text('motivo');
            $table->foreignId('reprogramado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index('vacaciones_id','ix_reprogramaciones_vacaciones');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reprogramaciones_vacaciones');
    }
};
