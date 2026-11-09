<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\ChamadaPainel;
use App\Models\ConfiguracaoEstabelecimento;
use App\Services\TvPairingService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.tv')]
#[Title('Painel de Chamadas para TV — CER Digital')]
class PainelChamadas extends Component
{
    #[Url]
    public ?string $token = null;

    #[Locked]
    public ?int $ultimaChamadaId = null;

    #[Locked]
    public bool $autorizado = false;

    #[Locked]
    public bool $revogado = false;

    #[Locked]
    public ?string $dispositivoNome = null;

    // --- Dados do Pareamento / Ativação ---
    #[Locked]
    public ?string $solicitacaoUid = null;

    #[Locked]
    public ?string $codigoAtivacao = null;

    #[Locked]
    public ?string $segredoTemporario = null;

    #[Locked]
    public ?string $expiraEmFormatado = null;

    public int $segundosRestantes = 600;

    #[Locked]
    public bool $codigoExpirado = false;

    public function mount(TvPairingService $pairingService, ?string $token = null): void
    {
        $this->token = $token ?? request()->query('token');

        if ($this->verificarAutorizacao($pairingService)) {
            $this->autorizado = true;
            $this->revogado = false;
        } else {
            $this->autorizado = false;
            if (! $this->revogado) {
                $this->gerarNovoCodigo($pairingService);
            }
        }
    }

    /**
     * Polling periódico (a cada 4s) para verificar chamadas ou status de autorização do pareamento.
     */
    public function atualizarPainel(TvPairingService $pairingService): void
    {
        // Se já está autorizado no servidor, mantém status ativo
        if ($this->verificarAutorizacao($pairingService)) {
            $this->autorizado = true;
            $this->revogado = false;

            return;
        }

        // Não autorizado no servidor
        $this->autorizado = false;

        // Se estiver na tela de ativação, consulta se a Coordenação já autorizou o código
        if ($this->solicitacaoUid !== null && $this->segredoTemporario !== null && ! $this->codigoExpirado) {
            $resultado = $pairingService->verificarStatusPareamento($this->solicitacaoUid, $this->segredoTemporario);

            if ($resultado['status'] === 'APROVADO' && $resultado['token'] !== null) {
                // Entrega da credencial definitiva por Cookie persistente HttpOnly
                cookie()->queue(
                    TvPairingService::COOKIE_NAME,
                    $resultado['token'],
                    TvPairingService::TEMPO_EXPIRACAO_COOKIE_MINUTOS,
                    '/',
                    null,
                    request()->secure(),
                    true,
                    false,
                    'Lax'
                );

                // Define no request atual para que a autorização imediata nesta mesma requisição funcione
                request()->cookies->set(TvPairingService::COOKIE_NAME, $resultado['token']);

                $this->autorizado = true;
                $this->revogado = false;
                $this->dispositivoNome = $resultado['dispositivo']?->nome ?? 'Smart TV';

                // Limpa segredos temporários da memória
                $this->solicitacaoUid = null;
                $this->codigoAtivacao = null;
                $this->segredoTemporario = null;

                $this->dispatch('tv-autorizada', ['nome' => $this->dispositivoNome]);
            } elseif ($resultado['status'] === 'EXPIRADO') {
                $this->codigoExpirado = true;
            }
        }
    }

    public function gerarNovoCodigo(TvPairingService $pairingService): void
    {
        try {
            $dados = $pairingService->iniciarSolicitacaoPareamento(
                request()->ip(),
                request()->userAgent()
            );

            $this->solicitacaoUid = $dados['solicitacao']->solicitacao_uid;
            $this->codigoAtivacao = $dados['codigo'];
            $this->segredoTemporario = $dados['segredo_temporario'];
            $this->expiraEmFormatado = $dados['solicitacao']->expira_em->format('H:i');
            $this->segundosRestantes = 600;
            $this->codigoExpirado = false;
            $this->revogado = false;
            $this->autorizado = false;
        } catch (ValidationException) {
            $this->codigoExpirado = true;
        }
    }

    /**
     * Valida os 3 métodos de autorização admitidos exclusivamente no servidor:
     * 1. Cookie individual persistente HttpOnly de TV pareada;
     * 2. Sessão autenticada de funcionário do CER;
     * 3. Token legado na URL (se habilitado pela Coordenação).
     */
    protected function verificarAutorizacao(TvPairingService $pairingService): bool
    {
        // 1. Cookie persistente de Smart TV individual
        $cookieToken = request()->cookie(TvPairingService::COOKIE_NAME);
        if (! empty($cookieToken)) {
            $dispositivo = $pairingService->autenticarPorCookie((string) $cookieToken);
            if ($dispositivo !== null) {
                $this->dispositivoNome = $dispositivo->nome;
                $this->revogado = false;

                return true;
            }

            // Se o cookie foi fornecido mas não autentica, a TV foi revogada ou a credencial é inválida
            $this->revogado = true;
            $this->autorizado = false;
            cookie()->queue(cookie()->forget(TvPairingService::COOKIE_NAME));

            return false;
        }

        // 2. Usuário interno autenticado (médico, terapeuta, recepção ou coordenação)
        if (auth()->check() && auth()->user()->hasAnyRole(['recepcao', 'terapeuta', 'faturamento', 'coordenacao'])) {
            $this->dispositivoNome = auth()->user()->name.' (Sessão Web)';
            $this->revogado = false;

            return true;
        }

        // 3. Acesso legado por token global na URL (durante transição)
        $configuracao = $this->obterConfiguracao();
        $permitirLegado = (bool) ($configuracao->painel_permitir_token_legado ?? true);

        if ($permitirLegado && ! empty($configuracao->painel_token_acesso) && ! empty($this->token)) {
            if (hash_equals((string) $configuracao->painel_token_acesso, (string) $this->token)) {
                $this->dispositivoNome = 'Acesso Transitório por URL';
                $this->revogado = false;

                return true;
            }
        }

        return false;
    }

    protected function obterConfiguracao(): ConfiguracaoEstabelecimento
    {
        return ConfiguracaoEstabelecimento::atual();
    }

    public function render(TvPairingService $pairingService): View
    {
        $configuracao = $this->obterConfiguracao();

        // VALIDAÇÃO CRÍTICA DE SEGURANÇA:
        // O servidor NUNCA confia no estado de $this->autorizado vindo do cliente ou requisições anteriores.
        // A autorização é revalidada no servidor antes de consultar qualquer informação de paciente.
        $estaRealmenteAutorizado = $this->verificarAutorizacao($pairingService);
        $this->autorizado = $estaRealmenteAutorizado;

        if (! $estaRealmenteAutorizado) {
            // Dispositivo não autorizado: exibe exclusivamente a tela de pareamento ou de revogação.
            // Nenhum registro de paciente é consultado ou retornado.
            return view('livewire.painel-chamadas', [
                'autorizado' => false,
                'revogado' => $this->revogado,
                'codigoAtivacao' => $this->codigoAtivacao,
                'expiraEmFormatado' => $this->expiraEmFormatado,
                'codigoExpirado' => $this->codigoExpirado,
                'dispositivoNome' => $this->dispositivoNome,
                'configuracao' => $configuracao,
                'chamadaAtual' => null,
                'pacienteNomeAtual' => '',
                'historico' => collect(),
            ]);
        }

        // --- DISPOSITIVO AUTORIZADO NO SERVIDOR ---
        // Otimização de Performance VPS: verifica se houve nova chamada antes de carregar o histórico completo
        $limiteHistorico = max(1, min(15, (int) ($configuracao->painel_quantidade_historico ?? 5)));

        // Consulta leve de 1 coluna (índice primário) para saber o ID da última chamada do dia
        $ultimoIdHoje = (int) (ChamadaPainel::query()
            ->doDia()
            ->latest('id')
            ->value('id') ?? 0);

        $chamadaAtual = null;
        $pacienteNomeAtual = '';
        $textoVoz = '';
        $historico = collect();

        if ($ultimoIdHoje > 0) {
            $atual = ChamadaPainel::query()->find($ultimoIdHoje);
            if ($atual !== null) {
                $chamadaAtual = $atual;
                $pacienteNomeAtual = $configuracao->formatarNomePaciente($atual->paciente_nome);
                $textoVoz = "Paciente {$pacienteNomeAtual}, dirigir-se à sala {$atual->sala_nome}";
                $historico = ChamadaPainel::query()
                    ->doDia()
                    ->where('id', '!=', $atual->id)
                    ->orderByDesc('chamado_em')
                    ->orderByDesc('id')
                    ->limit($limiteHistorico)
                    ->get()
                    ->map(function (ChamadaPainel $item) use ($configuracao): array {
                        return [
                            'id' => $item->id,
                            'paciente_nome' => $configuracao->formatarNomePaciente($item->paciente_nome),
                            'sala_nome' => $item->sala_nome,
                            'profissional_nome' => $item->profissional_nome,
                            'tipo' => $item->tipo,
                            'hora' => $item->chamado_em->format('H:i'),
                            'segundos_atras' => max(0, $item->chamado_em->diffInSeconds(now())),
                        ];
                    });

                // Dispara evento sonoro e anúncio por voz quando há nova chamada ou repetição
                if ($chamadaAtual->id !== $this->ultimaChamadaId) {
                    $this->ultimaChamadaId = $chamadaAtual->id;
                    $this->dispatch('nova-chamada-painel', [
                        'id' => $chamadaAtual->id,
                        'paciente' => $pacienteNomeAtual,
                        'sala' => $chamadaAtual->sala_nome,
                        'profissional' => $chamadaAtual->profissional_nome,
                        'tipo' => $chamadaAtual->tipo,
                        'textoVoz' => $textoVoz,
                        'vozAtiva' => (bool) ($configuracao->painel_voz_ativa ?? true),
                        'volume' => ((int) ($configuracao->painel_volume ?? 80)) / 100,
                    ]);
                }
            }
        }

        return view('livewire.painel-chamadas', [
            'autorizado' => true,
            'revogado' => false,
            'chamadaAtual' => $chamadaAtual,
            'pacienteNomeAtual' => $pacienteNomeAtual,
            'historico' => $historico,
            'configuracao' => $configuracao,
            'dispositivoNome' => $this->dispositivoNome,
        ]);
    }
}
