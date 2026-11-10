<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsignacionCampo extends Model
{
    use BelongsToEmpresa, HasFactory;

    protected $table = 'asignaciones';

    protected $fillable = [
        'empresa_id',
        'trabajador_id',
        'fecha_inicio',
        'fecha_fin',
        'lugar',
        'actividad',
        'estado',
        'autorizado_por',
        'observacion',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function trabajador()
    {
        return $this->belongsTo(Trabajador::class, 'trabajador_id');
    }

    public function autorizadoPor()
    {
        return $this->belongsTo(User::class, 'autorizado_por');
    }

    public function marcacionesRemotas()
    {
        return $this->hasMany(MarcacionCampo::class, 'asignacion_campo_id');
    }

    public function avances()
    {
        return $this->hasMany(ActividadReporte::class, 'asignacion_campo_id');
    }
}
