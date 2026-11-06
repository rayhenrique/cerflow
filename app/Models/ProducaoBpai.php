<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProducaoBpai extends Model
{
    use HasFactory;

    protected $table = 'producoes_bpai';

    protected $fillable = [
        'evolucao_id',
        'paciente_id',
        'profissional_id',
        'procedimento_id',
        'cbo_executante',
        'cid_atendimento',
        'data_atendimento',
        'competencia',
        'status_exportacao',
        'mensagem_inconsistencia',
        'lote_exportacao_id',
    ];

    protected function casts(): array
    {
        return [
            'data_atendimento' => 'date',
        ];
    }

    public function evolucao(): BelongsTo
    {
        return $this->belongsTo(EvolucaoClinica::class, 'evolucao_id');
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class, 'profissional_id');
    }

    public function procedimento(): BelongsTo
    {
        return $this->belongsTo(ProcedimentoSigtap::class, 'procedimento_id');
    }

    public function scopePorCompetencia(Builder $query, string $competencia): Builder
    {
        return $query->where('competencia', $competencia);
    }

    public function scopeCompetencia(Builder $query, string $competencia): Builder
    {
        return $this->scopePorCompetencia($query, $competencia);
    }

    public function scopePorStatusExportacao(Builder $query, string $status): Builder
    {
        return $query->where('status_exportacao', $status);
    }

    public function scopeStatusExportacao(Builder $query, string $status): Builder
    {
        return $this->scopePorStatusExportacao($query, $status);
    }
}
