<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ControlAsistenciaSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['codigo' => 'SUPERADMIN', 'ambito' => 'GLOBAL', 'nombre' => 'Superadministrador', 'descripcion' => 'Administra la plataforma CONNEXT y sus empresas'],
            ['codigo' => 'GERENTE', 'ambito' => 'EMPRESA', 'nombre' => 'Gerente', 'descripcion' => 'Consulta información general, indicadores y reportes'],
            ['codigo' => 'ADMINISTRADOR', 'ambito' => 'EMPRESA', 'nombre' => 'Administrador', 'descripcion' => 'Gestiona personal, asistencia y configuraciones de su empresa'],
            ['codigo' => 'TRABAJADOR', 'ambito' => 'EMPRESA', 'nombre' => 'Trabajador', 'descripcion' => 'Consulta información personal y registra solicitudes'],
        ];

        foreach ($roles as $rol) {
            DB::table('roles')->updateOrInsert(
                ['codigo' => $rol['codigo']],
                $rol + ['estado' => true, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
