<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Especialidade extends Model
{
    use HasFactory;

    protected $table = 'especialidades';

    protected $fillable = [
        'nome',
        'descricao',
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

    public function cbos(): BelongsToMany
    {
        return $this->belongsToMany(Cbo::class, 'cbo_especialidade', 'especialidade_id', 'cbo_id')
            ->withTimestamps();
    }

    public function profissionais(): HasMany
    {
        return $this->hasMany(Profissional::class, 'especialidade_id');
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
                ->orWhere('descricao', 'like', "%{$limpo}%");
        });
    }

    public function podeSerExcluida(): bool
    {
        return ! $this->profissionais()->exists();
    }
}

