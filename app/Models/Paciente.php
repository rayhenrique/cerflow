<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Paciente extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pacientes';

    protected $fillable = [
        'cns',
        'cpf',
        'nome',
        'data_nascimento',
        'sexo',
        'nome_mae',
        'municipio_ibge',
        'endereco',
        'bairro',
        'telefone_contato',
        'cid_principal',
        'cid_secundario',
        'modalidades_reabilitacao',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'data_nascimento' => 'date',
            'modalidades_reabilitacao' => 'array',
            'ativo' => 'boolean',
        ];
    }

    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class, 'paciente_id');
    }

    public function evolucoes(): HasMany
    {
        return $this->hasMany(EvolucaoClinica::class, 'paciente_id');
    }

    public function producoesBpai(): HasMany
    {
        return $this->hasMany(ProducaoBpai::class, 'paciente_id');
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }
}
