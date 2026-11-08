<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ConfiguracaoEstabelecimento extends Model
{
    protected $table = 'configuracoes_estabelecimento';

    protected $fillable = [
        'logo_path',
        'codigo_ibge_padrao',
        'cnes',
        'painel_voz_ativa',
        'painel_volume',
        'painel_tempo_destaque_segundos',
        'painel_quantidade_historico',
        'painel_modo_exibicao_nome',
        'painel_token_acesso',
    ];

    protected function casts(): array
    {
        return [
            'painel_voz_ativa' => 'boolean',
            'painel_volume' => 'integer',
            'painel_tempo_destaque_segundos' => 'integer',
            'painel_quantidade_historico' => 'integer',
        ];
    }

    /**
     * Retorna uma instância padrão em memória com os valores legados, sem escrita no banco.
     */
    public static function padrao(): self
    {
        $padrao = new self([
            'codigo_ibge_padrao' => '270430',
            'cnes' => '2000001',
            'logo_path' => null,
            'painel_voz_ativa' => true,
            'painel_volume' => 80,
            'painel_tempo_destaque_segundos' => 15,
            'painel_quantidade_historico' => 5,
            'painel_modo_exibicao_nome' => 'COMPLETO',
            'painel_token_acesso' => null,
        ]);
        $padrao->id = 1;

        return $padrao;
    }

    /**
     * Retorna o cadastro único da unidade, preservando os valores legados
     * enquanto a Coordenação não os substituir pelos dados oficiais.
     * Somente realiza leitura e trata especificamente a ausência da tabela antes da migration.
     */
    public static function atual(): self
    {
        try {
            if (! Schema::hasTable('configuracoes_estabelecimento')) {
                return static::padrao();
            }

            return static::query()->find(1) ?? static::padrao();
        } catch (QueryException) {
            return static::padrao();
        }
    }

    /**
     * Aplica a regra de privacidade configurada no nome do paciente para exibição na TV.
     */
    public function formatarNomePaciente(string $nomeCompleto): string
    {
        $nome = trim(preg_replace('/\s+/', ' ', $nomeCompleto) ?? '');
        if ($nome === '') {
            return '';
        }

        $partes = explode(' ', $nome);
        if (count($partes) <= 1) {
            return $nome;
        }

        $modo = strtoupper((string) ($this->painel_modo_exibicao_nome ?? 'COMPLETO'));

        return match ($modo) {
            'PRIMEIRO_NOME' => $partes[0],
            'PRIMEIRO_E_ULTIMO' => $partes[0].' '.$partes[count($partes) - 1],
            'ABREVIADO' => $this->abreviarPartesNome($partes),
            default => $nome,
        };
    }

    /**
     * Gera e salva um novo token aleatório para acesso direto da TV.
     */
    public function gerarTokenTv(): string
    {
        $novoToken = Str::random(40);
        $this->update(['painel_token_acesso' => $novoToken]);

        return $novoToken;
    }

    /**
     * @param  array<int, string>  $partes
     */
    private function abreviarPartesNome(array $partes): string
    {
        $primeiro = array_shift($partes);
        $ultimo = array_pop($partes);
        $preposicoes = ['de', 'da', 'do', 'das', 'dos', 'e'];

        $meio = [];
        foreach ($partes as $parte) {
            $minusculo = mb_strtolower($parte, 'UTF-8');
            if (in_array($minusculo, $preposicoes, true)) {
                continue;
            }
            $inicial = mb_substr($parte, 0, 1, 'UTF-8');
            $meio[] = mb_strtoupper($inicial, 'UTF-8').'.';
        }

        if (empty($meio)) {
            return $primeiro.' '.$ultimo;
        }

        return $primeiro.' '.implode(' ', $meio).' '.$ultimo;
    }
}
