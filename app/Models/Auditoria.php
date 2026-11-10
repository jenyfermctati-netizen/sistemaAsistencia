<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;

class Auditoria extends Model
{
    use BelongsToEmpresa;

    protected $table = 'auditoria';

    protected $fillable = [
        'empresa_id',
        'user_id',
        'accion',
        'tabla',
        'registro_id',
        'valores_anteriores',
        'valores_nuevos',
        'ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'valores_anteriores' => 'array',
            'valores_nuevos' => 'array',
        ];
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
