<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sala extends Model
{
    use HasFactory;

    protected $table = 'salas';

    protected $fillable = [
        'nome',
        'tipo',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class, 'sala_id');
    }

    public function scopeAtivas(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }
}
