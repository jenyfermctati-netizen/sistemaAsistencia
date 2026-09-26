<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trabajador_horarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trabajador_id')
                ->constrained('trabajadores')
                ->cascadeOnDelete();
            $table->foreignId('horario_id')
                ->constrained('horarios')
                ->restrictOnDelete();
            $table->enum('tipo_control', ['OBLIGATORIO','REFERENCIAL']);
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->boolean('estado')->default(true);
            $table->timestamps();
            $table->index(['trabajador_id', 'fecha_inicio', 'fecha_fin', 'estado'], 'ix_trabajador_horarios_trabajador');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trabajador_horarios');
    }
};
