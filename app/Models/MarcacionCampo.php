<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarcacionCampo extends Model
{
    use BelongsToEmpresa, HasFactory;

    protected $table = 'marcaciones_campo';

    protected $fillable = [
        'empresa_id',
        'asignacion_campo_id',
        'trabajador_id',
        'fecha_hora',
        'tipo',
        'estado',
        'registrado_por',
        'evidencia_path',
        'revisado_por',
        'fecha_revision',
        'observacion_revision',
        'marcacion_id',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
        'fecha_revision' => 'datetime',
    ];

    public function asignacion()
    {
        return $this->belongsTo(AsignacionCampo::class, 'asignacion_campo_id');
    }

    public function trabajador()
    {
        return $this->belongsTo(Trabajador::class, 'trabajador_id');
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function revisadoPor()
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }

    public function marcacion()
    {
        return $this->belongsTo(Marcacion::class, 'marcacion_id');
    }
}
