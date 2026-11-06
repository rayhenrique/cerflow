<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EvolucaoClinica extends Model
{
    use HasFactory;

    protected $table = 'evolucoes_clinicas';

    protected $fillable = [
        'agendamento_id',
        'paciente_id',
        'profissional_id',
        'queixa_estado_atual',
        'conduta_terapeutica',
        'intercorrencias',
        'assinado_em',
        'assinatura_hash',
        'bloqueado_edicao',
    ];

    protected function casts(): array
    {
        return [
            'assinado_em' => 'datetime',
            'bloqueado_edicao' => 'boolean',
        ];
    }

    public function agendamento(): BelongsTo
    {
        return $this->belongsTo(Agendamento::class, 'agendamento_id');
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class, 'profissional_id');
    }

    public function adendos(): HasMany
    {
        return $this->hasMany(EvolucaoAdendo::class, 'evolucao_id');
    }

    public function producaoBpai(): HasOne
    {
        return $this->hasOne(ProducaoBpai::class, 'evolucao_id');
    }

    /**
     * Verifica se a evolução clínica está bloqueada para edição (trava legal de 24 horas após assinatura).
     */
    public function estaBloqueada(): bool
    {
        if ($this->bloqueado_edicao) {
            return true;
        }

        if ($this->assinado_em === null) {
            return false;
        }

        return $this->assinado_em->diffInHours(now()) >= 24;
    }

    /**
     * Helper inverso de estaBloqueada() para verificar se ainda permite edição direta.
     */
    public function podeSerEditada(): bool
    {
        return ! $this->estaBloqueada();
    }
}
