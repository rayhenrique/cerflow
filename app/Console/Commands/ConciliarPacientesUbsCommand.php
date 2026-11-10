<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Paciente;
use App\Models\UnidadeBasicaSaude;
use App\Services\EsusPecIntegrationService;
use Illuminate\Console\Command;

class ConciliarPacientesUbsCommand extends Command
{
    /**
     * A assinatura do comando de conciliação de vínculos de UBS via CLI.
     *
     * @var string
     */
    protected $signature = 'esus:conciliar-pacientes
        {--lote=500 : Tamanho de cada lote de processamento (padrão: 500)}
        {--dry-run : Executa a simulação sem persistir alterações no banco}';

    /**
     * Descrição do comando exibida na listagem do Artisan.
     *
     * @var string
     */
    protected $description = 'Identifica e concilia pacientes com ubs_referencia preenchida e ubs_id vazio, associando a UBS correspondente sem sobrescrever dados manuais';

    public function handle(EsusPecIntegrationService $service): int
    {
        $tamanhoLote = max(10, min(5000, (int) ($this->option('lote') ?: 500)));
        $dryRun = (bool) $this->option('dry-run');

        $this->newLine();
        $this->info('========================================================================');
        $this->info(' CER DIGITAL — CONCILIAÇÃO DE VÍNCULOS DE UBS DE PACIENTES');
        $this->info('========================================================================');
        $this->line(sprintf(' Tamanho do lote     : <comment>%d</comment>', $tamanhoLote));
        $this->line(sprintf(' Simulação (Dry-Run) : <comment>%s</comment>', $dryRun ? 'SIM (Nenhuma alteração será gravada)' : 'NÃO (Gravação real)'));
        $this->info('========================================================================');
        $this->newLine();

        $inicio = microtime(true);

        $totalGeralPacientes = Paciente::count();
        $pacientesComUbsId = Paciente::whereNotNull('ubs_id')->count();
        $pacientesSemUbsTotal = Paciente::whereNull('ubs_id')
            ->where(function ($q) {
                $q->whereNull('ubs_referencia')->orWhere('ubs_referencia', '');
            })
            ->count();
        $pacientesSemAcsTotal = Paciente::where(function ($q) {
            $q->whereNull('nome_acs')->orWhere('nome_acs', '');
        })->count();

        $queryCandidatos = Paciente::whereNull('ubs_id')
            ->whereNotNull('ubs_referencia')
            ->where('ubs_referencia', '!=', '');

        $totalCandidatos = $queryCandidatos->count();

        $conciliados = 0;
        $conflitosAmbiguidade = 0;
        $naoLocalizados = 0;
        $nomesUbsConciliadas = [];

        if ($totalCandidatos > 0) {
            $this->comment(sprintf('Processando %d paciente(s) candidato(s) em lotes de %d...', $totalCandidatos, $tamanhoLote));

            $queryCandidatos->chunkById($tamanhoLote, function ($lotePacientes) use (
                $service,
                $dryRun,
                &$conciliados,
                &$conflitosAmbiguidade,
                &$naoLocalizados,
                &$nomesUbsConciliadas
            ) {
                foreach ($lotePacientes as $paciente) {
                    $nomeUbs = trim((string) $paciente->ubs_referencia);
                    if ($service->nomeUbsInconclusivo($nomeUbs)) {
                        $naoLocalizados++;

                        continue;
                    }

                    // 1. Tenta correspondência inequívoca pelo nome e município
                    $ubs = $service->localizarUbsExistente($nomeUbs, null, $paciente->municipio_ibge);

                    if ($ubs) {
                        if (! $dryRun) {
                            $paciente->update(['ubs_id' => $ubs->id]);
                        }
                        $conciliados++;
                        $nomesUbsConciliadas[$ubs->nome] = ($nomesUbsConciliadas[$ubs->nome] ?? 0) + 1;

                        continue;
                    }

                    // 2. Se nomeUbs contém padrão "UBS CNES XXXXXXX", tenta extrair o CNES
                    if (str_starts_with($nomeUbs, 'UBS CNES ')) {
                        $cnesExtraido = substr(preg_replace('/\D/', '', $nomeUbs) ?? '', 0, 7);
                        if (strlen($cnesExtraido) >= 6) {
                            $ubsCnes = UnidadeBasicaSaude::where('cnes', $cnesExtraido)->first();
                            if ($ubsCnes) {
                                if (! $dryRun) {
                                    $paciente->update(['ubs_id' => $ubsCnes->id]);
                                }
                                $conciliados++;
                                $nomesUbsConciliadas[$ubsCnes->nome] = ($nomesUbsConciliadas[$ubsCnes->nome] ?? 0) + 1;

                                continue;
                            }
                        }
                    }

                    // 3. Verifica se o insucesso foi por ambiguidade (homônimas em municípios diferentes)
                    $contagemHomonimas = UnidadeBasicaSaude::where('nome', $nomeUbs)->count();
                    if ($contagemHomonimas > 1) {
                        $conflitosAmbiguidade++;
                    } else {
                        $naoLocalizados++;
                    }
                }
            });
        }

        $duracao = round(microtime(true) - $inicio, 2);

        $this->table(
            ['Métrica de Conciliação', 'Quantidade'],
            [
                ['Total de Pacientes no CER Digital', number_format($totalGeralPacientes, 0, ',', '.')],
                ['Pacientes Já Vinculados com ID (ubs_id)', number_format($pacientesComUbsId, 0, ',', '.')],
                ['Pacientes Candidatos (Com Nome Textual e Sem ID)', number_format($totalCandidatos, 0, ',', '.')],
                ['Vínculos Conciliados com Sucesso (ubs_id associado)', number_format($conciliados, 0, ',', '.')],
                ['Vínculos Pendentes por Ambiguidade / Conflito', number_format($conflitosAmbiguidade, 0, ',', '.')],
                ['Vínculos Pendentes (Não Cadastrados no Catálogo)', number_format($naoLocalizados, 0, ',', '.')],
                ['Pacientes Sem Nenhuma UBS (Vazia)', number_format($pacientesSemUbsTotal, 0, ',', '.')],
                ['Pacientes Sem ACS Identificado', number_format($pacientesSemAcsTotal, 0, ',', '.')],
                ['Duração do Processamento', "{$duracao} s"],
            ]
        );

        if (! empty($nomesUbsConciliadas)) {
            $this->newLine();
            $this->info('--- PRINCIPAIS UNIDADES CONCILIADAS ---');
            $linhasUbs = [];
            arsort($nomesUbsConciliadas);
            foreach (array_slice($nomesUbsConciliadas, 0, 10, true) as $nome => $qtd) {
                $linhasUbs[] = [$nome, number_format($qtd, 0, ',', '.')];
            }
            $this->table(['Unidade Básica de Saúde', 'Pacientes Associados'], $linhasUbs);
        }

        $this->newLine();
        if ($dryRun) {
            $this->info('========================================================================');
            $this->info(' [MODO SIMULAÇÃO CONCLUÍDO]');
            $this->info(' Nenhuma alteração foi gravada no banco de dados.');
            $this->info('========================================================================');
        } else {
            $this->info('========================================================================');
            $this->info(' [SUCESSO] Conciliação de vínculos finalizada!');
            $this->info('========================================================================');
        }

        return self::SUCCESS;
    }
}
