<?php

namespace App\Models;

use App\Models\Concerns\ScopedByEmpresaRelation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrabajadorHorario extends Model
{
    use HasFactory, ScopedByEmpresaRelation;

    protected static string $empresaRelation = 'trabajador';

    protected $table = 'trabajador_horarios';

    protected $fillable = [
        'trabajador_id',
        'horario_id',
        'tipo_control',
        'fecha_inicio',
        'fecha_fin',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'estado' => 'boolean',
        ];
    }

    public function trabajador()
    {
        return $this->belongsTo(Trabajador::class, 'trabajador_id');
    }

    public function horario()
    {
        return $this->belongsTo(Horario::class, 'horario_id');
    }
}
