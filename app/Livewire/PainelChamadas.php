<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\ChamadaPainel;
use App\Models\ConfiguracaoEstabelecimento;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.tv')]
#[Title('Painel de Chamadas para TV — CER Digital')]
class PainelChamadas extends Component
{
    #[Url]
    public ?string $token = null;

    public ?int $ultimaChamadaId = null;

    public function mount(?string $token = null): void
    {
        $this->token = $token ?? request()->query('token');

        $configuracao = ConfiguracaoEstabelecimento::atual();
        $tokenValido = ! empty($configuracao->painel_token_acesso) && hash_equals((string) $configuracao->painel_token_acesso, (string) $this->token);

        $usuarioAutenticado = auth()->check() && auth()->user()->hasAnyRole(['recepcao', 'terapeuta', 'faturamento', 'coordenacao']);

        abort_unless($tokenValido || $usuarioAutenticado, 403, 'Acesso não autorizado ao Painel de Chamadas da TV.');
    }

    public function render(): View
    {
        $configuracao = ConfiguracaoEstabelecimento::atual();
        $limiteHistorico = max(1, min(15, (int) ($configuracao->painel_quantidade_historico ?? 5)));

        $chamadaAtual = ChamadaPainel::query()
            ->doDia()
            ->orderByDesc('chamado_em')
            ->orderByDesc('id')
            ->first();

        $historico = collect();
        $pacienteNomeAtual = '';
        $textoVoz = '';

        if ($chamadaAtual !== null) {
            $pacienteNomeAtual = $configuracao->formatarNomePaciente($chamadaAtual->paciente_nome);
            $textoVoz = "Paciente {$pacienteNomeAtual}, dirigir-se à sala {$chamadaAtual->sala_nome}";

            $historico = ChamadaPainel::query()
                ->doDia()
                ->where('id', '!=', $chamadaAtual->id)
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

        return view('livewire.painel-chamadas', [
            'chamadaAtual' => $chamadaAtual,
            'pacienteNomeAtual' => $pacienteNomeAtual,
            'historico' => $historico,
            'configuracao' => $configuracao,
        ]);
    }
}
