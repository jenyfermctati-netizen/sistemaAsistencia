<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuarioPrincipalSeeder extends Seeder
{

    public function run(): void
    {
        $rolAdministradorId = DB::table('roles')
            ->where('nombre', 'ADMINISTRADOR')
            ->value('id');

        if (!$rolAdministradorId) {
            throw new \Exception(
                'No existe el rol ADMINISTRADOR. Ejecuta primero ControlAsistenciaSeeder.'
            );
        }

        DB::table('users')->updateOrInsert(
            [
                'email' => 'admin@gmail.com',
            ],
            [
                'trabajador_id' => null,
                'rol_id' => $rolAdministradorId,

                'name' => 'Administrador Principal',

                'email_verified_at' => now(),

                'password' => Hash::make('password'),

                'estado' => true,
                'ultimo_acceso' => null,

                'remember_token' => null,

                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
