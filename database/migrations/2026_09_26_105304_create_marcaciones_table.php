<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marcaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trabajador_id')
                ->constrained('trabajadores')
                ->restrictOnDelete();
            $table->dateTime('fecha_hora');
            $table->enum('tipo', ['ENTRADA','SALIDA']);
            $table->enum('origen', ['BIOMETRICO','MANUAL']);
            $table->string('dispositivo', 100)->nullable();
            $table->string('evento_externo_id', 100)->nullable()->unique();
            $table->foreignId('registrado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('motivo_manual', 500)->nullable();
            $table->boolean('anulada')->default(false);
            $table->foreignId('anulada_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('motivo_anulacion', 500)->nullable();
            $table->dateTime('fecha_anulacion')->nullable();
            $table->timestamps();
            $table->index(['trabajador_id', 'fecha_hora'], 'ix_marcaciones_trabajador_fecha');
            $table->index('fecha_hora', 'ix_marcaciones_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marcaciones');
    }
};
