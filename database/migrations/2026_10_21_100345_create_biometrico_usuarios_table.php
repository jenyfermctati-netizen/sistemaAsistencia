<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('biometrico_usuarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispositivo_id')->constrained('dispositivos_biometricos')->cascadeOnDelete();
            $table->foreignId('trabajador_id')->nullable()->constrained('trabajadores')->nullOnDelete();
            $table->string('pin', 50);
            $table->string('nombre_dispositivo', 150)->nullable();
            $table->string('privilegio', 20)->nullable();
            $table->string('tarjeta', 50)->nullable();
            $table->enum('estado', ['SINCRONIZADO','PENDIENTE','SOLO_DISPOSITIVO','CONFLICTO'])->default('PENDIENTE');
            $table->timestamp('ultima_verificacion')->nullable();
            $table->timestamps();

            $table->unique(['dispositivo_id','pin']);
            $table->index(['trabajador_id','estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biometrico_usuarios');
    }
};
