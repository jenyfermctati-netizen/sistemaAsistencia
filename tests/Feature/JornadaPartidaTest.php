<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Asistencia;
use App\Models\Horario;
use App\Models\Rol;
use App\Models\Trabajador;
use App\Models\TrabajadorHorario;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JornadaPartidaTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_contratado_registra_entrada_refrigerio_retorno_y_salida_final(): void
    {
        [$administrador, $trabajador] = $this->prepararTrabajador('CONTRATADO');

        foreach (['08:35', '13:00', '14:05', '19:00'] as $hora) {
            Carbon::setTestNow("2026-10-21 {$hora}:00");

            $this->actingAs($administrador)
                ->post(route('marcaciones.marcarAhora', $trabajador))
                ->assertRedirect(route('marcaciones.manual'));
        }

        $this->assertDatabaseCount('marcaciones', 4);
        $this->assertSame(
            ['ENTRADA', 'SALIDA', 'ENTRADA', 'SALIDA'],
            $trabajador->marcaciones()->orderBy('fecha_hora')->pluck('tipo')->all()
        );

        $asistencia = Asistencia::where('trabajador_id', $trabajador->id)->firstOrFail();

        $this->assertSame('08:35:00', $asistencia->hora_entrada);
        $this->assertSame('13:00:00', $asistencia->hora_salida_refrigerio);
        $this->assertSame('14:05:00', $asistencia->hora_retorno_refrigerio);
        $this->assertSame('19:00:00', $asistencia->hora_salida);
        $this->assertSame(560, $asistencia->minutos_trabajados);
        $this->assertSame(5, $asistencia->minutos_tardanza);
        $this->assertSame('TARDANZA', $asistencia->estado);
        $this->assertNull($asistencia->observacion);

        Carbon::setTestNow('2026-10-21 19:05:00');

        $this->actingAs($administrador)
            ->get(route('marcaciones.manual'))
            ->assertOk()
            ->assertSee('Salida refrigerio')
            ->assertSee('Retorno / entrada tarde')
            ->assertSee('13:00:00')
            ->assertSee('14:05:00');
    }

    public function test_personal_referencial_puede_trabajar_turno_manana_con_dos_marcas(): void
    {
        [$administrador, $trabajador] = $this->prepararTrabajador('LOCADOR', '70000903');

        foreach (['08:30', '13:00'] as $hora) {
            Carbon::setTestNow("2026-10-21 {$hora}:00");

            $this->actingAs($administrador)
                ->post(route('marcaciones.marcarAhora', $trabajador))
                ->assertRedirect(route('marcaciones.manual'));
        }

        $asistencia = Asistencia::where('trabajador_id', $trabajador->id)->firstOrFail();

        $this->assertSame('08:30:00', $asistencia->hora_entrada);
        $this->assertNull($asistencia->hora_salida_refrigerio);
        $this->assertNull($asistencia->hora_retorno_refrigerio);
        $this->assertSame('13:00:00', $asistencia->hora_salida);
        $this->assertSame('08:20', $asistencia->hora_programada_entrada);
        $this->assertSame('13:00', $asistencia->hora_programada_salida);
        $this->assertSame(270, $asistencia->minutos_trabajados);
        $this->assertSame('PRESENTE', $asistencia->estado);
    }

    public function test_personal_referencial_puede_trabajar_turno_tarde_con_dos_marcas(): void
    {
        [$administrador, $trabajador] = $this->prepararTrabajador('LOCADOR', '70000902');

        foreach (['14:00', '19:00'] as $hora) {
            Carbon::setTestNow("2026-10-21 {$hora}:00");

            $this->actingAs($administrador)
                ->post(route('marcaciones.marcarAhora', $trabajador))
                ->assertRedirect(route('marcaciones.manual'));
        }

        $asistencia = Asistencia::where('trabajador_id', $trabajador->id)->firstOrFail();

        $this->assertSame('14:00:00', $asistencia->hora_entrada);
        $this->assertNull($asistencia->hora_salida_refrigerio);
        $this->assertNull($asistencia->hora_retorno_refrigerio);
        $this->assertSame('19:00:00', $asistencia->hora_salida);
        $this->assertSame('14:00', $asistencia->hora_programada_entrada);
        $this->assertSame('19:00', $asistencia->hora_programada_salida);
        $this->assertSame(300, $asistencia->minutos_trabajados);
        $this->assertSame('PRESENTE', $asistencia->estado);
    }

    public function test_contratado_registra_solo_entrada_y_salida_en_sabado_de_media_jornada(): void
    {
        [$administrador, $trabajador] = $this->prepararTrabajador(
            'CONTRATADO',
            '70000904',
            6,
            false
        );

        foreach (['08:30', '13:00'] as $hora) {
            Carbon::setTestNow("2026-10-24 {$hora}:00");

            $this->actingAs($administrador)
                ->post(route('marcaciones.marcarAhora', $trabajador))
                ->assertRedirect(route('marcaciones.manual'));
        }

        Carbon::setTestNow('2026-10-24 13:05:00');

        $this->actingAs($administrador)
            ->post(route('marcaciones.marcarAhora', $trabajador))
            ->assertSessionHas('error');

        $asistencia = Asistencia::where('trabajador_id', $trabajador->id)->firstOrFail();

        $this->assertDatabaseCount('marcaciones', 2);
        $this->assertSame('08:30:00', $asistencia->hora_entrada);
        $this->assertNull($asistencia->hora_salida_refrigerio);
        $this->assertNull($asistencia->hora_retorno_refrigerio);
        $this->assertSame('13:00:00', $asistencia->hora_salida);
        $this->assertSame(270, $asistencia->minutos_trabajados);
        $this->assertSame('PRESENTE', $asistencia->estado);
    }

    private function prepararTrabajador(
        string $vinculo,
        string $dni = '70000901',
        int $diaSemana = 3,
        bool $conRefrigerio = true
    ): array {
        $area = Area::create(['nombre' => 'Operaciones', 'estado' => true]);
        $rolAdministrador = Rol::create(['nombre' => 'ADMINISTRADOR', 'estado' => true]);
        $administrador = User::create([
            'rol_id' => $rolAdministrador->id,
            'name' => 'Administrador',
            'email' => 'admin.jornada@example.com',
            'password' => 'password123',
            'estado' => true,
        ]);
        $trabajador = Trabajador::create([
            'area_id' => $area->id,
            'dni' => $dni,
            'nombres' => 'Persona',
            'apellidos' => $vinculo,
            'tipo_vinculo' => $vinculo,
            'estado' => 'ACTIVO',
        ]);
        $horario = Horario::create([
            'nombre' => 'Horario partido',
            'formato_hora' => '24',
            'estado' => true,
        ]);
        $horario->dias()->create([
            'dia_semana' => $diaSemana,
            'es_laborable' => true,
            'hora_entrada' => '08:20',
            'hora_salida_refrigerio' => $conRefrigerio ? '13:00' : null,
            'hora_retorno_refrigerio' => $conRefrigerio ? '14:00' : null,
            'hora_salida' => $conRefrigerio ? '19:00' : '13:00',
            'tolerancia_minutos' => 20,
        ]);
        TrabajadorHorario::create([
            'trabajador_id' => $trabajador->id,
            'horario_id' => $horario->id,
            'tipo_control' => $vinculo === 'CONTRATADO' ? 'OBLIGATORIO' : 'REFERENCIAL',
            'fecha_inicio' => '2026-10-01',
            'estado' => true,
        ]);

        return [$administrador, $trabajador];
    }
}
