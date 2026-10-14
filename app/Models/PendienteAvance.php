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
        'area_id',
        'creado_por',
        'origen',
        'descripcion',
        'detalle',
        'ruta_archivos',
        'estado',
        'estado_revision',
        'observacion_revision',
        'revisado_por',
        'fecha_revision',
        'resuelto_por',
        'fecha_resolucion',
    ];

    protected $casts = [
        'fecha_resolucion' => 'datetime',
        'fecha_revision' => 'datetime',
    ];

    public function reporte()
    {
        return $this->belongsTo(ReporteAvance::class, 'reporte_avance_id');
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function resueltoPor()
    {
        return $this->belongsTo(Trabajador::class, 'resuelto_por');
    }

    public function revisadoPor()
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }
}
