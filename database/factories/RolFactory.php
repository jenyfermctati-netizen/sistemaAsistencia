<?php

namespace Database\Factories;

use App\Models\Rol;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Rol> */
class RolFactory extends Factory
{
    protected $model = Rol::class;

    public function definition(): array
    {
        $codigo = strtoupper(fake()->unique()->lexify('ROL_????'));

        return [
            'codigo' => $codigo,
            'ambito' => 'EMPRESA',
            'nombre' => $codigo,
            'descripcion' => fake()->sentence(),
            'estado' => true,
        ];
    }
}
