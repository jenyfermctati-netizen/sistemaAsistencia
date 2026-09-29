<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('trabajadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained('areas')->restrictOnDelete();
            $table->string('codigo_biometrico', 50)->nullable()->unique();
            $table->string('dni', 15)->unique();
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->enum('tipo_vinculo', ['CONTRATADO','LOCADOR']);
            $table->string('cargo', 150)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->date('fecha_ingreso')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->enum('estado', ['ACTIVO','INACTIVO'])->default('ACTIVO');
            $table->timestamps();
            $table->index(['area_id', 'estado', 'tipo_vinculo'], 'ix_trabajadores_area_estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trabajadores');
    }
};
