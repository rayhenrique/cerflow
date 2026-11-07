<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoricoSincronizacaoEsus extends Model
{
    use HasFactory;

    protected $table = 'historico_sincronizacoes_esus';

    protected $fillable = [
        'iniciado_em',
        'finalizado_em',
        'duracao_segundos',
        'tipo_execucao',
        'status',
        'total_consultado',
        'novos_pacientes',
        'pacientes_atualizados',
        'registros_ignorados',
        'duplicidades_identificadas',
        'mensagem_erro',
        'detalhes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'iniciado_em' => 'datetime',
            'finalizado_em' => 'datetime',
            'duracao_segundos' => 'integer',
            'total_consultado' => 'integer',
            'novos_pacientes' => 'integer',
            'pacientes_atualizados' => 'integer',
            'registros_ignorados' => 'integer',
            'duplicidades_identificadas' => 'integer',
            'detalhes' => 'array',
        ];
    }
}
