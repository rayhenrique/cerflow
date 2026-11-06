<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Agendamento;
use App\Models\EvolucaoAdendo;
use App\Models\EvolucaoClinica;
use App\Models\LogAuditoria;
use App\Models\ProcedimentoSigtap;
use App\Models\ProducaoBpai;
use App\Models\Profissional;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EvolucaoService
{
    /**
     * Assina eletronicamente uma evolução clínica (PIN + SHA-256) e gera automaticamente o registro em producoes_bpai.
     *
     * @param  array{queixa_estado_atual: string, conduta_terapeutica: string, intercorrencias?: ?string, cid_atendimento?: ?string}  $dadosClinicos
     */
    public function registrarEAssinar(
        Agendamento $agendamento,
        Profissional $profissional,
        ProcedimentoSigtap $procedimento,
        array $dadosClinicos,
        string $pinAssinatura,
        ?CarbonInterface $assinadoEm = null,
        ?string $ipAddress = null
    ): EvolucaoClinica {
        if (! Hash::check($pinAssinatura, $profissional->pin_assinatura_hash)) {
            throw ValidationException::withMessages([
                'pin_assinatura' => 'PIN de assinatura eletrônica inválido para o profissional.',
            ]);
        }

        return DB::transaction(function () use (
            $agendamento,
            $profissional,
            $procedimento,
            $dadosClinicos,
            $assinadoEm,
            $ipAddress
        ): EvolucaoClinica {
            $agendamento->loadMissing('paciente');
            $paciente = $agendamento->paciente;
            $momentoAssinatura = $assinadoEm ?? now();

            $queixa = trim((string) $dadosClinicos['queixa_estado_atual']);
            $conduta = trim((string) $dadosClinicos['conduta_terapeutica']);
            $intercorrencias = isset($dadosClinicos['intercorrencias']) && trim((string) $dadosClinicos['intercorrencias']) !== ''
                ? trim((string) $dadosClinicos['intercorrencias'])
                : null;

            $hash = $this->calcularHashAssinatura(
                pacienteId: (int) $paciente->id,
                profissionalId: (int) $profissional->id,
                assinadoEm: $momentoAssinatura,
                queixaEstadoAtual: $queixa,
                condutaTerapeutica: $conduta,
                intercorrencias: $intercorrencias,
            );

            $evolucao = EvolucaoClinica::updateOrCreate(
                [
                    'agendamento_id' => $agendamento->id,
                    'profissional_id' => $profissional->id,
                ],
                [
                    'paciente_id' => $paciente->id,
                    'queixa_estado_atual' => $queixa,
                    'conduta_terapeutica' => $conduta,
                    'intercorrencias' => $intercorrencias,
                    'assinado_em' => $momentoAssinatura,
                    'assinatura_hash' => $hash,
                    'bloqueado_edicao' => false,
                ]
            );

            $dataAtendimento = $agendamento->data_atendimento;
            $competencia = $dataAtendimento->format('Ym');
            $cidAtendimento = (string) ($dadosClinicos['cid_atendimento'] ?? $paciente->cid_principal);

            ProducaoBpai::updateOrCreate(
                [
                    'evolucao_id' => $evolucao->id,
                ],
                [
                    'paciente_id' => $paciente->id,
                    'profissional_id' => $profissional->id,
                    'procedimento_id' => $procedimento->id,
                    'cbo_executante' => $profissional->cbo_padrao,
                    'cid_atendimento' => $cidAtendimento,
                    'data_atendimento' => $dataAtendimento->toDateString(),
                    'competencia' => $competencia,
                    'status_exportacao' => 'PENDENTE',
                    'mensagem_inconsistencia' => null,
                ]
            );

            if ($agendamento->status !== 'ATENDIDO') {
                $agendamento->update(['status' => 'ATENDIDO']);
            }

            if ($profissional->user_id !== null) {
                LogAuditoria::create([
                    'user_id' => $profissional->user_id,
                    'acao' => 'ASSINATURA_EVOLUCAO',
                    'tabela_afetada' => 'evolucoes_clinicas',
                    'registro_id' => $evolucao->id,
                    'ip_address' => $ipAddress ?? '127.0.0.1',
                    'detalhes' => [
                        'agendamento_id' => $agendamento->id,
                        'paciente_id' => $paciente->id,
                        'profissional_id' => $profissional->id,
                        'procedimento_codigo' => $procedimento->codigo,
                        'cbo_executante' => $profissional->cbo_padrao,
                        'assinatura_hash' => $hash,
                    ],
                    'created_at' => now(),
                ]);
            }

            return $evolucao->load(['producaoBpai', 'profissional', 'paciente', 'adendos']);
        });
    }

    /**
     * RN04 - Desmembramento de Produção Compartilhada:
     * Registra evoluções individuais para cada profissional participante de um Atendimento Compartilhado,
     * gerando linhas independentes na tabela producoes_bpai com o respectivo CBO e procedimento SIGTAP.
     *
     * @param  array<int, array{profissional: Profissional, procedimento: ProcedimentoSigtap, queixa_estado_atual: string, conduta_terapeutica: string, intercorrencias?: ?string, pin_assinatura: string}>  $evolucoesPorProfissional
     * @return Collection<int, EvolucaoClinica>
     */
    public function registrarAtendimentoCompartilhado(Agendamento $agendamento, array $evolucoesPorProfissional): Collection
    {
        return DB::transaction(function () use ($agendamento, $evolucoesPorProfissional): Collection {
            $evolucoes = collect();

            foreach ($evolucoesPorProfissional as $item) {
                $evolucao = $this->registrarEAssinar(
                    agendamento: $agendamento,
                    profissional: $item['profissional'],
                    procedimento: $item['procedimento'],
                    dadosClinicos: [
                        'queixa_estado_atual' => $item['queixa_estado_atual'],
                        'conduta_terapeutica' => $item['conduta_terapeutica'],
                        'intercorrencias' => $item['intercorrencias'] ?? null,
                    ],
                    pinAssinatura: $item['pin_assinatura'],
                );

                $evolucoes->push($evolucao);
            }

            return $evolucoes;
        });
    }

    /**
     * Atualiza uma evolução clínica respeitando estritamente a EvolucaoPolicy (autoria + bloqueio de 24 horas).
     *
     * @param  array{queixa_estado_atual: string, conduta_terapeutica: string, intercorrencias?: ?string}  $dados
     */
    public function atualizarEvolucao(User $user, EvolucaoClinica $evolucao, array $dados): EvolucaoClinica
    {
        Gate::forUser($user)->authorize('update', $evolucao);

        $queixa = trim((string) $dados['queixa_estado_atual']);
        $conduta = trim((string) $dados['conduta_terapeutica']);
        $intercorrencias = isset($dados['intercorrencias']) && trim((string) $dados['intercorrencias']) !== ''
            ? trim((string) $dados['intercorrencias'])
            : null;

        $novoHash = $this->calcularHashAssinatura(
            pacienteId: (int) $evolucao->paciente_id,
            profissionalId: (int) $evolucao->profissional_id,
            assinadoEm: $evolucao->assinado_em,
            queixaEstadoAtual: $queixa,
            condutaTerapeutica: $conduta,
            intercorrencias: $intercorrencias,
        );

        $evolucao->update([
            'queixa_estado_atual' => $queixa,
            'conduta_terapeutica' => $conduta,
            'intercorrencias' => $intercorrencias,
            'assinatura_hash' => $novoHash,
        ]);

        return $evolucao->refresh();
    }

    /**
     * RF3.5 - Registra um termo de adendo retificador vinculado à evolução original (para ajustes após 24h).
     */
    public function registrarAdendo(
        User $user,
        EvolucaoClinica $evolucao,
        string $justificativa,
        string $textoAdendo,
        string $pinAssinatura
    ): EvolucaoAdendo {
        Gate::forUser($user)->authorize('createAdendo', $evolucao);

        $profissional = $user->profissional;
        if ($profissional === null || ! Hash::check($pinAssinatura, $profissional->pin_assinatura_hash)) {
            throw ValidationException::withMessages([
                'pin_assinatura' => 'PIN de assinatura eletrônica inválido.',
            ]);
        }

        return EvolucaoAdendo::create([
            'evolucao_id' => $evolucao->id,
            'profissional_id' => $profissional->id,
            'justificativa' => trim($justificativa),
            'texto_adendo' => trim($textoAdendo),
            'assinado_em' => now(),
        ]);
    }

    /**
     * Calcula o hash SHA-256 de 64 caracteres (paciente + profissional + timestamp + texto clínico).
     */
    public function calcularHashAssinatura(
        int $pacienteId,
        int $profissionalId,
        CarbonInterface $assinadoEm,
        string $queixaEstadoAtual,
        string $condutaTerapeutica,
        ?string $intercorrencias = null
    ): string {
        $payload = implode('|', [
            $pacienteId,
            $profissionalId,
            $assinadoEm->format('Y-m-d H:i:s'),
            $queixaEstadoAtual,
            $condutaTerapeutica,
            (string) $intercorrencias,
        ]);

        return hash('sha256', $payload);
    }
}
