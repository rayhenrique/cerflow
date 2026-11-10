<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cbo extends Model
{
    use HasFactory;

    protected $table = 'cbos';

    protected $fillable = [
        'codigo',
        'titulo_oficial',
        'ativo',
        'fonte_referencia',
        'versao_referencia',
        'verificado_em',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'verificado_em' => 'datetime',
        ];
    }

    public function especialidades(): BelongsToMany
    {
        return $this->belongsToMany(Especialidade::class, 'cbo_especialidade', 'cbo_id', 'especialidade_id')
            ->withTimestamps();
    }

    public function profissionais(): HasMany
    {
        return $this->hasMany(Profissional::class, 'cbo_id');
    }

    public function scopeAtivos(Builder $query): Builder
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
            $sub->where('codigo', 'like', "%{$limpo}%")
                ->orWhere('titulo_oficial', 'like', "%{$limpo}%");
        });
    }

    public function podeSerExcluido(): bool
    {
        return ! $this->profissionais()->exists();
    }

    public function estaVerificado(): bool
    {
        return $this->verificado_em !== null;
    }
}

