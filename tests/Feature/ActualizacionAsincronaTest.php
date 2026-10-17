<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Asistencia;
use App\Models\Marcacion;
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

        $marcacion = Marcacion::where('trabajador_id', $trabajador->id)->firstOrFail();

        $this->assertDatabaseHas('asistencias', [
            'trabajador_id' => $trabajador->id,
            'marcacion_entrada_id' => $marcacion->id,
            'hora_entrada' => '08:05:00',
        ]);
        $this->assertSame(
            '2026-10-14',
            Asistencia::where('trabajador_id', $trabajador->id)
                ->firstOrFail()
                ->fecha
                ->toDateString()
        );

        $this->actingAs($administrador)
            ->get(route('marcaciones.index'))
            ->assertOk()
            ->assertSee('data-live-region="marcaciones"', false)
            ->assertSee('data-live-poll="5000"', false);
    }

    public function test_marcaciones_manual_y_biometrica_recalculan_la_asistencia_y_su_origen(): void
    {
        [$administrador] = $this->crearUsuarioTrabajador('ADMINISTRADOR');
        [, $trabajador] = $this->crearUsuarioTrabajador('TRABAJADOR', '70000003');

        $this->actingAs($administrador)
            ->post(route('marcaciones.store'), [
                'trabajador_id' => $trabajador->id,
                'fecha_hora' => '2026-10-14 08:00:00',
                'tipo' => 'ENTRADA',
                'motivo_manual' => 'Ingreso registrado por administración.',
            ])
            ->assertRedirect(route('marcaciones.index'));

        $salida = Marcacion::create([
            'trabajador_id' => $trabajador->id,
            'fecha_hora' => '2026-10-14 17:00:00',
            'tipo' => 'SALIDA',
            'origen' => 'BIOMETRICO',
            'evento_externo_id' => 'BIO-70000003-20261014-1700',
            'anulada' => false,
        ]);

        $asistencia = Asistencia::with(['marcacionEntrada', 'marcacionSalida'])
            ->where('trabajador_id', $trabajador->id)
            ->whereDate('fecha', '2026-10-14')
            ->firstOrFail();

        $this->assertSame(540, $asistencia->minutos_trabajados);
        $this->assertSame('MIXTO', $asistencia->origen_marcacion);
        $this->assertSame($salida->id, $asistencia->marcacion_salida_id);

        $this->actingAs($administrador)
            ->patch(route('marcaciones.anular', $salida), [
                'motivo_anulacion' => 'Salida duplicada por el dispositivo.',
            ])
            ->assertRedirect(route('marcaciones.index'));

        $asistencia->refresh()->load(['marcacionEntrada', 'marcacionSalida']);

        $this->assertNull($asistencia->marcacion_salida_id);
        $this->assertSame(0, $asistencia->minutos_trabajados);
        $this->assertSame('MANUAL', $asistencia->origen_marcacion);
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
