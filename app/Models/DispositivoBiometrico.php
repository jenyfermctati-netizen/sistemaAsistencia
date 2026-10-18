<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DispositivoBiometrico extends Model
{
    protected $table = 'dispositivos_biometricos';

    protected $fillable = [
        'nombre',
        'serial',
        'modelo',
        'ultima_ip',
        'ultima_conexion',
        'ultima_marcacion',
    ];

    protected $casts = [
        'ultima_conexion' => 'datetime',
        'ultima_marcacion' => 'datetime',
    ];

    public function getEstaConectadoAttribute(): bool
    {
        return $this->ultima_conexion && $this->ultima_conexion->gte(now()->subMinutes(2));
    }
}
