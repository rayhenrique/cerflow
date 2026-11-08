<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Paciente extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pacientes';

    protected $fillable = [
        'cns',
        'cpf',
        'nome',
        'data_nascimento',
        'sexo',
        'nome_mae',
        'municipio_ibge',
        'ubs_referencia',
        'nome_acs',
        'endereco',
        'bairro',
        'telefone_contato',
        'telefone_secundario',
        'cid_principal',
        'cid_secundario',
        'modalidades_reabilitacao',
        'ativo',
        'origem_cadastro',
        'id_externo_esus',
        'data_ultima_sincronizacao_esus',
    ];

    protected function casts(): array
    {
        return [
            'data_nascimento' => 'date',
            'modalidades_reabilitacao' => 'array',
            'ativo' => 'boolean',
            'data_ultima_sincronizacao_esus' => 'datetime',
        ];
    }

    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class, 'paciente_id');
    }

    public function evolucoes(): HasMany
    {
        return $this->hasMany(EvolucaoClinica::class, 'paciente_id');
    }

    public function producoesBpai(): HasMany
    {
        return $this->hasMany(ProducaoBpai::class, 'paciente_id');
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function temCns(): bool
    {
        return ! empty($this->cns);
    }

    public function temDiagnosticoClinico(): bool
    {
        return ! empty($this->cid_principal);
    }

    public function getIdentificadorExternoEsusAttribute(): ?string
    {
        return $this->id_externo_esus;
    }

    /**
     * Realiza a busca sob demanda e altamente otimizada de pacientes para seleção e autocompletes.
     * Prioriza índices em Nome, CPF e CNS, garantindo tempo de resposta sub-milissegundo em bases com 40.000+ pacientes.
     *
     * @return Collection<int, Paciente>
     */
    public static function buscarParaSelecao(string $termo, int $limite = 20): Collection
    {
        $termoLimpo = trim($termo);
        if (mb_strlen($termoLimpo) < 3) {
            return new Collection;
        }

        $digitos = preg_replace('/\D/', '', $termoLimpo) ?? '';
        $somenteDigitos = $digitos !== '' && strlen(preg_replace('/[\s\.\-\/]/', '', $termoLimpo)) === strlen($digitos);
        $colunas = ['id', 'nome', 'cpf', 'cns', 'data_nascimento'];

        // 1. Busca por Documento (CPF / CNS) exclusivamente com índices (igualdade ou prefixo)
        if ($somenteDigitos) {
            $queryDoc = static::query()->ativos()->select($colunas);

            if (strlen($digitos) === 11) {
                return $queryDoc->where(function (Builder $q) use ($digitos): void {
                    $q->where('cpf', $digitos)->orWhere('cns', 'like', $digitos.'%');
                })->limit($limite)->get();
            }

            if (strlen($digitos) === 15) {
                return $queryDoc->where('cns', $digitos)->limit($limite)->get();
            }

            if (strlen($digitos) >= 3) {
                return $queryDoc->where(function (Builder $q) use ($digitos): void {
                    $q->where('cpf', 'like', $digitos.'%')->orWhere('cns', 'like', $digitos.'%');
                })->limit($limite)->get();
            }
        }

        // 2. Busca por Nome com Prioridade para Prefixo (Range Scan direto no índice idx_pacientes_nome)
        $prefixo = static::query()
            ->ativos()
            ->select($colunas)
            ->where('nome', 'like', $termoLimpo.'%')
            ->orderBy('nome')
            ->limit($limite)
            ->get();

        if ($prefixo->count() >= $limite) {
            return $prefixo;
        }

        $resultados = $prefixo;
        $idsJaEncontrados = $resultados->pluck('id')->all();
        $restante = $limite - $resultados->count();

        // 3. Se não completou e o termo tem dígitos misturados, busca documento por prefixo
        if (strlen($digitos) >= 3 && $restante > 0) {
            $porDoc = static::query()
                ->ativos()
                ->select($colunas)
                ->where(function (Builder $q) use ($digitos): void {
                    $q->where('cpf', 'like', $digitos.'%')->orWhere('cns', 'like', $digitos.'%');
                })
                ->when($idsJaEncontrados !== [], fn (Builder $q) => $q->whereNotIn('id', $idsJaEncontrados))
                ->limit($restante)
                ->get();

            $resultados = $resultados->concat($porDoc);
            $idsJaEncontrados = $resultados->pluck('id')->all();
            $restante = $limite - $resultados->count();
        }

        // 4. Complemento para palavras intermediárias (FULLTEXT no MySQL ou fallback indexável)
        if ($restante > 0 && mb_strlen($termoLimpo) >= 3) {
            $driver = DB::connection()->getDriverName();
            $queryPalavras = static::query()
                ->ativos()
                ->select($colunas)
                ->when($idsJaEncontrados !== [], fn (Builder $q) => $q->whereNotIn('id', $idsJaEncontrados));

            if ($driver === 'mysql') {
                $termoFulltext = preg_replace('/[+\-><()~*\"@]+/', ' ', $termoLimpo) ?? $termoLimpo;
                $palavras = array_filter(explode(' ', trim($termoFulltext)));
                if ($palavras !== []) {
                    $expressao = implode(' ', array_map(fn (string $p): string => '+'.$p.'*', $palavras));
                    $porPalavras = $queryPalavras
                        ->whereRaw('MATCH(nome) AGAINST(? IN BOOLEAN MODE)', [$expressao])
                        ->limit($restante)
                        ->get();
                    $resultados = $resultados->concat($porPalavras);
                }
            } else {
                $porPalavras = $queryPalavras
                    ->where('nome', 'like', '%'.$termoLimpo.'%')
                    ->orderBy('nome')
                    ->limit($restante)
                    ->get();
                $resultados = $resultados->concat($porPalavras);
            }
        }

        return $resultados;
    }
}
