<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Marcacion extends Model
{
    use BelongsToEmpresa, HasFactory;

    protected $table = 'marcaciones';

    protected $fillable = [
        'empresa_id',
        'dispositivo_id',
        'trabajador_id',
        'fecha_hora',
        'tipo',
        'origen',
        'dispositivo',
        'evento_externo_id',
        'registrado_por',
        'motivo_manual',
        'anulada',
        'anulada_por',
        'motivo_anulacion',
        'fecha_anulacion',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
        'anulada' => 'boolean',
        'fecha_anulacion' => 'datetime',
    ];

    public function trabajador()
    {
        return $this->belongsTo(
            Trabajador::class,
            'trabajador_id'
        );
    }

    public function registradoPor()
    {
        return $this->belongsTo(
            User::class,
            'registrado_por'
        );
    }

    public function anuladaPor()
    {
        return $this->belongsTo(
            User::class,
            'anulada_por'
        );
    }

    public function registroCampo()
    {
        return $this->hasOne(MarcacionCampo::class, 'marcacion_id');
    }

    public function dispositivoBiometrico()
    {
        return $this->belongsTo(DispositivoBiometrico::class, 'dispositivo_id');
    }
}
