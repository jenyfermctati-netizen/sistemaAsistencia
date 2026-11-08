<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActividadReporte extends Model
{
    use \App\Models\Concerns\PerteneceAEmpresa, HasFactory;

    protected $table = 'actividades_reporte';

    protected $fillable = [
        'reporte_avance_id',
        'descripcion',
        'archivo',
        'asignacion_campo_id',
    ];

    public function reporte()
    {
        return $this->belongsTo(ReporteAvance::class, 'reporte_avance_id');
    }

    public function asignacionCampo()
    {
        return $this->belongsTo(AsignacionCampo::class, 'asignacion_campo_id');
    }
}
