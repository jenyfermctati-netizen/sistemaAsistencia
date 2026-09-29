<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trabajador extends Model
{
    protected $fillable = [
        'nombres',
        'apellidos',
        'dni',
        'sexo',
        'fecha_nacimiento',
        'telefono',
        'direccion',
        'email',
        'cargo',
        'area',
        'estado',
    ];
    public function user()
    {
        return $this->hasOne(User::class, 'trabajador_id');
    }
}
