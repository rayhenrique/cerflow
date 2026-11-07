<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

class ConfiguracaoEstabelecimento extends Model
{
    protected $table = 'configuracoes_estabelecimento';

    protected $fillable = [
        'logo_path',
        'codigo_ibge_padrao',
        'cnes',
    ];

    /**
     * Retorna uma instância padrão em memória com os valores legados, sem escrita no banco.
     */
    public static function padrao(): self
    {
        $padrao = new self([
            'codigo_ibge_padrao' => '270430',
            'cnes' => '2000001',
            'logo_path' => null,
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
}
