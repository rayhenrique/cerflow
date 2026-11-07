<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Rules\CnsValido;
use PHPUnit\Framework\TestCase;

class CnsValidationTest extends TestCase
{
    public function test_aceita_cns_definitivo_valido_com_15_digitos(): void
    {
        $cnsIniciadoEm1 = CnsValido::gerarDefinitivo('12345678901');
        $cnsIniciadoEm2 = CnsValido::gerarDefinitivo('29876543210');

        $this->assertSame(15, strlen($cnsIniciadoEm1));
        $this->assertSame(15, strlen($cnsIniciadoEm2));
        $this->assertTrue(CnsValido::validar($cnsIniciadoEm1));
        $this->assertTrue(CnsValido::validar($cnsIniciadoEm2));
    }

    public function test_aceita_cns_provisorio_valido_iniciado_em_7_8_ou_9(): void
    {
        // 700000000000007 -> 7*15 + 7*1 = 105 + 7 = 112 (112 % 11 = 2) -> ajuste último dígito para 5 -> 105 + 5 = 110 (110 % 11 === 0)
        $cnsProvisorioValido = '700000000000005';

        $this->assertSame(15, strlen($cnsProvisorioValido));
        $this->assertTrue(CnsValido::validar($cnsProvisorioValido));
    }

    public function test_rejeita_cns_com_comprimento_diferente_de_15_digitos_ou_nao_numerico(): void
    {
        $this->assertFalse(CnsValido::validar('12345678901234'));   // 14 dígitos
        $this->assertFalse(CnsValido::validar('1234567890123456')); // 16 dígitos
        $this->assertFalse(CnsValido::validar('70000000000000A'));  // Alfanumérico
        $this->assertFalse(CnsValido::validar(''));                 // Vazio
    }

    public function test_rejeita_cns_com_digito_inicial_invalido_ou_digito_verificador_incorreto(): void
    {
        $this->assertFalse(CnsValido::validar('300000000000008')); // Inicia em 3
        $this->assertFalse(CnsValido::validar('777777777777777')); // Dígitos repetidos
        $this->assertFalse(CnsValido::validar('700000000000006')); // Soma módulo 11 inválida
    }
}
