<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feriado extends Model
{
    use BelongsToEmpresa, HasFactory;

    protected $table = 'trabajador_feriados';

    protected $fillable = [
        'empresa_id',
        'area_id',
        'fecha',
        'nombre',
        'tipo',
        'descripcion',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'date',
        'estado' => 'boolean',
    ];

    public function area()
    {
        return $this->belongsTo(
            Area::class,
            'area_id'
        );
    }
}
