<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracaoEstabelecimento extends Model
{
    protected $table = 'configuracoes_estabelecimento';

    protected $fillable = [
        'logo_path',
        'codigo_ibge_padrao',
        'cnes',
    ];

    /**
     * Retorna o cadastro único da unidade, preservando os valores legados
     * enquanto a Coordenação não os substituir pelos dados oficiais.
     */
    public static function atual(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'codigo_ibge_padrao' => '270430',
                'cnes' => '2000001',
            ]
        );
    }
}
