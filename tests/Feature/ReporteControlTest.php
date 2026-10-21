<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\AreaEncargado;
use App\Models\Asistencia;
use App\Models\PendienteAvance;
use App\Models\ReporteAvance;
use App\Models\Rol;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class ReporteControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_administrador_ve_el_detalle_consolidado_de_todas_las_areas(): void
    {
        $administrador = $this->crearUsuario('ADMINISTRADOR');
        $tecnologia = Area::create(['nombre' => 'Tecnología', 'estado' => true]);
        $finanzas = Area::create(['nombre' => 'Finanzas', 'estado' => true]);
        [, $trabajadorTecnologia] = $this->crearTrabajador($tecnologia, '70000101', 'Analista TI');
        [, $trabajadorFinanzas] = $this->crearTrabajador($finanzas, '70000102', 'Analista contable');

        $this->crearInformacionSemanal($trabajadorTecnologia, 'Actividad de tecnología');
        $this->crearInformacionSemanal($trabajadorFinanzas, 'Actividad de finanzas');

        $this->actingAs($administrador)
            ->get(route('dashboard', $this->periodoDashboard()))
            ->assertOk()
            ->assertViewHas('reporteControl', fn (array $reporte) => $reporte['esAdministrador']
                && $reporte['metricas']['trabajadores'] === 2
                && $reporte['metricas']['dias_registrados'] === 2
                && $reporte['metricas']['actividades'] === 2
                && $reporte['metricas']['finalizadas'] === 2)
            ->assertSee('Asistencia, avances y tareas')
            ->assertSee('Actividades finalizadas')
            ->assertSee('Pendientes del área')
            ->assertSee('✓ Asistencia')
            ->assertSee('Detalle administrativo')
            ->assertSee('Tecnología')
            ->assertSee('Finanzas')
            ->assertSee('08:05')
            ->assertSee('Actividad de tecnología')
            ->assertSee('Tarea finalizada por Analista TI');
    }

    public function test_el_gerente_ve_el_detalle_general_y_el_encargado_solo_el_resumen_de_su_area(): void
    {
        $area = Area::create(['nombre' => 'Operaciones', 'estado' => true]);
        $otraArea = Area::create(['nombre' => 'Comercial', 'estado' => true]);
        [$gerente, $trabajadorGerente] = $this->crearTrabajador($area, '70000201', 'Gerente');
        [, $trabajadorEquipo] = $this->crearTrabajador($area, '70000202', 'Operador');
        [, $trabajadorExterno] = $this->crearTrabajador($otraArea, '70000203', 'Vendedor');
        $gerente->update([
            'rol_id' => $this->rol('GERENTE')->id,
            'name' => 'Gerente de operaciones',
            'email' => 'gerente@example.com',
        ]);
        [$encargado, $trabajadorEncargado] = $this->crearTrabajador($area, '70000204', 'Encargado');

        AreaEncargado::create([
            'area_id' => $area->id,
            'trabajador_id' => $trabajadorEncargado->id,
            'fecha_inicio' => today(),
            'estado' => true,
        ]);

        $this->crearInformacionSemanal($trabajadorEquipo, 'Actividad interna');
        $this->crearInformacionSemanal($trabajadorExterno, 'Actividad externa');

        $this->actingAs($gerente)
            ->get(route('dashboard', $this->periodoDashboard()))
            ->assertOk()
            ->assertViewHas('reporteControl', fn (array $reporte) => $reporte['esVistaGeneral']
                && ! $reporte['esResumido'])
            ->assertSee('Asistencia, avances y tareas')
            ->assertSee('Detalle administrativo')
            ->assertSee('Operaciones')
            ->assertSee('Comercial')
            ->assertSee('Operador')
            ->assertSee('Vendedor');

        $excelGerente = $this->actingAs($gerente)
            ->get(route('dashboard.reporteControl.excel', $this->periodo()))
            ->assertOk()
            ->assertDownload();

        $this->assertSame(
            ['Resumen', 'Asistencias', 'Actividades', 'Pendientes'],
            $this->cargarExcel($excelGerente->streamedContent())->getSheetNames()
        );

        $this->actingAs($encargado)
            ->get(route('dashboard', array_merge($this->periodoDashboard(), [
                'reporte_area_id' => $otraArea->id,
            ])))
            ->assertOk()
            ->assertViewHas('reporteControl', fn (array $reporte) => $reporte['esResumido']
                && ! $reporte['esVistaGeneral'])
            ->assertSee('Asistencia, avances y tareas')
            ->assertSee('Operaciones')
            ->assertSee('Operador')
            ->assertDontSee('Comercial')
            ->assertDontSee('Vendedor')
            ->assertDontSee('Detalle administrativo');

        $excelEncargado = $this->actingAs($encargado)
            ->get(route('dashboard.reporteControl.excel', array_merge($this->periodo(), [
                'area_id' => $otraArea->id,
            ])))
            ->assertOk()
            ->assertDownload();

        $libroEncargado = $this->cargarExcel($excelEncargado->streamedContent());
        $this->assertSame(['Resumen'], $libroEncargado->getSheetNames());
        $this->assertSame('Operaciones', $libroEncargado->getSheetByName('Resumen')->getCell('A10')->getValue());
    }

    public function test_un_trabajador_sin_designacion_no_puede_ver_el_reporte(): void
    {
        $area = Area::create(['nombre' => 'Logística', 'estado' => true]);
        [$trabajador] = $this->crearTrabajador($area, '70000301', 'Auxiliar');

        $this->actingAs($trabajador)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Asistencia, avances y tareas');

        $this->actingAs($trabajador)
            ->get(route('dashboard.reporteControl.excel'))
            ->assertForbidden();
    }

    public function test_el_administrador_descarga_pdf_y_excel_validos(): void
    {
        $administrador = $this->crearUsuario('ADMINISTRADOR');
        $area = Area::create(['nombre' => 'Soporte', 'estado' => true]);
        [, $trabajador] = $this->crearTrabajador($area, '70000401', 'Técnico');
        $this->crearInformacionSemanal($trabajador, 'Atendió incidencias');

        $pdf = $this->actingAs($administrador)
            ->get(route('dashboard.reporteControl.pdf', $this->periodo()))
            ->assertOk()
            ->assertDownload();

        $this->assertStringStartsWith('%PDF', $pdf->getContent());

        $excel = $this->actingAs($administrador)
            ->get(route('dashboard.reporteControl.excel', $this->periodo()))
            ->assertOk()
            ->assertDownload();

        $contenido = $excel->streamedContent();
        $this->assertSame('PK', substr($contenido, 0, 2));

        $libro = $this->cargarExcel($contenido);
        $this->assertSame(
            ['Resumen', 'Asistencias', 'Actividades', 'Pendientes'],
            $libro->getSheetNames()
        );
        $this->assertSame('Reporte consolidado de control', $libro->getSheetByName('Resumen')->getCell('A1')->getValue());
        $this->assertSame('Soporte', $libro->getSheetByName('Resumen')->getCell('A10')->getValue());
        $this->assertSame('Atendió incidencias', $libro->getSheetByName('Actividades')->getCell('E2')->getValue());
    }

    public function test_el_periodo_no_puede_superar_31_dias(): void
    {
        $administrador = $this->crearUsuario('ADMINISTRADOR');

        $this->actingAs($administrador)
            ->from(route('dashboard'))
            ->get(route('dashboard', [
                'reporte_desde' => today()->subDays(40)->toDateString(),
                'reporte_hasta' => today()->toDateString(),
            ]))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('reporte_hasta');
    }

    private function crearInformacionSemanal(Trabajador $trabajador, string $actividad): void
    {
        Asistencia::create([
            'trabajador_id' => $trabajador->id,
            'fecha' => today(),
            'hora_entrada' => '08:05:00',
            'hora_salida' => '17:15:00',
            'minutos_trabajados' => 550,
            'minutos_tardanza' => 5,
            'estado' => 'TARDANZA',
            'procesado_en' => now(),
        ]);

        $reporte = ReporteAvance::create([
            'trabajador_id' => $trabajador->id,
            'fecha_presentacion' => today(),
            'estado' => 'REVISADO',
        ]);
        $reporte->actividades()->create(['descripcion' => $actividad]);

        PendienteAvance::create([
            'reporte_avance_id' => $reporte->id,
            'area_id' => $trabajador->area_id,
            'origen' => 'TRABAJADOR',
            'descripcion' => 'Tarea finalizada por '.$trabajador->cargo,
            'estado' => 'RESUELTO',
            'estado_revision' => 'APROBADO',
            'resuelto_por' => $trabajador->id,
            'fecha_resolucion' => now(),
            'fecha_revision' => now(),
        ]);
    }

    private function periodo(): array
    {
        return [
            'desde' => today()->startOfWeek()->toDateString(),
            'hasta' => today()->endOfWeek()->toDateString(),
        ];
    }

    private function periodoDashboard(): array
    {
        return [
            'reporte_desde' => today()->startOfWeek()->toDateString(),
            'reporte_hasta' => today()->endOfWeek()->toDateString(),
        ];
    }

    private function crearUsuario(
        string $rolNombre,
        ?Trabajador $trabajador = null,
        ?string $email = null
    ): User {
        return User::create([
            'trabajador_id' => $trabajador?->id,
            'rol_id' => $this->rol($rolNombre)->id,
            'name' => $rolNombre.' '.$trabajador?->id,
            'email' => $email ?? strtolower($rolNombre).User::count().'@example.com',
            'password' => 'password123',
            'estado' => true,
        ]);
    }

    private function crearTrabajador(Area $area, string $dni, string $cargo): array
    {
        $trabajador = Trabajador::create([
            'area_id' => $area->id,
            'dni' => $dni,
            'nombres' => 'Usuario',
            'apellidos' => $cargo,
            'tipo_vinculo' => 'CONTRATADO',
            'cargo' => $cargo,
            'estado' => 'ACTIVO',
        ]);

        return [
            $this->crearUsuario('TRABAJADOR', $trabajador, $dni.'@example.com'),
            $trabajador,
        ];
    }

    private function rol(string $nombre): Rol
    {
        return Rol::firstOrCreate(
            ['nombre' => $nombre],
            ['estado' => true]
        );
    }

    private function cargarExcel(string $contenido): Spreadsheet
    {
        $temporal = tempnam(sys_get_temp_dir(), 'reporte-control-');
        file_put_contents($temporal, $contenido);

        try {
            return IOFactory::load($temporal);
        } finally {
            unlink($temporal);
        }
    }
}
