<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ControlAsistenciaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('roles')->insert([
            [
                'nombre' => 'GERENTE',
                'descripcion' => 'Consulta información general, indicadores y reportes',
                'estado' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'ADMINISTRADOR',
                'descripscion' => 'Gestiona personal, asistencia, solicitudes, vacaciones y configuraciones',
                'estado' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'TRABAJADOR',
                'descripcion' => 'Consulta información personal y registra solicitudes',
                'estado' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('areas')->insert([
            ['nombre' => 'Gerencia General', 'descripcion' => 'Área de dirección y supervisión general', 'estado' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Administración Central', 'descripcion' => 'Área administrativa de la empresa', 'estado' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'División de Ingeniería y Arquitectura', 'descripcion' => 'Área de ingeniería y arquitectura', 'estado' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Logística y Operaciones', 'descripcion' => 'Área encargada de logística y operaciones', 'estado' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Tecnología y Desarrollo', 'descripcion' => 'Área encargada de tecnología y desarrollo', 'estado' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $horarioId = DB::table('horarios')->insertGetId([
            'nombre' => 'Horario Oficina',
            'descripcion' => 'Horario principal del personal de oficina',
            'estado' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (range(1, 5) as $dia) {
            DB::table('horario_dias')->insert([
                'horario_id' => $horarioId,
                'dia_semana' => $dia,
                'es_laborable' => true,
                'hora_entrada' => '08:00:00',
                'hora_salida' => '13:00:00',
                'tolerancia_minutos' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ([6, 7] as $dia) {
            DB::table('horario_dias')->insert([
                'horario_id' => $horarioId,
                'dia_semana' => $dia,
                'es_laborable' => false,
                'hora_entrada' => null,
                'hora_salida' => null,
                'tolerancia_minutos' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
