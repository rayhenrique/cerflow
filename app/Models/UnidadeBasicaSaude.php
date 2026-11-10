<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnidadeBasicaSaude extends Model
{
    use HasFactory;

    protected $table = 'unidades_basicas_saude';

    protected $fillable = [
        'nome',
        'cnes',
        'municipio_ibge',
        'bairro',
        'endereco',
        'telefone',
        'origem',
        'identificador_externo',
        'ativo',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    public function pacientes(): HasMany
    {
        return $this->hasMany(Paciente::class, 'ubs_id');
    }

    public function scopeAtivas(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function scopeBuscar(Builder $query, string $termo): Builder
    {
        $limpo = trim($termo);
        if ($limpo === '') {
            return $query;
        }

        return $query->where(function (Builder $sub) use ($limpo): void {
            $sub->where('nome', 'like', "%{$limpo}%")
                ->orWhere('cnes', 'like', "%{$limpo}%")
                ->orWhere('bairro', 'like', "%{$limpo}%");
        });
    }

    public function podeSerExcluida(): bool
    {
        return ! $this->pacientes()->withTrashed()->exists();
    }
}
