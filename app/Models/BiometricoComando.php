<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BiometricoComando extends Model
{
    protected $table = 'biometrico_comandos';

    protected $fillable = [
        'dispositivo_id',
        'tipo',
        'comando',
        'estado',
        'codigo_respuesta',
        'respuesta',
        'intentos',
        'enviado_en',
        'respondido_en',
    ];

    protected $casts = [
        'enviado_en' => 'datetime',
        'respondido_en' => 'datetime',
    ];

    public function dispositivo()
    {
        return $this->belongsTo(DispositivoBiometrico::class, 'dispositivo_id');
    }
}
