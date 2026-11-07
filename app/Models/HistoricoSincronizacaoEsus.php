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

    public function getRegistrosValidosAttribute(): int
    {
        return (int) ($this->detalhes['registros_validos'] ?? ($this->novos_pacientes + $this->pacientes_atualizados + ($this->detalhes['pacientes_sem_alteracoes'] ?? 0)));
    }

    public function getRegistrosInvalidosAttribute(): int
    {
        return (int) ($this->detalhes['registros_invalidos'] ?? $this->registros_ignorados);
    }

    public function getSemIdentificadorAttribute(): int
    {
        return (int) ($this->detalhes['sem_identificador'] ?? 0);
    }

    public function getPacientesSemAlteracoesAttribute(): int
    {
        return (int) ($this->detalhes['pacientes_sem_alteracoes'] ?? 0);
    }

    public function getFalhasGravacaoAttribute(): int
    {
        return (int) ($this->detalhes['falhas_gravacao'] ?? 0);
    }

    public function getEtapaAtualAttribute(): string
    {
        return (string) ($this->detalhes['etapa'] ?? '');
    }

    public function getPercentualProgressoAttribute(): int
    {
        return (int) ($this->detalhes['percentual'] ?? 0);
    }
}
