<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Utilitário para manipulação, validação e conversão de códigos municipais do IBGE.
 *
 * Especificações:
 * - Código oficial IBGE: 7 posições (UF de 2 dígitos + Município de 4 dígitos + 1 dígito verificador módulo 10).
 * - Código BPA-i / SIA-SUS: 6 posições (UF de 2 dígitos + Município de 4 dígitos, sem o dígito verificador).
 *
 * Município sede oficial do CER:
 * - Teotônio Vilela — Alagoas: IBGE 7 dígitos = '2709152' | BPA-i 6 dígitos = '270915'.
 */
class IbgeMunicipio
{
    public const PADRAO_7 = '2709152';

    public const PADRAO_6 = '270915';

    public const NOME_PADRAO = 'Teotônio Vilela';

    public const UF_PADRAO = 'AL';

    /**
     * Mapa de nomes dos municípios mais frequentes da região para exibição humanizada.
     *
     * @var array<string, string>
     */
    private const NOMES_CONHECIDOS = [
        '270915' => 'Teotônio Vilela / AL',
        '2709152' => 'Teotônio Vilela / AL',
        '270430' => 'Maceió / AL',
        '2704302' => 'Maceió / AL',
        '270030' => 'Arapiraca / AL',
        '2700300' => 'Arapiraca / AL',
        '270400' => 'Junqueiro / AL',
        '2704003' => 'Junqueiro / AL',
        '270890' => 'São Sebastião / AL',
        '2708907' => 'São Sebastião / AL',
        '270210' => 'Campo Alegre / AL',
        '2702107' => 'Campo Alegre / AL',
        '270230' => 'Coruripe / AL',
        '2702305' => 'Coruripe / AL',
        '270040' => 'Atalaia / AL',
        '2700409' => 'Atalaia / AL',
    ];

    /**
     * Valida se uma string é um código municipal IBGE válido (6 ou 7 dígitos numéricos).
     */
    public static function validar(?string $codigo): bool
    {
        if ($codigo === null) {
            return false;
        }

        $limpo = preg_replace('/\D/', '', $codigo);
        if ($limpo === null || strlen($limpo) < 6 || strlen($limpo) > 7) {
            return false;
        }

        // Os dois primeiros dígitos devem ser uma UF brasileira válida (11 a 53)
        $uf = (int) substr($limpo, 0, 2);
        if ($uf < 11 || $uf > 53) {
            return false;
        }

        return true;
    }

    /**
     * Converte o código IBGE para a representação de 6 posições exigida pelo layout do BPA-i (SIA/SUS).
     * Regra oficial: omite o 7º dígito (DV), preservando zeros à esquerda e retornando exatamente 6 dígitos.
     */
    public static function converterParaBpai(?string $codigo): string
    {
        if ($codigo === null || trim($codigo) === '') {
            return self::PADRAO_6;
        }

        $limpo = preg_replace('/\D/', '', $codigo) ?? '';
        if ($limpo === '') {
            return self::PADRAO_6;
        }

        if (strlen($limpo) >= 7) {
            return substr($limpo, 0, 6);
        }

        return str_pad($limpo, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Formata o código municipal para o formato oficial completo de 7 dígitos do IBGE.
     * Caso o código recebido possua 6 dígitos legados, calcula o DV pelo algoritmo Módulo 10 oficial.
     */
    public static function formatar7(?string $codigo): string
    {
        if ($codigo === null || trim($codigo) === '') {
            return self::PADRAO_7;
        }

        $limpo = preg_replace('/\D/', '', $codigo) ?? '';
        if (strlen($limpo) === 7) {
            return $limpo;
        }

        if (strlen($limpo) === 6) {
            $dv = self::calcularDigitoVerificador($limpo);

            return $limpo.(string) $dv;
        }

        return self::PADRAO_7;
    }

    /**
     * Calcula o dígito verificador oficial do IBGE para o código de 6 posições.
     * Algoritmo: Módulo 10 com pesos alternados [1, 2, 1, 2, 1, 2].
     * Quando o produto individual excede 9, subtrai-se 9 (ou soma-se os dois dígitos do produto).
     */
    public static function calcularDigitoVerificador(string $seisDigitos): int
    {
        $limpo = str_pad(substr(preg_replace('/\D/', '', $seisDigitos) ?? '', 0, 6), 6, '0', STR_PAD_LEFT);
        $pesos = [1, 2, 1, 2, 1, 2];
        $soma = 0;

        for ($i = 0; $i < 6; $i++) {
            $prod = ((int) $limpo[$i]) * $pesos[$i];
            $soma += ($prod > 9) ? ($prod - 9) : $prod;
        }

        $resto = $soma % 10;

        return ($resto === 0) ? 0 : (10 - $resto);
    }

    /**
     * Retorna o nome amigável do município se conhecido ou uma descrição padrão.
     */
    public static function nomeMunicipio(?string $codigo): string
    {
        if ($codigo === null || trim($codigo) === '') {
            return self::NOMES_CONHECIDOS[self::PADRAO_7];
        }

        $limpo = preg_replace('/\D/', '', $codigo) ?? '';

        return self::NOMES_CONHECIDOS[$limpo] ?? ('Município IBGE '.$limpo);
    }
}
