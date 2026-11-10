<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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

    /**
     * Busca inteligente de CBOs para autocomplete com ranqueamento por relevância:
     * 1. Código exato
     * 2. Código iniciado pelo termo
     * 3. Título oficial iniciado pelo termo
     * 4. Demais ocorrências em código ou título oficial
     *
     * @return Collection<int, Cbo>
     */
    public static function buscarParaAutocomplete(string $termo, int $limite = 10): Collection
    {
        $termoLimpo = trim($termo);
        if (mb_strlen($termoLimpo) < 2) {
            return new Collection;
        }

        $digitos = preg_replace('/\D/', '', $termoLimpo) ?? '';
        $termoLower = mb_strtolower($termoLimpo);
        $colunas = ['id', 'codigo', 'titulo_oficial', 'verificado_em'];

        $codigoExato = $digitos !== '' ? $digitos : $termoLimpo;
        $codigoPrefixo = ($digitos !== '' ? $digitos : $termoLimpo).'%';
        $tituloPrefixo = $termoLower.'%';
        $likeQualquer = "%{$termoLimpo}%";
        $likeLower = "%{$termoLower}%";

        return static::query()
            ->ativos()
            ->select($colunas)
            ->where(function (Builder $q) use ($digitos, $likeQualquer, $likeLower): void {
                if ($digitos !== '') {
                    $q->where('codigo', 'like', "{$digitos}%")
                        ->orWhere('codigo', 'like', "%{$digitos}%");
                }
                $q->orWhere('titulo_oficial', 'like', $likeQualquer)
                    ->orWhere('titulo_oficial', 'like', $likeLower);
            })
            ->orderByRaw(
                'CASE 
                    WHEN codigo = ? THEN 1 
                    WHEN codigo LIKE ? THEN 2 
                    WHEN LOWER(titulo_oficial) LIKE ? THEN 3 
                    ELSE 4 
                END, codigo ASC, titulo_oficial ASC',
                [$codigoExato, $codigoPrefixo, $tituloPrefixo]
            )
            ->limit($limite)
            ->get();
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
