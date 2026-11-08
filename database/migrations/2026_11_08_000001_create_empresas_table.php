<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('ruc', 11)->nullable()->unique();
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });

        DB::table('empresas')->insert([
            'nombre' => 'Empresa inicial', 'estado' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
