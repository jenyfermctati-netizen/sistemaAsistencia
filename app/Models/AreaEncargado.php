<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AreaEncargado extends Model
{
    use HasFactory;

    protected $table = 'area_encargados';

    protected $fillable = [
        'area_id',
        'trabajador_id',
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

    public function scopeVigentes(Builder $query): Builder
    {
        return $query
            ->where('estado', true)
            ->whereDate('fecha_inicio', '<=', today())
            ->where(function (Builder $query) {
                $query
                    ->whereNull('fecha_fin')
                    ->orWhereDate('fecha_fin', '>=', today());
            });
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function trabajador()
    {
        return $this->belongsTo(Trabajador::class, 'trabajador_id');
    }
}
