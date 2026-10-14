<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Rol;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActualizacionAsincronaTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_envio_asincrono_de_una_solicitud_devuelve_json_y_la_registra(): void
    {
        [$usuario, $trabajador] = $this->crearUsuarioTrabajador('TRABAJADOR');

        $response = $this->actingAs($usuario)
            ->postJson(route('solicitudes.store'), [
                'tipo' => 'PERMISO',
                'fecha_inicio' => '2026-10-15',
                'hora_inicio' => '09:00',
                'hora_fin' => '11:00',
                'motivo' => 'Cita médica programada.',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Solicitud registrada correctamente.')
            ->assertJsonPath('refresh_url', route('solicitudes.index'));

        $this->assertDatabaseHas('solicitudes', [
            'trabajador_id' => $trabajador->id,
            'tipo' => 'PERMISO',
            'estado' => 'PENDIENTE',
        ]);

        $this->actingAs($usuario)
            ->get(route('solicitudes.index'))
            ->assertOk()
            ->assertSee('data-live-region="solicitudes"', false)
            ->assertSee('data-live-poll="5000"', false);
    }

    public function test_el_envio_asincrono_de_una_marcacion_devuelve_json_y_la_registra(): void
    {
        [$administrador] = $this->crearUsuarioTrabajador('ADMINISTRADOR');
        [, $trabajador] = $this->crearUsuarioTrabajador('TRABAJADOR', '70000002');

        $response = $this->actingAs($administrador)
            ->postJson(route('marcaciones.store'), [
                'trabajador_id' => $trabajador->id,
                'fecha_hora' => '2026-10-14T08:05',
                'tipo' => 'ENTRADA',
                'motivo_manual' => 'Regularización autorizada.',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Marcación manual registrada correctamente.')
            ->assertJsonPath('refresh_url', route('marcaciones.index'));

        $this->assertDatabaseHas('marcaciones', [
            'trabajador_id' => $trabajador->id,
            'tipo' => 'ENTRADA',
            'origen' => 'MANUAL',
            'anulada' => false,
        ]);

        $this->actingAs($administrador)
            ->get(route('marcaciones.index'))
            ->assertOk()
            ->assertSee('data-live-region="marcaciones"', false)
            ->assertSee('data-live-poll="5000"', false);
    }

    private function crearUsuarioTrabajador(string $rolNombre, string $dni = '70000001'): array
    {
        $area = Area::firstOrCreate(
            ['nombre' => 'Área de prueba'],
            ['estado' => true]
        );
        $rol = Rol::firstOrCreate(
            ['nombre' => $rolNombre],
            ['estado' => true]
        );
        $trabajador = Trabajador::create([
            'area_id' => $area->id,
            'dni' => $dni,
            'nombres' => 'Usuario',
            'apellidos' => $rolNombre,
            'tipo_vinculo' => 'CONTRATADO',
            'estado' => 'ACTIVO',
        ]);
        $usuario = User::create([
            'trabajador_id' => $trabajador->id,
            'rol_id' => $rol->id,
            'name' => "Usuario {$rolNombre}",
            'email' => strtolower($rolNombre).$trabajador->id.'@example.com',
            'password' => 'secret',
            'estado' => true,
        ]);

        return [$usuario, $trabajador];
    }
}
