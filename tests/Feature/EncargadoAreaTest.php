<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\AreaEncargado;
use App\Models\ReporteAvance;
use App\Models\Rol;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EncargadoAreaTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_administrador_designa_un_solo_encargado_vigente_por_area(): void
    {
        $administrador = $this->crearUsuario('ADMINISTRADOR');
        $rolTrabajador = $this->rol('TRABAJADOR');
        $area = Area::create(['nombre' => 'Operaciones', 'estado' => true]);

        $this->actingAs($administrador)
            ->post(route('trabajadores.store'), $this->datosTrabajador(
                $area,
                $rolTrabajador,
                '70000001',
                true
            ))
            ->assertRedirect(route('trabajadores.index'));

        $primerEncargado = Trabajador::where('dni', '70000001')->firstOrFail();

        $this->assertDatabaseHas('area_encargados', [
            'area_id' => $area->id,
            'trabajador_id' => $primerEncargado->id,
            'estado' => true,
        ]);

        $this->actingAs($administrador)
            ->post(route('trabajadores.store'), $this->datosTrabajador(
                $area,
                $rolTrabajador,
                '70000002',
                true
            ))
            ->assertRedirect(route('trabajadores.index'));

        $segundoEncargado = Trabajador::where('dni', '70000002')->firstOrFail();

        $this->assertDatabaseHas('area_encargados', [
            'trabajador_id' => $primerEncargado->id,
            'estado' => false,
        ]);
        $this->assertDatabaseHas('area_encargados', [
            'area_id' => $area->id,
            'trabajador_id' => $segundoEncargado->id,
            'estado' => true,
        ]);
        $this->assertSame(1, AreaEncargado::vigentes()->where('area_id', $area->id)->count());

        $this->actingAs($administrador)
            ->get(route('trabajadores.index'))
            ->assertOk()
            ->assertSee('Encargado de área')
            ->assertSee('data-encargado-area="1"', false);
    }

    public function test_el_encargado_consulta_solo_los_avances_de_su_area(): void
    {
        $area = Area::create(['nombre' => 'Tecnología', 'estado' => true]);
        $otraArea = Area::create(['nombre' => 'Finanzas', 'estado' => true]);
        [$usuarioEncargado, $encargado] = $this->crearTrabajador($area, '70000010');
        [, $companero] = $this->crearTrabajador($area, '70000011');
        [, $externo] = $this->crearTrabajador($otraArea, '70000012');

        AreaEncargado::create([
            'area_id' => $area->id,
            'trabajador_id' => $encargado->id,
            'fecha_inicio' => today(),
            'estado' => true,
        ]);

        $reporteEquipo = ReporteAvance::create([
            'trabajador_id' => $companero->id,
            'fecha_presentacion' => today(),
            'estado' => 'PENDIENTE',
        ]);
        $reporteEquipo->actividades()->create([
            'descripcion' => 'Avance exclusivo del equipo',
        ]);

        $reporteExterno = ReporteAvance::create([
            'trabajador_id' => $externo->id,
            'fecha_presentacion' => today(),
            'estado' => 'PENDIENTE',
        ]);
        $reporteExterno->actividades()->create([
            'descripcion' => 'Avance confidencial de finanzas',
        ]);

        $this->actingAs($usuarioEncargado)
            ->get(route('reportesAvance.index', ['vista' => 'equipo']))
            ->assertOk()
            ->assertViewIs('reportesAvance.admin.index')
            ->assertViewHas('puedeRevisar', false)
            ->assertSee('Avances de mi área')
            ->assertSee('Avance exclusivo del equipo')
            ->assertDontSee('Avance confidencial de finanzas')
            ->assertDontSee('Aprobar reporte');

        $this->actingAs($usuarioEncargado)
            ->patch(route('reportesAvance.revisar', $reporteEquipo), [
                'estado' => 'REVISADO',
            ])
            ->assertForbidden();
    }

    public function test_un_trabajador_sin_designacion_no_puede_activar_la_vista_del_equipo(): void
    {
        $area = Area::create(['nombre' => 'Logística', 'estado' => true]);
        [$usuario] = $this->crearTrabajador($area, '70000020');
        [, $companero] = $this->crearTrabajador($area, '70000021');

        ReporteAvance::create([
            'trabajador_id' => $companero->id,
            'fecha_presentacion' => today(),
            'estado' => 'PENDIENTE',
        ])->actividades()->create([
            'descripcion' => 'Actividad de otro trabajador',
        ]);

        $this->actingAs($usuario)
            ->get(route('reportesAvance.index', ['vista' => 'equipo']))
            ->assertOk()
            ->assertViewIs('reportesAvance.trabajador.index')
            ->assertDontSee('Actividad de otro trabajador');
    }

    public function test_un_trabajador_no_puede_administrar_designaciones(): void
    {
        $area = Area::create(['nombre' => 'Recursos Humanos', 'estado' => true]);
        [$usuario] = $this->crearTrabajador($area, '70000025');

        $this->actingAs($usuario)
            ->get(route('trabajadores.index'))
            ->assertForbidden();

        $this->actingAs($usuario)
            ->post(route('trabajadores.store'), [])
            ->assertForbidden();
    }

    public function test_la_designacion_solo_acepta_trabajadores_activos(): void
    {
        $administrador = $this->crearUsuario('ADMINISTRADOR');
        $rolGerente = $this->rol('GERENTE');
        $area = Area::create(['nombre' => 'Comercial', 'estado' => true]);

        $this->actingAs($administrador)
            ->from(route('trabajadores.index'))
            ->post(route('trabajadores.store'), $this->datosTrabajador(
                $area,
                $rolGerente,
                '70000030',
                true
            ))
            ->assertRedirect(route('trabajadores.index'))
            ->assertSessionHasErrors('encargado_area');

        $this->assertDatabaseMissing('trabajadores', ['dni' => '70000030']);
    }

    private function datosTrabajador(
        Area $area,
        Rol $rol,
        string $dni,
        bool $encargado
    ): array {
        return [
            'area_id' => $area->id,
            'dni' => $dni,
            'nombres' => 'Usuario',
            'apellidos' => $dni,
            'tipo_vinculo' => 'CONTRATADO',
            'cargo' => 'Analista',
            'estado' => 'ACTIVO',
            'email' => $dni.'@example.com',
            'rol_id' => $rol->id,
            'encargado_area' => $encargado ? 1 : 0,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
    }

    private function crearUsuario(string $rolNombre, ?Trabajador $trabajador = null): User
    {
        return User::create([
            'trabajador_id' => $trabajador?->id,
            'rol_id' => $this->rol($rolNombre)->id,
            'name' => $rolNombre.' '.$trabajador?->id,
            'email' => strtolower($rolNombre).User::count().'@example.com',
            'password' => 'password123',
            'estado' => true,
        ]);
    }

    private function crearTrabajador(Area $area, string $dni): array
    {
        $trabajador = Trabajador::create([
            'area_id' => $area->id,
            'dni' => $dni,
            'nombres' => 'Trabajador',
            'apellidos' => $dni,
            'tipo_vinculo' => 'CONTRATADO',
            'estado' => 'ACTIVO',
        ]);

        return [$this->crearUsuario('TRABAJADOR', $trabajador), $trabajador];
    }

    private function rol(string $nombre): Rol
    {
        return Rol::firstOrCreate(
            ['nombre' => $nombre],
            ['estado' => true]
        );
    }
}
