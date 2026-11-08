<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChamadaPainel extends Model
{
    use HasFactory;

    protected $table = 'chamadas_painel';

    protected $fillable = [
        'agendamento_id',
        'paciente_id',
        'sala_id',
        'profissional_id',
        'chamado_por_id',
        'paciente_nome',
        'sala_nome',
        'profissional_nome',
        'tipo',
        'status',
        'chamado_em',
    ];

    protected function casts(): array
    {
        return [
            'chamado_em' => 'datetime',
        ];
    }

    public function agendamento(): BelongsTo
    {
        return $this->belongsTo(Agendamento::class, 'agendamento_id');
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function sala(): BelongsTo
    {
        return $this->belongsTo(Sala::class, 'sala_id');
    }

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class, 'profissional_id');
    }

    public function chamadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'chamado_por_id');
    }

    /**
     * @param  Builder<ChamadaPainel>  $query
     */
    public function scopeDoDia(Builder $query, ?CarbonInterface $data = null): Builder
    {
        $dataRef = $data ?? now();

        return $query->whereDate('chamado_em', $dataRef->toDateString());
    }

    /**
     * @param  Builder<ChamadaPainel>  $query
     */
    public function scopeRecentes(Builder $query, int $limite = 5): Builder
    {
        return $query->orderByDesc('chamado_em')->limit($limite);
    }
}
