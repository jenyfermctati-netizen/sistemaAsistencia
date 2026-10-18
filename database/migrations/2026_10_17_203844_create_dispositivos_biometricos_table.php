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
        Schema::create('dispositivos_biometricos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->default('SenseFace Principal');
            $table->string('serial', 100)->unique();
            $table->string('modelo', 100)->nullable();
            $table->string('ultima_ip', 45)->nullable();
            $table->timestamp('ultima_conexion')->nullable();
            $table->timestamp('ultima_marcacion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispositivos_biometricos');
    }
};
