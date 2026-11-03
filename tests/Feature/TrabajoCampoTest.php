<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\AsignacionCampo;
use App\Models\Asistencia;
use App\Models\Horario;
use App\Models\HorarioDia;
use App\Models\Marcacion;
use App\Models\MarcacionCampo;
use App\Models\Rol;
use App\Models\Trabajador;
use App\Models\TrabajadorHorario;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TrabajoCampoTest extends TestCase
{
    use RefreshDatabase;

    private User $administrador;

    private User $usuarioTrabajador;

    private Trabajador $trabajador;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-03 08:30:00');

        $rolAdministrador = Rol::create([
            'nombre' => 'ADMINISTRADOR',
            'estado' => true,
        ]);
        $rolTrabajador = Rol::create([
            'nombre' => 'TRABAJADOR',
            'estado' => true,
        ]);
        $area = Area::create([
            'nombre' => 'Operaciones',
            'estado' => true,
        ]);

        $this->trabajador = Trabajador::create([
            'area_id' => $area->id,
            'dni' => '70000001',
            'nombres' => 'Ana',
            'apellidos' => 'Campo',
            'tipo_vinculo' => 'CONTRATADO',
            'estado' => 'ACTIVO',
        ]);

        $this->administrador = User::create([
            'rol_id' => $rolAdministrador->id,
            'name' => 'Administración',
            'email' => 'admin@example.test',
            'password' => Hash::make('password'),
            'estado' => true,
        ]);
        $this->usuarioTrabajador = User::create([
            'trabajador_id' => $this->trabajador->id,
            'rol_id' => $rolTrabajador->id,
            'name' => 'Ana Campo',
            'email' => 'ana@example.test',
            'password' => Hash::make('password'),
            'estado' => true,
        ]);

        $horario = Horario::create([
            'nombre' => 'Horario de prueba',
            'estado' => true,
        ]);
        HorarioDia::create([
            'horario_id' => $horario->id,
            'dia_semana' => Carbon::now()->isoWeekday(),
            'es_laborable' => true,
            'hora_entrada' => '08:00:00',
            'hora_salida' => '17:00:00',
            'tolerancia_minutos' => 10,
        ]);
        TrabajadorHorario::create([
            'trabajador_id' => $this->trabajador->id,
            'horario_id' => $horario->id,
            'tipo_control' => 'OBLIGATORIO',
            'fecha_inicio' => '2026-01-01',
            'estado' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_trabajador_se_autoasigna_y_la_marcacion_solo_cuenta_al_ser_aprobada(): void
    {
        $this->actingAs($this->usuarioTrabajador)
            ->post(route('asignaciones-campo.store'), [
                'trabajador_id' => 999999,
                'fecha_inicio' => '2026-11-03',
                'fecha_fin' => '2026-11-03',
                'lugar' => 'Proveedor',
                'actividad' => 'Comprar materiales',
            ])
            ->assertRedirect(route('asignaciones-campo.index'));

        $asignacion = AsignacionCampo::sole();
        $this->assertSame($this->trabajador->id, $asignacion->trabajador_id);
        $this->assertSame($this->usuarioTrabajador->id, $asignacion->autorizado_por);

        $this->actingAs($this->usuarioTrabajador)
            ->post(route('asignaciones-campo.marcar', $asignacion))
            ->assertRedirect(route('asignaciones-campo.index'));

        $remota = MarcacionCampo::sole();
        $this->assertSame('PENDIENTE', $remota->estado);
        $this->assertDatabaseCount('marcaciones', 0);
        $this->assertDatabaseCount('asistencias', 0);

        $this->actingAs($this->usuarioTrabajador)
            ->get(route('asignaciones-campo.index'))
            ->assertOk()
            ->assertSee('Mis marcaciones remotas');

        $this->actingAs($this->administrador)
            ->get(route('asignaciones-campo.index'))
            ->assertOk()
            ->assertSee('Marcaciones remotas pendientes');

        $this->actingAs($this->administrador)
            ->patch(route('marcaciones-campo.revisar', $remota), [
                'decision' => 'APROBAR',
                'observacion_revision' => 'Actividad validada.',
            ])
            ->assertRedirect(route('asignaciones-campo.index'));

        $remota->refresh();
        $this->assertSame('APROBADA', $remota->estado);
        $this->assertNotNull($remota->marcacion_id);
        $this->assertSame('WEB_CAMPO', Marcacion::sole()->origen);
        $this->assertSame('CAMPO', Asistencia::sole()->estado);
    }

    public function test_marcacion_rechazada_no_crea_marcacion_efectiva(): void
    {
        $asignacion = $this->crearAsignacion();

        $this->actingAs($this->usuarioTrabajador)
            ->post(route('asignaciones-campo.marcar', $asignacion));

        $remota = MarcacionCampo::sole();

        $this->actingAs($this->administrador)
            ->patch(route('marcaciones-campo.revisar', $remota), [
                'decision' => 'RECHAZAR',
                'observacion_revision' => 'No corresponde a una salida laboral.',
            ])
            ->assertRedirect(route('asignaciones-campo.index'));

        $this->assertSame('RECHAZADA', $remota->refresh()->estado);
        $this->assertNull($remota->marcacion_id);
        $this->assertDatabaseCount('marcaciones', 0);
        $this->assertDatabaseCount('asistencias', 0);
    }

    public function test_trabajador_no_puede_marcar_la_asignacion_de_otra_persona(): void
    {
        $otro = Trabajador::create([
            'area_id' => $this->trabajador->area_id,
            'dni' => '70000002',
            'nombres' => 'Luis',
            'apellidos' => 'Externo',
            'tipo_vinculo' => 'LOCADOR',
            'estado' => 'ACTIVO',
        ]);
        $asignacionAjena = AsignacionCampo::create([
            'trabajador_id' => $otro->id,
            'fecha_inicio' => '2026-11-03',
            'fecha_fin' => '2026-11-03',
            'actividad' => 'Visita técnica',
            'estado' => 'ACTIVA',
            'autorizado_por' => $this->administrador->id,
        ]);

        $this->actingAs($this->usuarioTrabajador)
            ->post(route('asignaciones-campo.marcar', $asignacionAjena))
            ->assertForbidden();

        $this->assertDatabaseCount('marcaciones_campo', 0);
    }

    private function crearAsignacion(): AsignacionCampo
    {
        return AsignacionCampo::create([
            'trabajador_id' => $this->trabajador->id,
            'fecha_inicio' => '2026-11-03',
            'fecha_fin' => '2026-11-03',
            'lugar' => 'Cliente',
            'actividad' => 'Soporte en sitio',
            'estado' => 'ACTIVA',
            'autorizado_por' => $this->usuarioTrabajador->id,
        ]);
    }
}
