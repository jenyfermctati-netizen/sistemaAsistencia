<?php

namespace Tests\Unit;

use App\Rules\RucPeru;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class RucPeruTest extends TestCase
{
    public function test_acepta_ruc_peruano_con_digito_verificador_valido(): void
    {
        $this->assertTrue(Validator::make(
            ['ruc' => '20131312955'],
            ['ruc' => [new RucPeru]]
        )->passes());
    }

    public function test_rechaza_ruc_con_digito_verificador_invalido(): void
    {
        $this->assertFalse(Validator::make(
            ['ruc' => '20131312954'],
            ['ruc' => [new RucPeru]]
        )->passes());
    }
}
