<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendienteAvance extends Model
{
    use HasFactory;

    protected $table = 'pendientes_avance';

    protected $fillable = [
        'reporte_avance_id',
        'descripcion',
        'detalle',
        'ruta_archivos',
        'estado',
        'resuelto_por',
        'fecha_resolucion',
    ];

    protected $casts = [
        'fecha_resolucion' => 'datetime',
    ];

    public function reporte()
    {
        return $this->belongsTo(ReporteAvance::class, 'reporte_avance_id');
    }

    public function resueltoPor()
    {
        return $this->belongsTo(Trabajador::class, 'resuelto_por');
    }
}
