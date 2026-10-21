<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Horario;
use App\Models\Rol;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HorarioGeneralTest extends TestCase
{
    use RefreshDatabase;

    public function test_guarda_un_solo_horario_y_lo_asigna_a_todos_los_trabajadores_activos(): void
    {
        $administrador = $this->crearAdministrador();
        $area = Area::create(['nombre' => 'Administracion', 'estado' => true]);

        $contratado = $this->crearTrabajador($area->id, '70000001', 'CONTRATADO');
        $locador = $this->crearTrabajador($area->id, '70000002', 'LOCADOR');

        $response = $this
            ->actingAs($administrador)
            ->post(route('trabajadores.horarios.store'), $this->datosHorario('12'));

        $response
            ->assertRedirect(route('trabajadores.horarios'))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('horarios', 1);
        $this->assertDatabaseHas('horarios', [
            'nombre' => 'Horario general',
            'formato_hora' => '12',
            'estado' => true,
        ]);
        $this->assertDatabaseCount('horario_dias', 7);
        $this->assertDatabaseHas('horario_dias', [
            'dia_semana' => 6,
            'es_laborable' => true,
            'hora_entrada' => '08:20',
            'hora_salida_refrigerio' => null,
            'hora_retorno_refrigerio' => null,
            'hora_salida' => '13:00',
        ]);

        $horario = Horario::firstOrFail();

        $this->assertDatabaseHas('trabajador_horarios', [
            'trabajador_id' => $contratado->id,
            'horario_id' => $horario->id,
            'tipo_control' => 'OBLIGATORIO',
            'estado' => true,
        ]);
        $this->assertDatabaseHas('trabajador_horarios', [
            'trabajador_id' => $locador->id,
            'horario_id' => $horario->id,
            'tipo_control' => 'REFERENCIAL',
            'estado' => true,
        ]);
    }

    public function test_editar_conserva_el_mismo_horario_y_actualiza_sus_horas(): void
    {
        $administrador = $this->crearAdministrador();

        $this->actingAs($administrador)
            ->post(route('trabajadores.horarios.store'), $this->datosHorario('12'));

        $horario = Horario::firstOrFail();
        $datos = $this->datosHorario('24');
        $datos['dias'][1]['hora_entrada'] = '09:15';
        $datos['dias'][1]['hora_salida_refrigerio'] = '13:00';
        $datos['dias'][1]['hora_retorno_refrigerio'] = '14:00';
        $datos['dias'][1]['hora_salida'] = '18:45';

        $response = $this
            ->actingAs($administrador)
            ->put(route('trabajadores.horarios.update', $horario), $datos);

        $response
            ->assertRedirect(route('trabajadores.horarios'))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('horarios', 1);
        $this->assertDatabaseHas('horarios', [
            'id' => $horario->id,
            'formato_hora' => '24',
        ]);
        $this->assertDatabaseHas('horario_dias', [
            'horario_id' => $horario->id,
            'dia_semana' => 1,
            'hora_entrada' => '09:15',
            'hora_salida_refrigerio' => '13:00',
            'hora_retorno_refrigerio' => '14:00',
            'hora_salida' => '18:45',
        ]);

        $this->actingAs($administrador)
            ->get(route('trabajadores.horarios'))
            ->assertOk()
            ->assertSee('value="09:15"', false)
            ->assertSee('value="18:45"', false)
            ->assertSee('value="24" selected', false);
    }

    public function test_permite_cambiar_el_sabado_de_jornada_completa_a_media_jornada(): void
    {
        $administrador = $this->crearAdministrador();
        $datosCompletos = $this->datosHorario('24');
        $datosCompletos['dias'][6] = [
            'dia_semana' => 6,
            'es_laborable' => '1',
            'tipo_jornada' => 'COMPLETA',
            'hora_entrada' => '08:20',
            'hora_salida_refrigerio' => '13:00',
            'hora_retorno_refrigerio' => '14:00',
            'hora_salida' => '19:00',
            'tolerancia_minutos' => 20,
        ];

        $this->actingAs($administrador)
            ->post(route('trabajadores.horarios.store'), $datosCompletos)
            ->assertRedirect(route('trabajadores.horarios'));

        $horario = Horario::firstOrFail();

        $this->actingAs($administrador)
            ->put(route('trabajadores.horarios.update', $horario), $this->datosHorario('24'))
            ->assertRedirect(route('trabajadores.horarios'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('horario_dias', [
            'horario_id' => $horario->id,
            'dia_semana' => 6,
            'es_laborable' => true,
            'hora_entrada' => '08:20',
            'hora_salida_refrigerio' => null,
            'hora_retorno_refrigerio' => null,
            'hora_salida' => '13:00',
        ]);

        $this->actingAs($administrador)
            ->get(route('trabajadores.horarios'))
            ->assertOk()
            ->assertSee('Tipo de jornada del sábado')
            ->assertSee('value="MEDIA" selected', false);
    }

    public function test_un_nuevo_trabajador_recibe_el_horario_general_automaticamente(): void
    {
        $administrador = $this->crearAdministrador();
        $area = Area::create(['nombre' => 'Operaciones', 'estado' => true]);

        $this->actingAs($administrador)
            ->post(route('trabajadores.horarios.store'), $this->datosHorario('24'));

        $response = $this->actingAs($administrador)
            ->post(route('trabajadores.store'), [
                'area_id' => $area->id,
                'dni' => '70000003',
                'nombres' => 'Nuevo',
                'apellidos' => 'Trabajador',
                'tipo_vinculo' => 'CONTRATADO',
                'fecha_ingreso' => '2026-10-14',
                'estado' => 'ACTIVO',
                'email' => 'nuevo.trabajador@example.com',
                'rol_id' => $administrador->rol_id,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response
            ->assertRedirect(route('trabajadores.index'))
            ->assertSessionHas('success');

        $trabajador = Trabajador::where('dni', '70000003')->firstOrFail();
        $this->assertDatabaseHas('trabajador_horarios', [
            'trabajador_id' => $trabajador->id,
            'horario_id' => Horario::firstOrFail()->id,
            'tipo_control' => 'OBLIGATORIO',
            'estado' => true,
        ]);
    }

    private function datosHorario(string $formato): array
    {
        $dias = [];

        foreach (range(1, 7) as $dia) {
            $laborable = $dia <= 6;
            $jornadaCompleta = $dia <= 5;
            $dias[$dia] = [
                'dia_semana' => $dia,
                'es_laborable' => $laborable ? '1' : '0',
                'tipo_jornada' => $jornadaCompleta ? 'COMPLETA' : 'MEDIA',
                'hora_entrada' => $laborable ? '08:20' : null,
                'hora_salida_refrigerio' => $jornadaCompleta ? '13:00' : null,
                'hora_retorno_refrigerio' => $jornadaCompleta ? '14:00' : null,
                'hora_salida' => $laborable ? ($jornadaCompleta ? '19:00' : '13:00') : null,
                'tolerancia_minutos' => 20,
            ];
        }

        return [
            'nombre' => 'Horario general',
            'descripcion' => 'Horario unico de la empresa',
            'formato_hora' => $formato,
            'dias' => $dias,
        ];
    }

    private function crearAdministrador(): User
    {
        $rol = Rol::create(['nombre' => 'ADMINISTRADOR', 'estado' => true]);

        return User::create([
            'rol_id' => $rol->id,
            'name' => 'Administrador',
            'email' => 'admin.horarios@example.com',
            'password' => 'secret',
            'estado' => true,
        ]);
    }

    private function crearTrabajador(int $areaId, string $dni, string $vinculo): Trabajador
    {
        return Trabajador::create([
            'area_id' => $areaId,
            'dni' => $dni,
            'nombres' => 'Trabajador',
            'apellidos' => $dni,
            'tipo_vinculo' => $vinculo,
            'estado' => 'ACTIVO',
        ]);
    }
}
