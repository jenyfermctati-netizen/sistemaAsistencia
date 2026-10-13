<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActividadReporte extends Model
{
    use HasFactory;

    protected $table = 'actividades_reporte';

    protected $fillable = [
        'reporte_avance_id',
        'descripcion',
        'archivo',
    ];

    public function reporte()
    {
        return $this->belongsTo(ReporteAvance::class, 'reporte_avance_id');
    }
}
