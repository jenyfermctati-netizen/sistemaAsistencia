<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Asistencia;
use App\Models\PendienteAvance;
use App\Models\ReporteAvance;
use App\Models\Rol;
use App\Models\Solicitud;
use App\Models\Trabajador;
use App\Models\User;
use App\Models\Vacaciones;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_administrador_ve_el_panel_general(): void
    {
        $administrador = $this->crearUsuario('ADMINISTRADOR');

        $this->actingAs($administrador)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('CONTROL GENERAL')
            ->assertSee('Bandeja administrativa')
            ->assertSee('Todas las áreas');
    }

    public function test_el_administrador_recibe_indicadores_globales_consistentes(): void
    {
        $area = Area::create([
            'nombre' => 'Operaciones',
            'descripcion' => 'Área operativa',
            'estado' => true,
        ]);
        $administrador = $this->crearUsuario('ADMINISTRADOR');
        $puntual = $this->crearTrabajador($area, '70000101', 'Analista');
        $tardanza = $this->crearTrabajador($area, '70000102', 'Asistente');
        $inactivo = $this->crearTrabajador($area, '70000103', 'Excolaborador');
        $inactivo->update(['estado' => 'INACTIVO']);

        foreach ([
            [$puntual, 'PRESENTE'],
            [$tardanza, 'TARDANZA'],
            [$inactivo, 'PRESENTE'],
        ] as [$trabajador, $estado]) {
            Asistencia::create([
                'trabajador_id' => $trabajador->id,
                'fecha' => today(),
                'estado' => $estado,
                'procesado_en' => now(),
            ]);
        }

        Solicitud::create([
            'trabajador_id' => $puntual->id,
            'tipo' => 'PERMISO',
            'fecha_inicio' => today(),
            'motivo' => 'Permiso pendiente',
            'estado' => 'PENDIENTE',
        ]);
        Vacaciones::create([
            'trabajador_id' => $puntual->id,
            'fecha_inicio' => today()->addMonth(),
            'fecha_fin' => today()->addMonth()->addDays(4),
            'dias_solicitados' => 5,
            'estado' => 'PENDIENTE',
        ]);
        ReporteAvance::create([
            'trabajador_id' => $puntual->id,
            'fecha_presentacion' => today(),
            'estado' => 'PENDIENTE',
        ]);
        PendienteAvance::create([
            'area_id' => $area->id,
            'creado_por' => $administrador->id,
            'origen' => 'ADMINISTRADOR',
            'descripcion' => 'Actividad pendiente',
            'estado' => 'PENDIENTE',
        ]);

        $this->actingAs($administrador)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('metrics', fn (array $metrics) =>
                $metrics['trabajadores'] === 2
                && $metrics['areas'] === 1
                && $metrics['asistencias'] === 2
                && $metrics['puntuales'] === 1
                && $metrics['tardanzas'] === 1)
            ->assertViewHas('alerts', fn (array $alerts) =>
                $alerts['solicitudes'] === 1
                && $alerts['vacaciones'] === 1
                && $alerts['reportes'] === 1
                && $alerts['pendientes'] === 1)
            ->assertViewHas('areasResumen', function ($areas) {
                $resumen = $areas->first();

                return $resumen
                    && $resumen->trabajadores_activos === 2
                    && $resumen->asistencias_hoy === 2
                    && $resumen->tardanzas_hoy === 1
                    && $resumen->incidencias_hoy === 1
                    && $resumen->cobertura === 100;
            });
    }

    public function test_el_dashboard_no_confunde_registros_procesados_con_personas_que_asistieron(): void
    {
        $area = Area::create([
            'nombre' => 'Área de control',
            'descripcion' => 'Área para validar cobertura',
            'estado' => true,
        ]);
        $administrador = $this->crearUsuario('ADMINISTRADOR');
        $estados = ['PRESENTE', 'FALTA', 'SIN_REGISTRO', 'SIN_REGISTRO'];

        foreach ($estados as $indice => $estado) {
            $trabajador = $this->crearTrabajador(
                $area,
                '7000030'.($indice + 1),
                'Colaborador '.($indice + 1)
            );

            Asistencia::create([
                'trabajador_id' => $trabajador->id,
                'fecha' => today(),
                'estado' => $estado,
                'procesado_en' => now(),
            ]);
        }

        $this->actingAs($administrador)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('1 asistieron')
            ->assertSee('0 tardanzas')
            ->assertSee('3 sin asistencia')
            ->assertSee('0 justificados')
            ->assertSee('0 pendientes de registrar')
            ->assertViewHas('metrics', fn (array $metrics) =>
                $metrics['trabajadores'] === 4
                && $metrics['asistencias'] === 1
                && $metrics['puntuales'] === 1)
            ->assertViewHas('areasResumen', function ($areas) {
                $resumen = $areas->first();

                return $resumen
                    && $resumen->procesadas_hoy === 4
                    && $resumen->asistencias_hoy === 1
                    && $resumen->ausencias_hoy === 3
                    && $resumen->tardanzas_hoy === 0
                    && $resumen->sin_asistencia_hoy === 3
                    && $resumen->sin_procesar === 0
                    && $resumen->cobertura === 25;
            });
    }

    public function test_el_gerente_ve_el_panel_de_su_area(): void
    {
        $area = Area::create([
            'nombre' => 'Tecnología',
            'descripcion' => 'Área de sistemas',
            'estado' => true,
        ]);
        $trabajador = $this->crearTrabajador($area, '70000001', 'Gerente');
        $gerente = $this->crearUsuario('GERENTE', $trabajador);

        $this->actingAs($gerente)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('SUPERVISIÓN OPERATIVA')
            ->assertSee('Tecnología')
            ->assertSee('Trabajo por revisar');
    }

    public function test_el_trabajador_ve_su_resumen_personal(): void
    {
        $area = Area::create([
            'nombre' => 'Operaciones',
            'descripcion' => 'Área operativa',
            'estado' => true,
        ]);
        $trabajador = $this->crearTrabajador($area, '70000002', 'Analista');
        $usuario = $this->crearUsuario('TRABAJADOR', $trabajador);

        $this->actingAs($usuario)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('ESPACIO PERSONAL')
            ->assertSee('Aún sin procesar')
            ->assertSee('Operaciones');
    }

    public function test_el_trabajador_recibe_solo_sus_datos_del_mes_y_su_estado_actual(): void
    {
        $area = Area::create([
            'nombre' => 'Logística',
            'descripcion' => 'Área logística',
            'estado' => true,
        ]);
        $trabajador = $this->crearTrabajador($area, '70000201', 'Operador');
        $otroTrabajador = $this->crearTrabajador($area, '70000202', 'Auxiliar');
        $usuario = $this->crearUsuario('TRABAJADOR', $trabajador);

        Asistencia::create([
            'trabajador_id' => $trabajador->id,
            'fecha' => today(),
            'minutos_trabajados' => 510,
            'estado' => 'TARDANZA',
            'procesado_en' => now(),
        ]);
        Asistencia::create([
            'trabajador_id' => $trabajador->id,
            'fecha' => today()->subMonth(),
            'minutos_trabajados' => 480,
            'estado' => 'FALTA',
            'procesado_en' => now()->subMonth(),
        ]);
        Asistencia::create([
            'trabajador_id' => $otroTrabajador->id,
            'fecha' => today(),
            'minutos_trabajados' => 480,
            'estado' => 'PRESENTE',
            'procesado_en' => now(),
        ]);

        Solicitud::create([
            'trabajador_id' => $trabajador->id,
            'tipo' => 'PERMISO',
            'fecha_inicio' => today(),
            'motivo' => 'Solicitud propia',
            'estado' => 'PENDIENTE',
        ]);
        Solicitud::create([
            'trabajador_id' => $otroTrabajador->id,
            'tipo' => 'PERMISO',
            'fecha_inicio' => today(),
            'motivo' => 'Solicitud ajena',
            'estado' => 'PENDIENTE',
        ]);

        Vacaciones::create([
            'trabajador_id' => $trabajador->id,
            'fecha_inicio' => today()->subMonths(2),
            'fecha_fin' => today()->subMonth(),
            'dias_solicitados' => 5,
            'estado' => 'APROBADA',
        ]);
        $vacacionVigente = Vacaciones::create([
            'trabajador_id' => $trabajador->id,
            'fecha_inicio' => today()->addMonth(),
            'fecha_fin' => today()->addMonth()->addDays(4),
            'dias_solicitados' => 5,
            'estado' => 'PROGRAMADA',
        ]);
        ReporteAvance::create([
            'trabajador_id' => $trabajador->id,
            'fecha_presentacion' => today(),
            'estado' => 'OBSERVADO',
        ]);

        $this->actingAs($usuario)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Observado: requiere corrección')
            ->assertViewHas('metrics', fn (array $metrics) =>
                $metrics['presentes'] === 1
                && $metrics['tardanzas'] === 1
                && $metrics['incidencias'] === 0
                && $metrics['horas'] === '8 h 30 min')
            ->assertViewHas('solicitudesPendientes', 1)
            ->assertViewHas('vacacionActiva', fn ($vacacion) =>
                $vacacion?->is($vacacionVigente))
            ->assertViewHas('asistenciasRecientes', fn ($asistencias) =>
                $asistencias->count() === 1
                && $asistencias->first()->trabajador_id === $trabajador->id);
    }

    public function test_el_gerente_solo_ve_y_gestiona_informacion_de_su_equipo(): void
    {
        $areaGerente = Area::create([
            'nombre' => 'Tecnología',
            'descripcion' => 'Área supervisada',
            'estado' => true,
        ]);
        $otraArea = Area::create([
            'nombre' => 'Finanzas',
            'descripcion' => 'Área externa',
            'estado' => true,
        ]);

        $trabajadorGerente = $this->crearTrabajador($areaGerente, '70000010', 'Gerente');
        $trabajadorEquipo = $this->crearTrabajador($areaGerente, '70000011', 'Colaborador');
        $trabajadorExterno = $this->crearTrabajador($otraArea, '70000012', 'Externo');
        $gerente = $this->crearUsuario('GERENTE', $trabajadorGerente);

        foreach ([$trabajadorEquipo, $trabajadorExterno] as $trabajador) {
            Asistencia::create([
                'trabajador_id' => $trabajador->id,
                'fecha' => today(),
                'estado' => 'PRESENTE',
                'procesado_en' => now(),
            ]);

            Solicitud::create([
                'trabajador_id' => $trabajador->id,
                'tipo' => 'PERMISO',
                'fecha_inicio' => today(),
                'motivo' => 'Solicitud de '.$trabajador->apellidos,
                'estado' => 'PENDIENTE',
            ]);

            ReporteAvance::create([
                'trabajador_id' => $trabajador->id,
                'fecha_presentacion' => today(),
                'descripcion' => 'Reporte de '.$trabajador->apellidos,
                'estado' => 'PENDIENTE',
            ]);
        }

        Solicitud::create([
            'trabajador_id' => $trabajadorGerente->id,
            'tipo' => 'PERMISO',
            'fecha_inicio' => today(),
            'motivo' => 'Solicitud personal del gerente',
            'estado' => 'PENDIENTE',
        ]);

        $this->actingAs($gerente)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('metrics', fn (array $metrics) =>
                $metrics['trabajadores'] === 2 && $metrics['asistencias'] === 1)
            ->assertViewHas('alerts', fn (array $alerts) =>
                $alerts['solicitudes'] === 1 && $alerts['reportes'] === 1);

        $this->actingAs($gerente)
            ->get(route('asistencias.index'))
            ->assertOk()
            ->assertSee('Colaborador')
            ->assertDontSee('Externo');

        $this->actingAs($gerente)
            ->get(route('solicitudes.index'))
            ->assertOk()
            ->assertSee('Solicitud de Colaborador')
            ->assertDontSee('Solicitud personal del gerente')
            ->assertDontSee('Solicitud de Externo');

        $this->actingAs($gerente)
            ->get(route('reportesAvance.index'))
            ->assertOk()
            ->assertSee('Colaborador')
            ->assertDontSee('Externo');

        $solicitudExterna = Solicitud::where('trabajador_id', $trabajadorExterno->id)->firstOrFail();

        $this->actingAs($gerente)
            ->patch(route('solicitudes.revisar', $solicitudExterna), [
                'decision' => 'APROBADA',
            ])
            ->assertForbidden();

        $solicitudEquipo = Solicitud::where('trabajador_id', $trabajadorEquipo->id)->firstOrFail();

        $this->actingAs($gerente)
            ->patch(route('solicitudes.revisar', $solicitudEquipo), [
                'decision' => 'APROBADA',
            ])
            ->assertRedirect(route('solicitudes.index'));

        $this->assertDatabaseHas('solicitudes', [
            'id' => $solicitudEquipo->id,
            'estado' => 'APROBADA',
            'revisado_por' => $gerente->id,
        ]);

        $reporteExterno = ReporteAvance::where('trabajador_id', $trabajadorExterno->id)->firstOrFail();

        $this->actingAs($gerente)
            ->patch(route('reportesAvance.revisar', $reporteExterno), [
                'estado' => 'REVISADO',
            ])
            ->assertForbidden();

        $reporteEquipo = ReporteAvance::where('trabajador_id', $trabajadorEquipo->id)->firstOrFail();

        $this->actingAs($gerente)
            ->patch(route('reportesAvance.revisar', $reporteEquipo), [
                'estado' => 'REVISADO',
            ])
            ->assertRedirect(route('reportesAvance.index'));

        $this->assertDatabaseHas('reportes_avance', [
            'id' => $reporteEquipo->id,
            'estado' => 'REVISADO',
            'revisado_por' => $gerente->id,
        ]);
    }

    public function test_un_gerente_sin_area_no_recibe_datos_globales(): void
    {
        $area = Area::create([
            'nombre' => 'Operaciones',
            'descripcion' => 'Área operativa',
            'estado' => true,
        ]);
        $trabajador = $this->crearTrabajador($area, '70000020', 'Operador');
        $gerente = $this->crearUsuario('GERENTE');

        Asistencia::create([
            'trabajador_id' => $trabajador->id,
            'fecha' => today(),
            'estado' => 'PRESENTE',
            'procesado_en' => now(),
        ]);

        $this->actingAs($gerente)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Sin área vinculada')
            ->assertViewHas('metrics', fn (array $metrics) =>
                $metrics['trabajadores'] === 0 && $metrics['asistencias'] === 0)
            ->assertViewHas('areasResumen', fn ($areas) => $areas->isEmpty());
    }

    private function crearUsuario(string $rolNombre, ?Trabajador $trabajador = null): User
    {
        $rol = Rol::create([
            'nombre' => $rolNombre,
            'estado' => true,
        ]);

        return User::create([
            'trabajador_id' => $trabajador?->id,
            'rol_id' => $rol->id,
            'name' => $trabajador?->nombres ?? ucfirst(strtolower($rolNombre)),
            'email' => strtolower($rolNombre).'@example.com',
            'password' => 'password123',
            'estado' => true,
        ]);
    }

    private function crearTrabajador(Area $area, string $dni, string $cargo): Trabajador
    {
        return Trabajador::create([
            'area_id' => $area->id,
            'dni' => $dni,
            'nombres' => 'Usuario',
            'apellidos' => $cargo,
            'tipo_vinculo' => 'CONTRATADO',
            'cargo' => $cargo,
            'estado' => 'ACTIVO',
        ]);
    }
}
