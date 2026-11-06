<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcedimentoSigtap extends Model
{
    use HasFactory;

    protected $table = 'procedimentos_sigtap';

    protected $fillable = [
        'codigo',
        'descricao',
        'cbos_permitidos',
        'idade_minima',
        'idade_maxima',
        'sexo_permitido',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'cbos_permitidos' => 'array',
            'idade_minima' => 'integer',
            'idade_maxima' => 'integer',
            'ativo' => 'boolean',
        ];
    }

    public function producoesBpai(): HasMany
    {
        return $this->hasMany(ProducaoBpai::class, 'procedimento_id');
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function permiteCbo(string $cbo): bool
    {
        return in_array($cbo, $this->cbos_permitidos ?? [], true);
    }
}
