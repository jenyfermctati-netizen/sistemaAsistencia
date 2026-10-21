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
        Schema::create('biometrico_comandos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispositivo_id')->constrained('dispositivos_biometricos')->cascadeOnDelete();
            $table->string('tipo', 50);
            $table->text('comando');
            $table->enum('estado', ['PENDIENTE','ENVIADO','COMPLETADO','ERROR'])->default('PENDIENTE');
            $table->integer('codigo_respuesta')->nullable();
            $table->text('respuesta')->nullable();
            $table->unsignedTinyInteger('intentos')->default(0);
            $table->timestamp('enviado_en')->nullable();
            $table->timestamp('respondido_en')->nullable();
            $table->timestamps();

            $table->index(['dispositivo_id','estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biometrico_comandos');
    }
};
