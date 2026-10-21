<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BiometricoUsuario extends Model
{
    protected $table = 'biometrico_usuarios';

    protected $fillable = [
        'dispositivo_id',
        'trabajador_id',
        'pin',
        'nombre_dispositivo',
        'privilegio',
        'tarjeta',
        'estado',
        'ultima_verificacion',
    ];

    protected $casts = [
        'ultima_verificacion' => 'datetime',
    ];

    public function dispositivo()
    {
        return $this->belongsTo(DispositivoBiometrico::class, 'dispositivo_id');
    }

    public function trabajador()
    {
        return $this->belongsTo(Trabajador::class, 'trabajador_id');
    }
}
