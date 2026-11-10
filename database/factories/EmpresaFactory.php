<?php

namespace Database\Factories;

use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Empresa> */
class EmpresaFactory extends Factory
{
    protected $model = Empresa::class;

    public function definition(): array
    {
        return [
            'razon_social' => fake()->company().' S.A.C.',
            'nombre_comercial' => fake()->company(),
            'ruc' => '20'.fake()->unique()->numerify('#########'),
            'email' => fake()->unique()->companyEmail(),
            'telefono' => fake()->numerify('9########'),
            'direccion' => fake()->address(),
            'estado' => Empresa::ACTIVA,
            'zona_horaria' => 'America/Lima',
        ];
    }
}
