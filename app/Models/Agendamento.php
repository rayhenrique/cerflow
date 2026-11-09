<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agendamento extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'agendamentos';

    protected $fillable = [
        'paciente_id',
        'sala_id',
        'data_atendimento',
        'hora_inicio',
        'hora_fim',
        'dia_semana',
        'tipo_atendimento',
        'status',
        'chegada_em',
        'recorrente',
        'observacao',
    ];

    protected function casts(): array
    {
        return [
            'data_atendimento' => 'date',
            'chegada_em' => 'datetime',
            'recorrente' => 'boolean',
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function sala(): BelongsTo
    {
        return $this->belongsTo(Sala::class, 'sala_id');
    }

    public function profissionais(): BelongsToMany
    {
        return $this->belongsToMany(
            Profissional::class,
            'agendamento_profissionais',
            'agendamento_id',
            'profissional_id'
        )
            ->withPivot('papel')
            ->withTimestamps();
    }

    public function evolucoes(): HasMany
    {
        return $this->hasMany(EvolucaoClinica::class, 'agendamento_id');
    }

    public function chamadas(): HasMany
    {
        return $this->hasMany(ChamadaPainel::class, 'agendamento_id');
    }

    public function ultimaChamada(): HasOne
    {
        return $this->hasOne(ChamadaPainel::class, 'agendamento_id')->latestOfMany('id');
    }
}
