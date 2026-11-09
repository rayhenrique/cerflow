<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class DispositivoTv extends Model
{
    use HasFactory;

    protected $table = 'dispositivos_tv';

    protected $fillable = [
        'nome',
        'identificador',
        'token_hash',
        'status',
        'autorizado_por_id',
        'autorizado_em',
        'ultimo_acesso_em',
        'expira_em',
        'revogado_em',
        'revogado_por_id',
        'ip_origem',
        'user_agent',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'autorizado_em' => 'datetime',
            'ultimo_acesso_em' => 'datetime',
            'expira_em' => 'datetime',
            'revogado_em' => 'datetime',
        ];
    }

    public function autorizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autorizado_por_id');
    }

    public function revogadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revogado_por_id');
    }

    public function solicitacoes(): HasMany
    {
        return $this->hasMany(SolicitacaoPareamentoTv::class, 'dispositivo_tv_id');
    }

    /**
     * @param  Builder<DispositivoTv>  $query
     */
    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('status', 'ATIVO')
            ->where(function (Builder $q): void {
                $q->whereNull('expira_em')->orWhere('expira_em', '>', now());
            });
    }

    public function estaAtivo(): bool
    {
        if ($this->status !== 'ATIVO') {
            return false;
        }

        if ($this->expira_em !== null && $this->expira_em->isPast()) {
            return false;
        }

        return true;
    }

    public function revogar(User $usuario): void
    {
        $this->update([
            'status' => 'REVOGADO',
            'revogado_em' => now(),
            'revogado_por_id' => $usuario->id,
        ]);
    }

    public function registrarAcesso(?string $ip = null, ?string $userAgent = null): void
    {
        // Throttled update com cache de 15 minutos: impede UPDATEs no MySQL a cada 4 segundos na VPS
        $chaveAcesso = "tv_access_touch_{$this->id}";
        if (Cache::add($chaveAcesso, true, now()->addMinutes(15))) {
            $this->update([
                'ultimo_acesso_em' => now(),
                'ip_origem' => $ip ?? $this->ip_origem,
                'user_agent' => $userAgent ?? $this->user_agent,
            ]);
        }
    }
}
