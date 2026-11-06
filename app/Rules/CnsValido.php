<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class CnsValido implements ValidationRule
{
    /**
     * Valida o formato (15 dígitos numéricos) e o dígito verificador (Módulo 11 SUS) do Cartão Nacional de Saúde (CNS).
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) && ! is_numeric($value)) {
            $fail('O campo :attribute deve conter exatamente 15 dígitos numéricos válidos.');

            return;
        }

        if (! self::validar((string) $value)) {
            $fail('O Cartão Nacional de Saúde (CNS) informado é inválido.');
        }
    }

    /**
     * Algoritmo oficial do Ministério da Saúde / DATASUS para validação de CNS de 15 dígitos (iniciados em 1, 2, 7, 8 ou 9).
     */
    public static function validar(string $cns): bool
    {
        if (preg_match('/^\d{15}$/', $cns) !== 1) {
            return false;
        }

        if (preg_match('/^(\d)\1{14}$/', $cns) === 1) {
            return false;
        }

        $primeiroDigito = $cns[0];
        if (! in_array($primeiroDigito, ['1', '2', '7', '8', '9'], true)) {
            return false;
        }

        if (in_array($primeiroDigito, ['1', '2'], true)) {
            $pis = substr($cns, 0, 11);
            $soma = 0;

            for ($i = 0; $i < 11; $i++) {
                $soma += ((int) $pis[$i]) * (15 - $i);
            }

            $resto = $soma % 11;
            $dv = 11 - $resto;

            if ($dv === 11) {
                $dv = 0;
            }

            if ($dv === 10) {
                $soma += 2;
                $resto = $soma % 11;
                $dv = 11 - $resto;
                $esperado = $pis.'001'.$dv;
            } else {
                $esperado = $pis.'000'.$dv;
            }

            return $cns === $esperado;
        }

        $soma = 0;
        for ($i = 0; $i < 15; $i++) {
            $soma += ((int) $cns[$i]) * (15 - $i);
        }

        return ($soma % 11) === 0;
    }

    /**
     * Gera um CNS definitivo válido a partir de 11 dígitos base (para testes e seeders).
     */
    public static function gerarDefinitivo(string $pis11 = '12345678901'): string
    {
        $pis = str_pad(preg_replace('/\D/', '', $pis11) ?? '', 11, '0', STR_PAD_LEFT);
        if (! in_array($pis[0], ['1', '2'], true)) {
            $pis = '1'.substr($pis, 1, 10);
        } else {
            $pis = substr($pis, 0, 11);
        }

        $soma = 0;
        for ($i = 0; $i < 11; $i++) {
            $soma += ((int) $pis[$i]) * (15 - $i);
        }

        $resto = $soma % 11;
        $dv = 11 - $resto;

        if ($dv === 11) {
            $dv = 0;
        }

        if ($dv === 10) {
            $soma += 2;
            $resto = $soma % 11;
            $dv = 11 - $resto;

            return $pis.'001'.$dv;
        }

        return $pis.'000'.$dv;
    }
}
