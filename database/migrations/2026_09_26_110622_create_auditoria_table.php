<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('auditoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('accion', 50);
            $table->string('tabla', 100);
            $table->string('registro_id', 100)->nullable();
            $table->json('valores_anteriores')->nullable();
            $table->json('valores_nuevos')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at'], 'ix_auditorias_usuario_fecha');
            $table->index(['tabla', 'registro_id'], 'ix_auditorias_tabla_registro');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria');
    }
};
