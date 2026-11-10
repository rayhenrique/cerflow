<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Profissional extends Model
{
    use HasFactory;

    protected $table = 'profissionais';

    protected $fillable = [
        'user_id',
        'nome',
        'cpf',
        'cns',
        'registro_conselho',
        'cbo_id',
        'especialidade_id',
        'cbo_padrao',
        'especialidade',
        'pin_assinatura_hash',
        'ativo',
    ];

    protected $hidden = [
        'pin_assinatura_hash',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cbo(): BelongsTo
    {
        return $this->belongsTo(Cbo::class, 'cbo_id');
    }

    public function especialidadeRelacionada(): BelongsTo
    {
        return $this->belongsTo(Especialidade::class, 'especialidade_id');
    }

    public function agendamentos(): BelongsToMany
    {
        return $this->belongsToMany(
            Agendamento::class,
            'agendamento_profissionais',
            'profissional_id',
            'agendamento_id'
        )
            ->withPivot('papel')
            ->withTimestamps();
    }

    public function evolucoes(): HasMany
    {
        return $this->hasMany(EvolucaoClinica::class, 'profissional_id');
    }

    public function adendos(): HasMany
    {
        return $this->hasMany(EvolucaoAdendo::class, 'profissional_id');
    }

    public function producoesBpai(): HasMany
    {
        return $this->hasMany(ProducaoBpai::class, 'profissional_id');
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }
}
