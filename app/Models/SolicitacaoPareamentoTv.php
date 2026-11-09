<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitacaoPareamentoTv extends Model
{
    use HasFactory;

    protected $table = 'solicitacoes_pareamento_tv';

    protected $fillable = [
        'solicitacao_uid',
        'codigo',
        'segredo_temporario_hash',
        'status',
        'dispositivo_tv_id',
        'expira_em',
        'ip_origem',
        'user_agent',
        'tentativas',
    ];

    protected $hidden = [
        'segredo_temporario_hash',
    ];

    protected function casts(): array
    {
        return [
            'expira_em' => 'datetime',
            'tentativas' => 'integer',
        ];
    }

    public function dispositivo(): BelongsTo
    {
        return $this->belongsTo(DispositivoTv::class, 'dispositivo_tv_id');
    }

    /**
     * @param  Builder<SolicitacaoPareamentoTv>  $query
     */
    public function scopeValidas(Builder $query): Builder
    {
        return $query->where('status', 'PENDENTE')
            ->where('expira_em', '>', now());
    }

    public function estaExpirada(): bool
    {
        if ($this->expira_em->isPast()) {
            return true;
        }

        return in_array($this->status, ['EXPIRADO', 'REJEITADO', 'CONCLUIDO'], true);
    }

    /**
     * Gera um código de ativação amigável de 6 caracteres (ex: '4K7-9PX')
     * excluindo caracteres confusos à distância em televisores (0, O, 1, I).
     */
    public static function gerarCodigoLegivel(): string
    {
        $alfabeto = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $tamanho = strlen($alfabeto);

        do {
            $parte1 = '';
            for ($i = 0; $i < 3; $i++) {
                $parte1 .= $alfabeto[random_int(0, $tamanho - 1)];
            }
            $parte2 = '';
            for ($i = 0; $i < 3; $i++) {
                $parte2 .= $alfabeto[random_int(0, $tamanho - 1)];
            }
            $codigo = "{$parte1}-{$parte2}";

            $existe = self::query()->validas()->where('codigo', $codigo)->exists();
        } while ($existe);

        return $codigo;
    }

    public static function normalizarCodigo(string $codigo): string
    {
        $limpo = strtoupper(trim(preg_replace('/[^a-zA-Z0-9]/', '', $codigo) ?? ''));
        if (strlen($limpo) === 6) {
            return substr($limpo, 0, 3).'-'.substr($limpo, 3, 3);
        }

        return strtoupper(trim($codigo));
    }
}
