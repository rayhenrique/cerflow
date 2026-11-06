<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogAuditoria extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'logs_auditoria';

    protected $fillable = [
        'user_id',
        'acao',
        'tabela_afetada',
        'registro_id',
        'ip_address',
        'detalhes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'detalhes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
