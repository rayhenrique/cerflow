<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DispositivoTv;
use App\Models\LogAuditoria;
use App\Models\SolicitacaoPareamentoTv;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
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
     */
    public function iniciarSolicitacaoPareamento(?string $ip = null, ?string $userAgent = null): array
    {
        // Expira solicitações antigas pendentes que ultrapassaram o tempo limite de 10 minutos
        SolicitacaoPareamentoTv::query()
            ->where('status', 'PENDENTE')
            ->where('expira_em', '<', now())
            ->update(['status' => 'EXPIRADO']);

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
     *
     * @return array{status: string, token: ?string, dispositivo: ?DispositivoTv}
     */
    public function verificarStatusPareamento(string $solicitacaoUid, string $segredoTemporario): array
    {
        $solicitacao = SolicitacaoPareamentoTv::query()
            ->with('dispositivo')
            ->where('solicitacao_uid', $solicitacaoUid)
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

        $solicitacao = SolicitacaoPareamentoTv::query()
            ->validas()
            ->where('codigo', $codNormalizado)
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

        // Cache leve de 60 segundos para evitar hits excessivos ao MySQL durante o polling da TV
        /** @var ?DispositivoTv $dispositivo */
        $dispositivo = Cache::remember("tv_auth_{$tokenHash}", 60, function () use ($tokenHash) {
            return DispositivoTv::query()
                ->where('token_hash', $tokenHash)
                ->first();
        });

        if ($dispositivo === null || ! $dispositivo->estaAtivo()) {
            return null;
        }

        $dispositivo->registrarAcesso(request()->ip(), request()->userAgent());

        return $dispositivo;
    }

    /**
     * Revoga imediatamente um dispositivo autorizado.
     */
    public function revogarDispositivo(DispositivoTv $dispositivo, User $usuario): void
    {
        $dispositivo->refresh();
        $hash = $dispositivo->token_hash;
        $dispositivo->revogar($usuario);

        Cache::forget("tv_auth_{$hash}");

        LogAuditoria::create([
            'user_id' => $usuario->id,
            'acao' => 'REVOGACAO_DISPOSITIVO_TV',
            'tabela_afetada' => 'dispositivos_tv',
            'registro_id' => $dispositivo->id,
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'detalhes' => [
                'nome' => $dispositivo->nome,
                'identificador' => $dispositivo->identificador,
                'motivo' => 'Revogação manual executada pela Coordenação.',
            ],
            'created_at' => now(),
        ]);
    }

    /**
     * Reautoriza um dispositivo que foi previamente revogado.
     */
    public function reautorizarDispositivo(DispositivoTv $dispositivo, User $usuario): void
    {
        $dispositivo->refresh();
        $dispositivo->update([
            'status' => 'ATIVO',
            'revogado_em' => null,
            'revogado_por_id' => null,
            'expira_em' => now()->addYear(),
        ]);

        Cache::forget("tv_auth_{$dispositivo->token_hash}");

        LogAuditoria::create([
            'user_id' => $usuario->id,
            'acao' => 'REAUTORIZACAO_DISPOSITIVO_TV',
            'tabela_afetada' => 'dispositivos_tv',
            'registro_id' => $dispositivo->id,
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'detalhes' => [
                'nome' => $dispositivo->nome,
                'identificador' => $dispositivo->identificador,
            ],
            'created_at' => now(),
        ]);
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
