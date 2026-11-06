<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvolucaoAdendo extends Model
{
    use HasFactory;

    protected $table = 'evolucao_adendos';

    protected $fillable = [
        'evolucao_id',
        'profissional_id',
        'justificativa',
        'texto_adendo',
        'assinado_em',
    ];

    protected function casts(): array
    {
        return [
            'assinado_em' => 'datetime',
        ];
    }

    public function evolucao(): BelongsTo
    {
        return $this->belongsTo(EvolucaoClinica::class, 'evolucao_id');
    }

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class, 'profissional_id');
    }
}
