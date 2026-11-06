<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\LogAuditoria;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditAccessLog
{
    /**
     * Registra consultas ao prontuário eletrônico (PEP) e downloads de remessas BPA-i.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $acaoExplicita = null): Response
    {
        $response = $next($request);

        $user = $request->user();
        if ($user === null) {
            return $response;
        }

        [$acao, $tabelaAfetada] = $this->resolverAcaoETabela($request, $acaoExplicita);

        if ($acao === null) {
            return $response;
        }

        $registroId = $this->extrairRegistroId($request);

        LogAuditoria::create([
            'user_id' => $user->id,
            'acao' => $acao,
            'tabela_afetada' => $tabelaAfetada,
            'registro_id' => $registroId,
            'ip_address' => (string) ($request->ip() ?? '127.0.0.1'),
            'detalhes' => [
                'method' => $request->method(),
                'path' => $request->path(),
                'route' => $request->route()?->getName(),
                'status_code' => $response->getStatusCode(),
            ],
            'created_at' => now(),
        ]);

        return $response;
    }

    /**
     * @return array{0: ?string, 1: string}
     */
    private function resolverAcaoETabela(Request $request, ?string $acaoExplicita): array
    {
        if ($acaoExplicita !== null) {
            $tabela = match ($acaoExplicita) {
                'EXPORTACAO_BPA' => 'producoes_bpai',
                'ASSINATURA_EVOLUCAO' => 'evolucoes_clinicas',
                default => 'evolucoes_clinicas',
            };

            return [$acaoExplicita, $tabela];
        }

        $path = strtolower($request->path());
        $routeName = strtolower((string) $request->route()?->getName());

        if (
            str_contains($path, 'faturamento/export') ||
            str_contains($path, 'bpai/export') ||
            str_contains($path, 'bpa/download') ||
            str_contains($routeName, 'export')
        ) {
            return ['EXPORTACAO_BPA', 'producoes_bpai'];
        }

        if (
            str_contains($path, 'prontuario') ||
            str_contains($path, 'pep') ||
            str_contains($path, 'evoluc') ||
            str_contains($routeName, 'prontuario') ||
            str_contains($routeName, 'pep')
        ) {
            return ['ACESSO_PEP', 'evolucoes_clinicas'];
        }

        return [null, ''];
    }

    private function extrairRegistroId(Request $request): ?int
    {
        $route = $request->route();
        if ($route === null) {
            return null;
        }

        foreach (['paciente', 'evolucao', 'agendamento', 'id'] as $param) {
            $valor = $route->parameter($param);
            if ($valor instanceof Model) {
                return (int) $valor->getKey();
            }
            if (is_numeric($valor)) {
                return (int) $valor;
            }
        }

        return null;
    }
}
