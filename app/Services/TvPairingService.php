<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DispositivoTv;
use App\Models\LogAuditoria;
use App\Models\SolicitacaoPareamentoTv;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TvPairingService
{
    public const COOKIE_NAME = 'cer_tv_token';

    public const TEMPO_EXPIRACAO_COOKIE_MINUTOS = 525600; // 1 ano (365 dias)

    /**
     * Inicia uma nova solicitação de pareamento para uma Smart TV.
     * Gera um código de 6 caracteres legível e um segredo temporário para verificação mútua.
     *
     * @return array{solicitacao: SolicitacaoPareamentoTv, segredo_temporario: string, codigo: string}
     *
     * @throws ValidationException
     */
    public function iniciarSolicitacaoPareamento(?string $ip = null, ?string $userAgent = null): array
    {
        $ipChave = $ip ?? '127.0.0.1';
        $limiterKey = "tv_pairing_req_{$ipChave}";

        // Limita a 10 criações de código por IP a cada 10 minutos para impedir flooding/DoS
        if (RateLimiter::tooManyAttempts($limiterKey, 10)) {
            $segundos = RateLimiter::availableIn($limiterKey);
            throw ValidationException::withMessages([
                'rate_limit' => "Limite de solicitações de pareamento excedido. Aguarde {$segundos} segundos antes de tentar novamente.",
            ]);
        }
        RateLimiter::hit($limiterKey, 600);

        // Expira solicitações antigas pendentes que ultrapassaram o tempo limite de 10 minutos
        SolicitacaoPareamentoTv::query()
            ->where('status', 'PENDENTE')
            ->where('expira_em', '<', now())
            ->update(['status' => 'EXPIRADO']);

        // Cancela solicitações pendentes anteriores da mesma origem para não poluir a base
        if (! empty($ip)) {
            SolicitacaoPareamentoTv::query()
                ->where('ip_origem', $ip)
                ->where('status', 'PENDENTE')
                ->update(['status' => 'CANCELADO']);
        }

        $codigo = SolicitacaoPareamentoTv::gerarCodigoLegivel();
        $segredoTemporario = Str::random(64);
        $solicitacaoUid = (string) Str::uuid();

        $solicitacao = SolicitacaoPareamentoTv::create([
            'solicitacao_uid' => $solicitacaoUid,
            'codigo' => $codigo,
            'segredo_temporario_hash' => hash('sha256', $segredoTemporario),
            'status' => 'PENDENTE',
            'expira_em' => now()->addMinutes(10),
            'ip_origem' => $ip,
            'user_agent' => $userAgent ? Str::limit($userAgent, 500) : null,
            'tentativas' => 0,
        ]);

        return [
            'solicitacao' => $solicitacao,
            'segredo_temporario' => $segredoTemporario,
            'codigo' => $codigo,
        ];
    }

    /**
     * Consulta o estado do pareamento. Se aprovado pela coordenação, emite o token permanente e consome a solicitação.
     * Utiliza transação com lock pessimista para evitar concorrência e condições de corrida na emissão da credencial.
     *
     * @return array{status: string, token: ?string, dispositivo: ?DispositivoTv}
     */
    public function verificarStatusPareamento(string $solicitacaoUid, string $segredoTemporario): array
    {
        return DB::transaction(function () use ($solicitacaoUid, $segredoTemporario) {
            $solicitacao = SolicitacaoPareamentoTv::query()
                ->with('dispositivo')
                ->where('solicitacao_uid', $solicitacaoUid)
                ->lockForUpdate()
                ->first();

            if ($solicitacao === null) {
                return ['status' => 'INVALIDO', 'token' => null, 'dispositivo' => null];
            }

            if (! hash_equals($solicitacao->segredo_temporario_hash, hash('sha256', $segredoTemporario))) {
                return ['status' => 'INVALIDO', 'token' => null, 'dispositivo' => null];
            }

            if ($solicitacao->status === 'APROVADO' && $solicitacao->dispositivo !== null) {
                // Gera a credencial definitiva que será entregue apenas neste momento via Cookie HttpOnly
                $tokenPermanente = Str::random(64);
                $tokenHash = hash('sha256', $tokenPermanente);

                $solicitacao->dispositivo->update([
                    'token_hash' => $tokenHash,
                    'ultimo_acesso_em' => now(),
                ]);

                $solicitacao->update(['status' => 'CONCLUIDO']);
                Cache::forget("tv_auth_{$tokenHash}");

                return [
                    'status' => 'APROVADO',
                    'token' => $tokenPermanente,
                    'dispositivo' => $solicitacao->dispositivo,
                ];
            }

            if ($solicitacao->status === 'PENDENTE') {
                if ($solicitacao->estaExpirada()) {
                    $solicitacao->update(['status' => 'EXPIRADO']);

                    return ['status' => 'EXPIRADO', 'token' => null, 'dispositivo' => null];
                }

                return ['status' => 'PENDENTE', 'token' => null, 'dispositivo' => null];
            }

            return ['status' => $solicitacao->status, 'token' => null, 'dispositivo' => null];
        });
    }

    /**
     * Autoriza uma solicitação pendente informada pela coordenação.
     *
     * @throws ValidationException
     */
    public function autorizarCodigo(
        string $codigo,
        string $nomeDispositivo,
        User $autorizadoPor,
        ?string $ip = null,
        ?string $userAgent = null
    ): DispositivoTv {
        // Proteção contra adivinhação por força bruta (Rate limiting de 5 tentativas por 10 minutos por IP)
        $ipChave = $ip ?? '127.0.0.1';
        $limiterKey = "tv_auth_attempts_{$ipChave}";

        if (RateLimiter::tooManyAttempts($limiterKey, 5)) {
            $segundos = RateLimiter::availableIn($limiterKey);
            throw ValidationException::withMessages([
                'tv_codigo_ativacao' => "Limite de tentativas excedido. Tente novamente em {$segundos} segundos.",
            ]);
        }

        $codNormalizado = SolicitacaoPareamentoTv::normalizarCodigo($codigo);

        return DB::transaction(function () use ($codNormalizado, $nomeDispositivo, $autorizadoPor, $ip, $userAgent, $limiterKey) {
            $solicitacao = SolicitacaoPareamentoTv::query()
                ->validas()
                ->where('codigo', $codNormalizado)
                ->lockForUpdate()
                ->first();

            if ($solicitacao === null) {
                RateLimiter::hit($limiterKey, 600);
                throw ValidationException::withMessages([
                    'tv_codigo_ativacao' => 'Código de ativação inválido ou já expirado. Certifique-se de que a TV está na tela de pareamento.',
                ]);
            }

            RateLimiter::clear($limiterKey);

            $nomeFinal = trim($nomeDispositivo);
            if ($nomeFinal === '') {
                $nomeFinal = "Smart TV ({$codNormalizado})";
            }

            $dispositivo = DispositivoTv::create([
                'nome' => $nomeFinal,
                'identificador' => 'tv_'.Str::lower(Str::random(16)),
                'token_hash' => hash('sha256', Str::random(64)), // Hash provisório até a entrega na finalização
                'status' => 'ATIVO',
                'autorizado_por_id' => $autorizadoPor->id,
                'autorizado_em' => now(),
                'expira_em' => now()->addYear(),
                'ip_origem' => $solicitacao->ip_origem ?? $ip,
                'user_agent' => $solicitacao->user_agent ?? $userAgent,
            ]);

            $solicitacao->update([
                'status' => 'APROVADO',
                'dispositivo_tv_id' => $dispositivo->id,
            ]);

            LogAuditoria::create([
                'user_id' => $autorizadoPor->id,
                'acao' => 'AUTORIZACAO_DISPOSITIVO_TV',
                'tabela_afetada' => 'dispositivos_tv',
                'registro_id' => $dispositivo->id,
                'ip_address' => $ip ?? '127.0.0.1',
                'detalhes' => [
                    'nome' => $dispositivo->nome,
                    'codigo' => $codNormalizado,
                    'identificador' => $dispositivo->identificador,
                ],
                'created_at' => now(),
            ]);

            return $dispositivo;
        });
    }

    /**
     * Valida a credencial persistente enviada via Cookie pelo navegador da TV.
     */
    public function autenticarPorCookie(?string $tokenCookie): ?DispositivoTv
    {
        if (empty($tokenCookie) || ! is_string($tokenCookie)) {
            return null;
        }

        $tokenHash = hash('sha256', $tokenCookie);

        // Cache leve do ID do dispositivo por 60 segundos evitando a serialização de Modelos Eloquent no cache
        $dispositivoId = Cache::remember("tv_auth_id_{$tokenHash}", 60, function () use ($tokenHash): ?int {
            return DispositivoTv::query()
                ->where('token_hash', $tokenHash)
                ->value('id');
        });

        if (! $dispositivoId) {
            return null;
        }

        /** @var ?DispositivoTv $dispositivo */
        $dispositivo = DispositivoTv::query()->find($dispositivoId);

        if ($dispositivo === null || ! $dispositivo->estaAtivo()) {
            return null;
        }

        $dispositivo->registrarAcesso(request()->ip(), request()->userAgent());

        return $dispositivo;
    }

    /**
     * Revoga imediatamente e em definitivo um dispositivo autorizado.
     * Destrói a credencial anterior do banco e limpa o cache.
     */
    public function revogarDispositivo(DispositivoTv $dispositivo, User $usuario): void
    {
        $dispositivo->refresh();
        $hashAntigo = $dispositivo->token_hash;
        $dispositivo->revogar($usuario);

        Cache::forget("tv_auth_{$hashAntigo}");
        Cache::forget("tv_auth_id_{$hashAntigo}");

        LogAuditoria::create([
            'user_id' => $usuario->id,
            'acao' => 'REVOGACAO_DISPOSITIVO_TV',
            'tabela_afetada' => 'dispositivos_tv',
            'registro_id' => $dispositivo->id,
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'detalhes' => [
                'nome' => $dispositivo->nome,
                'identificador' => $dispositivo->identificador,
                'motivo' => 'Revogação manual definitiva executada pela Coordenação.',
            ],
            'created_at' => now(),
        ]);
    }

    /**
     * Reautoriza um televisor previamente revogado, EXIGINDO OBRIGATORIAMENTE um novo pareamento.
     * A credencial anterior continua permanentemente revogada e uma nova credencial única será emitida.
     *
     * @throws ValidationException
     */
    public function reautorizarDispositivo(
        DispositivoTv $dispositivo,
        string $novoCodigo,
        User $usuario,
        ?string $ip = null,
        ?string $userAgent = null
    ): DispositivoTv {
        $codNormalizado = SolicitacaoPareamentoTv::normalizarCodigo($novoCodigo);

        return DB::transaction(function () use ($dispositivo, $codNormalizado, $usuario, $ip, $userAgent) {
            $solicitacao = SolicitacaoPareamentoTv::query()
                ->validas()
                ->where('codigo', $codNormalizado)
                ->lockForUpdate()
                ->first();

            if ($solicitacao === null) {
                throw ValidationException::withMessages([
                    'tv_codigo_reautorizacao' => 'Código de ativação inválido ou já expirado. Certifique-se de que a TV está na tela de pareamento com este código.',
                ]);
            }

            $dispositivo->refresh();
            $hashAntigo = $dispositivo->token_hash;
            Cache::forget("tv_auth_{$hashAntigo}");
            Cache::forget("tv_auth_id_{$hashAntigo}");

            // Gera hash provisório novo (substituindo o antigo definitivamente)
            $novoHashProvisorio = hash('sha256', Str::random(64));

            $dispositivo->update([
                'token_hash' => $novoHashProvisorio,
                'status' => 'ATIVO',
                'revogado_em' => null,
                'revogado_por_id' => null,
                'autorizado_por_id' => $usuario->id,
                'autorizado_em' => now(),
                'expira_em' => now()->addYear(),
                'ip_origem' => $solicitacao->ip_origem ?? $ip ?? $dispositivo->ip_origem,
                'user_agent' => $solicitacao->user_agent ?? $userAgent ?? $dispositivo->user_agent,
            ]);

            $solicitacao->update([
                'status' => 'APROVADO',
                'dispositivo_tv_id' => $dispositivo->id,
            ]);

            LogAuditoria::create([
                'user_id' => $usuario->id,
                'acao' => 'REAUTORIZACAO_DISPOSITIVO_TV',
                'tabela_afetada' => 'dispositivos_tv',
                'registro_id' => $dispositivo->id,
                'ip_address' => $ip ?? request()->ip() ?? '127.0.0.1',
                'detalhes' => [
                    'nome' => $dispositivo->nome,
                    'codigo' => $codNormalizado,
                    'identificador' => $dispositivo->identificador,
                    'mensagem' => 'Reautorização concluída com exigência de novo pareamento e descarte da credencial anterior.',
                ],
                'created_at' => now(),
            ]);

            return $dispositivo;
        });
    }

    /**
     * Renomeia um dispositivo de TV existente.
     */
    public function renomearDispositivo(DispositivoTv $dispositivo, string $novoNome, User $usuario): void
    {
        $nomeAntigo = $dispositivo->nome;
        $nomeLimpo = trim($novoNome);

        if ($nomeLimpo === '' || $nomeLimpo === $nomeAntigo) {
            return;
        }

        $dispositivo->update(['nome' => $nomeLimpo]);

        LogAuditoria::create([
            'user_id' => $usuario->id,
            'acao' => 'RENOMEACAO_DISPOSITIVO_TV',
            'tabela_afetada' => 'dispositivos_tv',
            'registro_id' => $dispositivo->id,
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'detalhes' => [
                'de' => $nomeAntigo,
                'para' => $nomeLimpo,
            ],
            'created_at' => now(),
        ]);
    }

    /**
     * Exclui um dispositivo de TV da listagem.
     */
    public function excluirDispositivo(DispositivoTv $dispositivo, User $usuario): void
    {
        $dispositivo->refresh();
        $hash = $dispositivo->token_hash;
        Cache::forget("tv_auth_{$hash}");
        Cache::forget("tv_auth_id_{$hash}");

        LogAuditoria::create([
            'user_id' => $usuario->id,
            'acao' => 'EXCLUSAO_DISPOSITIVO_TV',
            'tabela_afetada' => 'dispositivos_tv',
            'registro_id' => $dispositivo->id,
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'detalhes' => [
                'nome' => $dispositivo->nome,
                'identificador' => $dispositivo->identificador,
            ],
            'created_at' => now(),
        ]);

        $dispositivo->delete();
    }
}
