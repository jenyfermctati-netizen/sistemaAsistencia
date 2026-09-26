<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trabajador_feriados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')
                ->nullable()
                ->constrained('areas')
                ->nullOnDelete();
            $table->date('fecha');
            $table->string('nombre', 150);
            $table->enum('tipo', ['NACIONAL','REGIONAL','LOCAL','INSTITUCIONAL']);
            $table->string('descripcion', 500)->nullable();
            $table->boolean('estado')->default(true);
            $table->timestamps();
            $table->index(['fecha', 'estado'],'ix_feriados_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trabajador_feriados');
    }
};
