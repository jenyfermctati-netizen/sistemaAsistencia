<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReporteAvance extends Model
{
    use HasFactory;

    protected $table = 'reportes_avance';

    protected $fillable = [
        'trabajador_id',
        'periodo_inicio',
        'periodo_fin',
        'fecha_presentacion',
        'descripcion',
        'porcentaje_avance',
        'archivo',
        'estado',
        'revisado_por',
        'comentario_revision',
        'fecha_revision',
    ];

    protected function casts(): array
    {
        return [
            'periodo_inicio' => 'date',
            'periodo_fin' => 'date',
            'fecha_presentacion' => 'date',
            'fecha_revision' => 'datetime',
            'porcentaje_avance' => 'decimal:2',
        ];
    }

    public function trabajador()
    {
        return $this->belongsTo(
            Trabajador::class,
            'trabajador_id'
        );
    }

    public function revisor()
    {
        return $this->belongsTo(
            User::class,
            'revisado_por'
        );
    }

    public function reportesAvance()
    {
        return $this->hasMany(
            ReporteAvance::class,
            'trabajador_id'
        );
}
}