<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ConfiguracaoEstabelecimento;
use App\Services\AudioSynthesisService;
use App\Services\TvPairingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AudioPainelController extends Controller
{
    /**
     * Sintetiza e transmite o áudio de anúncio de chamada em formato WAV.
     */
    public function sintetizar(
        Request $request,
        TvPairingService $pairingService,
        AudioSynthesisService $synthesisService
    ): Response {
        if (! $this->estaAutorizado($request, $pairingService)) {
            abort(403, 'Acesso não autorizado ao fluxo de áudio do painel.');
        }

        $texto = (string) $request->query('texto', '');
        if (trim($texto) === '') {
            abort(400, 'Texto de anúncio não informado.');
        }

        $audioWav = $synthesisService->sintetizar($texto);
        if ($audioWav === null || strlen($audioWav) < 44) {
            abort(500, 'Falha ao processar síntese de áudio.');
        }

        return response($audioWav, 200, [
            'Content-Type' => 'audio/wav',
            'Content-Length' => (string) strlen($audioWav),
            'Cache-Control' => 'private, max-age=86400, no-transform',
            'Accept-Ranges' => 'bytes',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Retorna o status operacional do motor TTS local do servidor.
     */
    public function status(
        Request $request,
        TvPairingService $pairingService,
        AudioSynthesisService $synthesisService
    ): JsonResponse {
        if (! $this->estaAutorizado($request, $pairingService)) {
            abort(403, 'Acesso não autorizado.');
        }

        return response()->json($synthesisService->status());
    }

    /**
     * Valida se a requisição possui credenciais legítimas do televisor ou da equipe.
     */
    protected function estaAutorizado(Request $request, TvPairingService $pairingService): bool
    {
        // 1. Smart TV pareada via cookie seguro
        $cookieToken = $request->cookie(TvPairingService::COOKIE_NAME);
        if (! empty($cookieToken)) {
            $dispositivo = $pairingService->autenticarPorCookie($cookieToken);
            if ($dispositivo !== null) {
                return true;
            }
        }

        // 2. Colaborador interno autenticado no sistema
        if (auth()->check() && auth()->user()->hasAnyRole(['recepcao', 'terapeuta', 'faturamento', 'coordenacao'])) {
            return true;
        }

        // 3. Acesso legado via token na URL
        $token = $request->query('token');
        if (! empty($token)) {
            $configuracao = ConfiguracaoEstabelecimento::atual();
            $permitirLegado = (bool) ($configuracao->painel_permitir_token_legado ?? true);
            if ($permitirLegado && ! empty($configuracao->painel_token_acesso)) {
                if (hash_equals((string) $configuracao->painel_token_acesso, (string) $token)) {
                    return true;
                }
            }
        }

        return false;
    }
}
