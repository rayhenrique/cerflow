<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ConfiguracaoEsusPec extends Model
{
    use HasFactory;

    protected $table = 'configuracoes_esus_pec';

    protected $fillable = [
        'ativo',
        'driver',
        'host',
        'porta',
        'banco',
        'usuario',
        'senha',
        'endereco_web_pec',
        'ultimo_status_conexao',
        'mensagem_status_conexao',
        'tipo_banco_detectado',
        'tabela_origem_detectada',
        'total_tabelas_descobertas',
        'detalhes_schema',
        'ultima_conexao_em',
        'ultima_sincronizacao_em',
        'ultima_sincronizacao_status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'porta' => 'integer',
            'senha' => 'encrypted',
            'total_tabelas_descobertas' => 'integer',
            'detalhes_schema' => 'array',
            'ultima_conexao_em' => 'datetime',
            'ultima_sincronizacao_em' => 'datetime',
        ];
    }

    /**
     * Retorna uma instância padrão em memória sem persistência.
     */
    public static function padrao(): self
    {
        $padrao = new self;
        $padrao->id = 1;
        $padrao->ativo = false;
        $padrao->driver = 'pgsql';
        $padrao->host = 'localhost';
        $padrao->porta = 5433;
        $padrao->banco = 'esus';
        $padrao->usuario = 'esus_leitura';
        $padrao->endereco_web_pec = 'http://189.89.61.50:8181';
        $padrao->ultimo_status_conexao = 'NAO_TESTADO';
        $padrao->total_tabelas_descobertas = 0;

        return $padrao;
    }

    /**
     * Retorna a configuração atual persistida no banco ou o fallback em memória.
     */
    public static function atual(): self
    {
        if (! Schema::hasTable('configuracoes_esus_pec')) {
            return self::padrao();
        }

        /** @var ?self $config */
        $config = self::query()->find(1);
        if ($config !== null) {
            return $config;
        }

        $novo = self::padrao();
        $novo->save();

        return $novo;
    }

    public function temSenha(): bool
    {
        return ! empty($this->senha);
    }
}
