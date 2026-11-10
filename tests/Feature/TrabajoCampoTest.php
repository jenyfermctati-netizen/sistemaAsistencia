<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\AsignacionCampo;
use App\Models\Asistencia;
use App\Models\Empresa;
use App\Models\Horario;
use App\Models\HorarioDia;
use App\Models\Marcacion;
use App\Models\MarcacionCampo;
use App\Models\Rol;
use App\Models\Trabajador;
use App\Models\TrabajadorHorario;
use App\Models\User;
use App\Support\EmpresaContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TrabajoCampoTest extends TestCase
{
    use RefreshDatabase;

    private User $administrador;

    private User $usuarioTrabajador;

    private Trabajador $trabajador;

    private Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-03 08:30:00');
        Storage::fake('local');

        $this->empresa = Empresa::create([
            'razon_social' => 'Empresa de prueba S.A.C.',
            'ruc' => '20131312955',
            'estado' => Empresa::ACTIVA,
            'zona_horaria' => 'America/Lima',
        ]);
        app(EmpresaContext::class)->activate($this->empresa);

        $rolAdministrador = Rol::create([
            'codigo' => 'ADMINISTRADOR',
            'ambito' => 'EMPRESA',
            'nombre' => 'Administrador',
            'estado' => true,
        ]);
        $rolTrabajador = Rol::create([
            'codigo' => 'TRABAJADOR',
            'ambito' => 'EMPRESA',
            'nombre' => 'Trabajador',
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
        app(EmpresaContext::class)->clear();
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
            ->post(route('asignaciones-campo.marcar', $asignacion), [
                'evidencia' => $this->evidencia(),
            ])
            ->assertRedirect(route('asignaciones-campo.index'));

        $remota = MarcacionCampo::sole();
        $this->assertSame('PENDIENTE', $remota->estado);
        $this->assertNotNull($remota->evidencia_path);
        Storage::disk('local')->assertExists($remota->evidencia_path);
        $this->assertDatabaseCount('marcaciones', 0);
        $this->assertDatabaseCount('asistencias', 0);

        $this->actingAs($this->usuarioTrabajador)
            ->get(route('marcaciones-campo.evidencia', $remota))
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');

        $this->actingAs($this->administrador)
            ->get(route('marcaciones-campo.evidencia', $remota))
            ->assertOk();

        $this->actingAs($this->usuarioTrabajador)
            ->get(route('asignaciones-campo.index'))
            ->assertOk()
            ->assertSee('Marcaciones aprobadas');

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
            ->post(route('asignaciones-campo.marcar', $asignacion), [
                'evidencia' => $this->evidencia(),
            ]);

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

    public function test_foto_es_obligatoria_para_la_marcacion_remota(): void
    {
        $asignacion = $this->crearAsignacion();

        $this->actingAs($this->usuarioTrabajador)
            ->post(route('asignaciones-campo.marcar', $asignacion))
            ->assertSessionHasErrors('evidencia');

        $this->assertDatabaseCount('marcaciones_campo', 0);
    }

    public function test_historial_trabajador_pagina_dias_completos_con_cantidades_y_sin_repetir_marcas(): void
    {
        $asignacion = $this->crearAsignacion();
        for ($dia = 1; $dia <= 8; $dia++) {
            foreach (['08:00:00', '17:00:00'] as $hora) {
                MarcacionCampo::create([
                    'asignacion_campo_id' => $asignacion->id,
                    'trabajador_id' => $this->trabajador->id,
                    'fecha_hora' => sprintf('2026-10-%02d %s', $dia, $hora),
                    'tipo' => $hora === '08:00:00' ? 'ENTRADA' : 'SALIDA',
                    'estado' => 'APROBADA',
                    'registrado_por' => $this->usuarioTrabajador->id,
                    'evidencia_path' => 'foto.jpg',
                ]);
            }
        }
        $respuesta = $this->actingAs($this->usuarioTrabajador)
            ->get(route('asignaciones-campo.index'))->assertOk()
            ->assertViewHas('resumenCampo', ['total' => 16, 'evidencias' => 16, 'pendientes' => 0])
            ->assertViewHas('marcacionesPorFecha', fn ($dias) => $dias->count() === 7
                && $dias->keys()->first() === '2026-10-08'
                && $dias->every(fn ($marcas) => $marcas->count() === 2));
        $this->assertSame(14, substr_count($respuesta->getContent(), 'data-worker-mark='));

        $this->get(route('asignaciones-campo.index', ['historial_page' => 2]))->assertOk()
            ->assertViewHas('marcacionesPorFecha', fn ($dias) => $dias->count() === 1 && $dias->keys()->first() === '2026-10-01');
    }

    public function test_vistas_del_trabajador_comparten_estilo_y_muestran_un_solo_aviso(): void
    {
        $this->actingAs($this->usuarioTrabajador);
        foreach (['dashboard', 'asignaciones-campo.index', 'solicitudes.index', 'vacaciones.index', 'reportesAvance.index', 'password.edit'] as $ruta) {
            $respuesta = $this->withSession(['success' => 'Aviso de prueba de interfaz'])
                ->get(route($ruta))->assertOk()->assertSee('css/worker-interface.css');
            $this->assertSame(1, substr_count($respuesta->getContent(), 'Aviso de prueba de interfaz'), $ruta);
        }
        $this->actingAs($this->administrador)->get(route('dashboard'))
            ->assertOk()->assertDontSee('css/worker-interface.css');
    }

    public function test_avance_de_campo_se_vincula_a_asignacion_y_reutiliza_reporte_diario(): void
    {
        Storage::fake('local');
        $asignacion = $this->crearAsignacion();
        $this->actingAs($this->usuarioTrabajador)
            ->post(route('asignaciones-campo.avances.store', $asignacion), [
                'descripcion_avance' => 'Instalación de equipo terminada',
                'archivo_avance' => $this->evidencia(),
            ])->assertSessionHasNoErrors()->assertRedirect();
        $actividad = \App\Models\ActividadReporte::sole();
        $this->assertSame($asignacion->id, $actividad->asignacion_campo_id);
        $this->assertSame($this->trabajador->id, $actividad->reporte->trabajador_id);
        Storage::disk('local')->assertExists($actividad->archivo);
        $this->post(route('asignaciones-campo.avances.store', $asignacion), [
            'descripcion_avance' => 'Pruebas realizadas',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('reportes_avance', 1);
        $this->assertDatabaseCount('actividades_reporte', 2);
        $this->get(route('asignaciones-campo.index'))->assertOk()
            ->assertSee('Marcaciones pendientes')->assertDontSee('Avances por aprobar');
        $this->get(route('reportesAvance.index'))->assertOk()->assertSee('Trabajo en campo:')->assertSee('Instalación de equipo terminada');
    }

    public function test_avance_campo_rechaza_asignacion_ajena_y_fuera_de_periodo(): void
    {
        $asignacion = $this->crearAsignacion();
        $otro = Trabajador::create([
            'area_id' => $this->trabajador->area_id, 'dni' => '70000003',
            'nombres' => 'Otro', 'apellidos' => 'Trabajador',
            'tipo_vinculo' => 'CONTRATADO', 'estado' => 'ACTIVO',
        ]);
        $asignacion->update(['trabajador_id' => $otro->id]);
        $this->actingAs($this->usuarioTrabajador)
            ->post(route('asignaciones-campo.avances.store', $asignacion), ['descripcion_avance' => 'No autorizado'])
            ->assertForbidden();
        $asignacion->update(['trabajador_id' => $this->trabajador->id, 'estado' => 'CANCELADA']);
        $this->post(route('asignaciones-campo.avances.store', $asignacion), ['descripcion_avance' => 'Cancelada'])
            ->assertSessionHasErrors('descripcion_avance');
        $asignacion->update(['estado' => 'FINALIZADA', 'fecha_fin' => '2026-11-02', 'fecha_inicio' => '2026-11-01']);
        $this->post(route('asignaciones-campo.avances.store', $asignacion), ['descripcion_avance' => 'Fuera de fecha'])
            ->assertSessionHasErrors('descripcion_avance');
        $this->assertDatabaseCount('actividades_reporte', 0);
        $this->assertDatabaseCount('reportes_avance', 0);
    }

    public function test_asignaciones_muestra_hoy_por_defecto_y_permite_consultar_otras_fechas(): void
    {
        $hoy = $this->crearAsignacion();
        $pasada = $hoy->replicate();
        $pasada->fill(['fecha_inicio' => '2026-11-01', 'fecha_fin' => '2026-11-01', 'actividad' => 'Visita anterior'])->save();
        $futura = $hoy->replicate();
        $futura->fill(['fecha_inicio' => '2026-11-10', 'fecha_fin' => '2026-11-10', 'actividad' => 'Visita futura'])->save();

        $this->actingAs($this->usuarioTrabajador)->get(route('asignaciones-campo.index'))
            ->assertOk()->assertViewHas('soloAsignacionesHoy', true)
            ->assertViewHas('asignaciones', fn ($asignaciones) => $asignaciones->count() === 1 && $asignaciones->first()->id === $hoy->id);
        $this->get(route('asignaciones-campo.index', ['fecha' => '2026-11-01']))
            ->assertOk()->assertViewHas('soloAsignacionesHoy', false)
            ->assertViewHas('asignaciones', fn ($asignaciones) => $asignaciones->count() === 0);
        $this->get(route('asignaciones-campo.index', ['todas' => 1]))
            ->assertOk()->assertViewHas('asignaciones', fn ($asignaciones) => $asignaciones->total() === 2);
    }

    public function test_aprobacion_mueve_marcacion_de_mis_avances_al_historial(): void
    {
        $this->actingAs($this->usuarioTrabajador)
            ->post(route('asignaciones-campo.marcar', $this->crearAsignacion()), ['evidencia' => $this->evidencia()])
            ->assertSessionHasNoErrors();
        $remota = MarcacionCampo::sole();
        $this->get(route('asignaciones-campo.index'))
            ->assertOk()->assertViewHas('marcacionesPorFecha', fn ($dias) => $dias->isEmpty())
            ->assertViewHas('marcacionesSinAprobar', fn ($dias) => $dias->flatten()->count() === 1);
        $this->actingAs($this->administrador)
            ->patch(route('marcaciones-campo.revisar', $remota), ['decision' => 'APROBAR'])
            ->assertSessionHasNoErrors();
        $this->actingAs($this->usuarioTrabajador)->get(route('asignaciones-campo.index'))
            ->assertOk()->assertViewHas('marcacionesSinAprobar', fn ($dias) => $dias->isEmpty())
            ->assertViewHas('marcacionesPorFecha', fn ($dias) => $dias->flatten()->count() === 1);
    }

    public function test_perfil_permite_actualizar_solo_el_telefono_del_trabajador_autenticado(): void
    {
        $this->actingAs($this->usuarioTrabajador)
            ->patch(route('perfil.telefono.update'), [
                'telefono' => '+51987654321', 'nombres' => 'No cambiar', 'trabajador_id' => 999,
            ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('+51987654321', $this->trabajador->refresh()->telefono);
        $this->assertSame('Ana', $this->trabajador->nombres);
        $this->patch(route('perfil.telefono.update'), ['telefono' => 'abc'])
            ->assertSessionHasErrors('telefono');
        $this->assertSame('+51987654321', $this->trabajador->refresh()->telefono);
        $this->actingAs($this->administrador)
            ->patch(route('perfil.telefono.update'), ['telefono' => '987654321'])->assertForbidden();
        $this->actingAs($this->usuarioTrabajador)->get(route('dashboard'))
            ->assertOk()->assertDontSee('class="dashboard-today__identity"', false);
    }

    public function test_aviso_dashboard_identifica_dia_y_ofrece_regularizacion_con_fecha(): void
    {
        Asistencia::create([
            'trabajador_id' => $this->trabajador->id, 'fecha' => '2026-11-02',
            'estado' => 'FALTA', 'observacion' => 'No existe entrada para esta fecha.',
        ]);
        $this->actingAs($this->usuarioTrabajador)->get(route('dashboard'))
            ->assertOk()->assertSee('data-open-incidencias', false)
            ->assertSee('02/11/2026')->assertSee('No existe entrada para esta fecha.')
            ->assertSee('regularizar=2026-11-02', false)->assertSee('tipo_regularizacion=FALTA', false);
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

    private function evidencia(): UploadedFile
    {
        return UploadedFile::fake()->image('ubicacion-campo.jpg', 800, 600);
    }
}
