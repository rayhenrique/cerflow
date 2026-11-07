<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ConfiguracaoEsusPec;
use App\Models\HistoricoSincronizacaoEsus;
use App\Services\EsusPecIntegrationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

class SincronizarEsusPecCommand extends Command
{
    /**
     * A assinatura do comando de sincronização do e-SUS PEC via CLI.
     *
     * @var string
     */
    protected $signature = 'esus:sincronizar
        {--lote=2000 : Tamanho de cada lote (padrão: 2000)}
        {--max-registros= : Limite total de registros a processar nesta execução}
        {--dry-run : Executa validações e matching sem gravar dados ou alterar checkpoint}
        {--reiniciar : Reinicia a leitura desde o primeiro registro ignorando checkpoint existente}
        {--modo= : Modo de sincronização (AUTO, INCREMENTAL, COMPLETA)}
        {--apenas-vinculos : Complementa exclusivamente os vínculos de UBS e ACS dos pacientes já importados}
        {--diagnostico : Executa diagnóstico somente-leitura da estrutura e vínculos no PostgreSQL do PEC}
        {--limpar-bloqueio : Libera bloqueios e encerra execuções travadas anteriores}
        {--force : Força a execução ignorando bloqueios e confirmações}';

    /**
     * Descrição do comando exibida na listagem do Artisan.
     *
     * @var string
     */
    protected $description = 'Executa a sincronização de pacientes do e-SUS APS PEC diretamente pelo terminal da VPS';

    public function handle(EsusPecIntegrationService $service): int
    {
        $config = ConfiguracaoEsusPec::atual();

        $tamanhoLote = (int) ($this->option('lote') ?: 2000);
        $maxRegistrosOpt = $this->option('max-registros');
        $maxRegistros = $maxRegistrosOpt !== null && is_numeric($maxRegistrosOpt) ? (int) $maxRegistrosOpt : null;
        $dryRun = (bool) $this->option('dry-run');
        $reiniciar = (bool) $this->option('reiniciar');
        $modo = $this->option('modo') ? strtoupper((string) $this->option('modo')) : null;
        $force = (bool) $this->option('force');
        $apenasVinculos = (bool) $this->option('apenas-vinculos');
        $diagnosticoOpt = (bool) $this->option('diagnostico');

        // Modo Diagnóstico Somente-Leitura
        if ($diagnosticoOpt) {
            try {
                $dadosDiag = $service->executarDiagnosticoVps();
                $this->exibirRelatorioDiagnostico($dadosDiag);

                return self::SUCCESS;
            } catch (Throwable $e) {
                $this->newLine();
                $this->error('========================================================================');
                $this->error(' [ERRO AO EXECUTAR DIAGNÓSTICO DO e-SUS PEC]');
                $this->error('========================================================================');
                $this->error($e->getMessage());
                $this->newLine();

                return self::FAILURE;
            }
        }

        // Confirmação de segurança caso o usuário solicite reiniciar sem --force
        if ($reiniciar && ! $force) {
            $confirmado = $this->confirm(
                'Atenção: A opção --reiniciar irá resetar o checkpoint e reler todos os registros desde o início (sem apagar pacientes locais). Deseja continuar?',
                false
            );

            if (! $confirmado) {
                $this->warn('Operação cancelada pelo usuário.');

                return self::SUCCESS;
            }
        }

        // Validação da integração ativa (a menos que seja ambiente de teste com mock injetado)
        if (! $config->ativo && EsusPecIntegrationService::obterPdoTeste() === null) {
            $this->newLine();
            $this->error('========================================================================');
            $this->error(' [ERRO] A integração e-SUS APS PEC está inativa nas configurações.');
            $this->error('========================================================================');
            $this->line(' Ative a integração pelo painel web do CER Digital (Configurações > e-SUS)');
            $this->line(' ou atualize o registro na tabela configuracoes_esus_pec para ativo = true.');
            $this->newLine();

            return self::FAILURE;
        }

        $limparBloqueio = (bool) $this->option('limpar-bloqueio');

        if ($limparBloqueio) {
            Cache::lock(EsusPecIntegrationService::LOCK_NAME, 7200)->forceRelease();
            Cache::forget(EsusPecIntegrationService::LOCK_NAME);
            $afetados = HistoricoSincronizacaoEsus::where('status', 'EM_ANDAMENTO')->update([
                'status' => 'INTERROMPIDO',
                'finalizado_em' => now(),
                'mensagem_erro' => 'Bloqueio liberado manualmente pelo comando esus:sincronizar --limpar-bloqueio.',
            ]);

            $this->info("Bloqueios de sincronização liberados com sucesso ({$afetados} execução(ões) marcada(s) como INTERROMPIDO).");

            return self::SUCCESS;
        }

        // Se --force foi passado, limpa preventivamente bloqueios e execuções anteriores travadas
        if ($force) {
            Cache::lock(EsusPecIntegrationService::LOCK_NAME, 7200)->forceRelease();
            Cache::forget(EsusPecIntegrationService::LOCK_NAME);
            HistoricoSincronizacaoEsus::where('status', 'EM_ANDAMENTO')->update([
                'status' => 'INTERROMPIDO',
                'finalizado_em' => now(),
                'mensagem_erro' => 'Execução anterior cancelada por nova execução forçada (--force).',
            ]);
        }

        // Validação preventiva de concorrência com Jobs, Web e outros processos CLI
        $lockTeste = Cache::lock(EsusPecIntegrationService::LOCK_NAME, 7200);
        $conseguiuLock = $lockTeste->get();
        if (! $conseguiuLock && ! $force) {
            $ativa = $service->verificarExecucaoAtiva();
            $this->newLine();
            $this->error('========================================================================');
            $this->error(' [ERRO DE CONCORRÊNCIA] Sincronização em andamento detectada');
            $this->error('========================================================================');
            $this->line(sprintf(' Uma sincronização já está sendo executada por: <comment>%s</comment>', $ativa ? $ativa->tipo_execucao : 'Outro processo ativo (bloqueio atômico de cache)'));
            if ($ativa) {
                $this->line(sprintf(' Identificador do Histórico : <comment>#%d</comment>', $ativa->id));
                $this->line(sprintf(' Iniciada em                : <comment>%s</comment>', $ativa->iniciado_em?->format('d/m/Y H:i:s') ?? 'Desconhecido'));
            }
            $this->newLine();
            $this->comment(' DICA: Se a sincronização anterior foi interrompida (fechamento do navegador,');
            $this->comment(' queda de conexão ou travamento da fila), você pode forçar a execução com:');
            $this->line('   <info>php artisan esus:sincronizar --force</info>');
            $this->line(' ou apenas liberar o bloqueio com:');
            $this->line('   <info>php artisan esus:sincronizar --limpar-bloqueio</info>');
            $this->newLine();

            return self::FAILURE;
        }
        if ($conseguiuLock) {
            $lockTeste->release();
        }

        $ativa = $service->verificarExecucaoAtiva();

        // Recuperação automática de execuções órfãs/abandonadas (> 2 horas)
        if ($ativa && $ativa->iniciado_em && $ativa->iniciado_em->lessThan(now()->subHours(2))) {
            $this->warn(sprintf(
                'Identificada execução anterior estagnada (#%d iniciada em %s). Marcando como INTERROMPIDO e prosseguindo...',
                $ativa->id,
                $ativa->iniciado_em->format('d/m/Y H:i:s')
            ));

            $ativa->update([
                'status' => 'INTERROMPIDO',
                'finalizado_em' => now(),
                'mensagem_erro' => 'Execução anterior expirou por inatividade (> 2 horas) e foi liberada automaticamente.',
            ]);
            Cache::lock(EsusPecIntegrationService::LOCK_NAME, 7200)->forceRelease();
            Cache::forget(EsusPecIntegrationService::LOCK_NAME);
            $ativa = null;
        }

        if ($ativa && ! $force) {
            $this->newLine();
            $this->error('========================================================================');
            $this->error(' [ERRO DE CONCORRÊNCIA] Sincronização em andamento detectada');
            $this->error('========================================================================');
            $this->line(sprintf(' Uma sincronização já está sendo executada por: <comment>%s</comment>', $ativa->tipo_execucao));
            $this->line(sprintf(' Identificador do Histórico : <comment>#%d</comment>', $ativa->id));
            $this->line(sprintf(' Iniciada em                : <comment>%s</comment>', $ativa->iniciado_em?->format('d/m/Y H:i:s') ?? 'Desconhecido'));
            $this->newLine();
            $this->comment(' DICA: Se a sincronização anterior foi interrompida (fechamento do navegador,');
            $this->comment(' queda de conexão ou travamento da fila), você pode forçar a execução com:');
            $this->line('   <info>php artisan esus:sincronizar --force</info>');
            $this->line(' ou apenas liberar o bloqueio com:');
            $this->line('   <info>php artisan esus:sincronizar --limpar-bloqueio</info>');
            $this->newLine();

            return self::FAILURE;
        }

        // =====================================================================
        // MODO EXCLUSIVO: COMPLEMENTAÇÃO DE VÍNCULOS (--apenas-vinculos)
        // =====================================================================
        if ($apenasVinculos) {
            return $this->executarComplementacaoVinculos(
                service: $service,
                tamanhoLote: $tamanhoLote,
                maxRegistros: $maxRegistros,
                dryRun: $dryRun,
                reiniciar: $reiniciar
            );
        }

        // =====================================================================
        // MODO PADRÃO: SINCRONIZAÇÃO COMPLETA / INCREMENTAL
        // =====================================================================
        $checkpointInicial = $reiniciar ? 0 : (int) ($config->ultimo_checkpoint_id ?: 0);

        // Banner inicial do comando
        $this->newLine();
        $this->info('========================================================================');
        $this->info(' CER DIGITAL — SINCRONIZAÇÃO e-SUS APS PEC (CLI)');
        $this->info('========================================================================');
        $this->line(sprintf(' Tamanho do lote     : <comment>%s</comment>', number_format($tamanhoLote, 0, ',', '.')));
        $this->line(sprintf(' Limite máximo       : <comment>%s</comment>', $maxRegistros !== null ? number_format($maxRegistros, 0, ',', '.') : 'Sem limite (carga completa)'));
        $this->line(sprintf(' Modo solicitado     : <comment>%s</comment>', $modo ?: ($config->modo_sincronizacao ?: 'AUTO')));
        $this->line(sprintf(' Checkpoint inicial  : <comment>%s</comment>', $checkpointInicial > 0 ? "ID #{$checkpointInicial}" : 'Início (0)'));
        $this->line(sprintf(' Simulação (Dry-Run) : <comment>%s</comment>', $dryRun ? 'SIM (Nenhuma alteração será gravada)' : 'NÃO (Gravação real)'));
        $this->line(sprintf(' Início da execução  : <comment>%s</comment>', now()->format('d/m/Y H:i:s')));
        $this->info('========================================================================');
        $this->newLine();

        $onLoteConcluido = function (
            int $numeroLote,
            array $metricasLote,
            array $contadores,
            int $totalConsultado,
            int $totalEstimado
        ): void {
            $this->line(sprintf(
                '<fg=cyan;options=bold>[LOTE %d]</> Consultados: %s%s',
                $numeroLote,
                number_format($metricasLote['consultados'], 0, ',', '.'),
                $totalEstimado > 0 ? sprintf(' (acumulado: %s / ~%s)', number_format($totalConsultado, 0, ',', '.'), number_format($totalEstimado, 0, ',', '.')) : ''
            ));

            $this->line(sprintf(
                '  <fg=green>Novos: %s</> | <fg=blue>Atualizados: %s</> | <fg=gray>Sem alterações: %s</>',
                number_format($metricasLote['novos'], 0, ',', '.'),
                number_format($metricasLote['atualizados'], 0, ',', '.'),
                number_format($metricasLote['sem_alteracoes'], 0, ',', '.')
            ));

            $this->line(sprintf(
                '  <fg=yellow>Ignorados: %s</> | <fg=yellow>Duplicidades: %s</> | <fg=red>Falhas: %s</>',
                number_format($metricasLote['invalidos'] + $metricasLote['sem_identificador'], 0, ',', '.'),
                number_format($metricasLote['duplicidades'], 0, ',', '.'),
                number_format($metricasLote['falhas'], 0, ',', '.')
            ));

            $this->line(sprintf(
                '  Tempo: %s segundos | Checkpoint: %s',
                number_format($metricasLote['duracao_segundos'], 2, ',', '.'),
                number_format($metricasLote['ultimo_id_chave'], 0, ',', '.')
            ));
            $this->newLine();
        };

        try {
            $historico = $service->sincronizarPacientes(
                tipoExecucao: 'CLI',
                pdoInjetado: null,
                historicoId: null,
                onProgresso: null,
                forcarModo: $modo,
                tamanhoLote: $tamanhoLote,
                maxRegistros: $maxRegistros,
                dryRun: $dryRun,
                reiniciar: $reiniciar,
                onLoteConcluido: $onLoteConcluido
            );
        } catch (Throwable $e) {
            $this->newLine();
            $this->error('========================================================================');
            $this->error(' [ERRO FATAL NA EXECUÇÃO]');
            $this->error('========================================================================');
            $this->error($e->getMessage());
            $this->newLine();

            return self::FAILURE;
        }

        // Tabela final de consolidação dos resultados
        $this->newLine();
        $this->info('========================================================================');
        $this->info(' RESUMO CONSOLIDADO DA SINCRONIZAÇÃO');
        $this->info('========================================================================');

        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Status da Sincronização', $historico->status],
                ['Tipo de Execução', $historico->tipo_execucao],
                ['Modo Executado', (string) ($historico->detalhes['modo_executado'] ?? 'N/D')],
                ['Total Consultado no PEC', number_format($historico->total_consultado, 0, ',', '.')],
                ['Novos Pacientes Cadastrados', number_format($historico->novos_pacientes, 0, ',', '.')],
                ['Pacientes Atualizados', number_format($historico->pacientes_atualizados, 0, ',', '.')],
                ['Pacientes Sem Alterações', number_format($historico->pacientes_sem_alteracoes, 0, ',', '.')],
                ['Registros Ignorados (Inválidos/Sem Doc)', number_format($historico->registros_ignorados, 0, ',', '.')],
                ['Duplicidades / Conflitos', number_format($historico->duplicidades_identificadas, 0, ',', '.')],
                ['Falhas de Gravação', number_format($historico->falhas_gravacao, 0, ',', '.')],
                ['Duração da Consulta', number_format(($historico->duracao_consulta_ms / 1000), 2, ',', '.').' s'],
                ['Duração da Gravação', number_format(($historico->duracao_gravacao_ms / 1000), 2, ',', '.').' s'],
                ['Duração Total', number_format($historico->duracao_segundos, 0, ',', '.').' s'],
                ['Último Checkpoint Registrado', number_format((int) ($historico->detalhes['ultimo_id_processado'] ?? 0), 0, ',', '.')],
            ]
        );

        if ($historico->status === 'PARCIAL') {
            $this->newLine();
            $this->warn('========================================================================');
            $this->warn(' [AVISO: EXECUÇÃO PARCIAL]');
            $this->warn(' O limite de registros (--max-registros) foi atingido.');
            $this->warn(sprintf(' Checkpoint salvo: ID #%d. Para continuar do ponto onde parou,', (int) ($historico->detalhes['ultimo_id_processado'] ?? 0)));
            $this->warn(' basta rodar novamente sem --reiniciar:');
            $this->line('   <comment>php artisan esus:sincronizar --lote=2000</comment>');
            $this->warn('========================================================================');
        } elseif ($historico->status === 'DRY_RUN') {
            $this->newLine();
            $this->info('========================================================================');
            $this->info(' [MODO SIMULAÇÃO CONCLUÍDO]');
            $this->info(' Validações executadas com sucesso. Nenhum paciente ou checkpoint alterado.');
            $this->info('========================================================================');
        } elseif ($historico->status === 'ERRO' || $historico->status === 'INTERROMPIDO') {
            $this->newLine();
            $this->error('========================================================================');
            $this->error(' [AVISO DE FALHA OU INTERRUPÇÃO]');
            $this->error($historico->mensagem_erro ?: 'A sincronização foi finalizada com falhas.');
            $this->error('========================================================================');

            return self::FAILURE;
        } else {
            $this->newLine();
            $this->info('========================================================================');
            $this->info(' [SUCESSO] Sincronização e-SUS APS PEC finalizada com êxito!');
            $this->info('========================================================================');
        }

        return self::SUCCESS;
    }

    /**
     * Executa o fluxo especializado de complementação exclusiva de vínculos UBS e ACS.
     */
    protected function executarComplementacaoVinculos(
        EsusPecIntegrationService $service,
        int $tamanhoLote,
        ?int $maxRegistros,
        bool $dryRun,
        bool $reiniciar
    ): int {
        $config = ConfiguracaoEsusPec::atual();
        $checkpointInicial = $reiniciar ? 0 : (int) ($config->ultimo_checkpoint_id ?: 0);

        $this->newLine();
        $this->info('========================================================================');
        $this->info(' CER DIGITAL — COMPLEMENTAÇÃO EXCLUSIVA DE VÍNCULOS UBS/ACS (CLI)');
        $this->info('========================================================================');
        $this->line(sprintf(' Tamanho do lote     : <comment>%s</comment>', number_format($tamanhoLote, 0, ',', '.')));
        $this->line(sprintf(' Limite máximo       : <comment>%s</comment>', $maxRegistros !== null ? number_format($maxRegistros, 0, ',', '.') : 'Sem limite (base completa)'));
        $this->line(sprintf(' Checkpoint inicial  : <comment>%s</comment>', $checkpointInicial > 0 ? "ID #{$checkpointInicial}" : 'Início (0)'));
        $this->line(sprintf(' Simulação (Dry-Run) : <comment>%s</comment>', $dryRun ? 'SIM (Nenhuma alteração será gravada)' : 'NÃO (Gravação real)'));
        $this->line(sprintf(' Início da execução  : <comment>%s</comment>', now()->format('d/m/Y H:i:s')));
        $this->line(' Regra de Proteção   : Preenche somente campos vazios. Nunca sobrescreve dados manuais.');
        $this->info('========================================================================');
        $this->newLine();

        $onLoteConcluido = function (
            int $numeroLote,
            array $metricasLote,
            array $contadores,
            int $totalConsultado,
            int $totalEstimado
        ): void {
            $this->line(sprintf(
                '<fg=cyan;options=bold>[LOTE %d]</> Consultados PEC: %s%s | Localizados no CER: %s',
                $numeroLote,
                number_format($metricasLote['consultados'], 0, ',', '.'),
                $totalEstimado > 0 ? sprintf(' (acumulado: %s / ~%s)', number_format($totalConsultado, 0, ',', '.'), number_format($totalEstimado, 0, ',', '.')) : '',
                number_format($metricasLote['localizados_local'], 0, ',', '.')
            ));

            $this->line(sprintf(
                '  <fg=green>UBS complementadas: %s</> | <fg=blue>ACS complementados: %s</> | <fg=gray>Inalterados: %s</>',
                number_format($metricasLote['ubs_complementadas'], 0, ',', '.'),
                number_format($metricasLote['acs_complementados'], 0, ',', '.'),
                number_format($metricasLote['sem_alteracoes'], 0, ',', '.')
            ));

            $this->line(sprintf(
                '  Pacientes atualizados: %s | Não cadastrados no CER: %s',
                number_format($metricasLote['pacientes_atualizados'], 0, ',', '.'),
                number_format($metricasLote['nao_cadastrados_local'], 0, ',', '.')
            ));

            $this->line(sprintf(
                '  Tempo: %s segundos | Checkpoint: %s',
                number_format($metricasLote['duracao_segundos'], 2, ',', '.'),
                number_format($metricasLote['ultimo_id_chave'], 0, ',', '.')
            ));
            $this->newLine();
        };

        try {
            $historico = $service->complementarVinculosPacientes(
                pdoInjetado: null,
                tamanhoLote: $tamanhoLote,
                maxRegistros: $maxRegistros,
                dryRun: $dryRun,
                reiniciar: $reiniciar,
                onLoteConcluido: $onLoteConcluido
            );
        } catch (Throwable $e) {
            $this->newLine();
            $this->error('========================================================================');
            $this->error(' [ERRO FATAL NA COMPLEMENTAÇÃO]');
            $this->error('========================================================================');
            $this->error($e->getMessage());
            $this->newLine();

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('========================================================================');
        $this->info(' RESUMO CONSOLIDADO DA COMPLEMENTAÇÃO DE VÍNCULOS');
        $this->info('========================================================================');

        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Status da Complementação', $historico->status],
                ['Modo Executado', 'APENAS_VINCULOS (Complementação Exclusiva)'],
                ['Total Consultado no PEC', number_format($historico->total_consultado, 0, ',', '.')],
                ['Pacientes Locais Identificados', number_format((int) ($historico->detalhes['localizados_local'] ?? 0), 0, ',', '.')],
                ['Vínculos de UBS Complementados', number_format((int) ($historico->detalhes['ubs_complementadas'] ?? 0), 0, ',', '.')],
                ['Vínculos de ACS Complementados', number_format((int) ($historico->detalhes['acs_complementados'] ?? 0), 0, ',', '.')],
                ['Total de Pacientes Atualizados', number_format($historico->pacientes_atualizados, 0, ',', '.')],
                ['Pacientes Inalterados / Já Vinculados', number_format((int) ($historico->detalhes['pacientes_sem_alteracoes'] ?? 0), 0, ',', '.')],
                ['Registros PEC Não Cadastrados no CER', number_format((int) ($historico->detalhes['nao_cadastrados_local'] ?? 0), 0, ',', '.')],
                ['Novos Pacientes Cadastrados (Garantia)', '0 (Nenhum registro criado)'],
                ['Duração da Consulta', number_format(($historico->duracao_consulta_ms / 1000), 2, ',', '.').' s'],
                ['Duração da Gravação Local', number_format(($historico->duracao_gravacao_ms / 1000), 2, ',', '.').' s'],
                ['Duração Total', number_format($historico->duracao_segundos, 0, ',', '.').' s'],
                ['Último Checkpoint Registrado', number_format((int) ($historico->detalhes['ultimo_id_processado'] ?? 0), 0, ',', '.')],
            ]
        );

        if ($historico->status === 'DRY_RUN') {
            $this->newLine();
            $this->info('========================================================================');
            $this->info(' [MODO SIMULAÇÃO CONCLUÍDO]');
            $this->info(' Validações executadas com sucesso. Nenhum paciente local foi modificado.');
            $this->info('========================================================================');
        } else {
            $this->newLine();
            $this->info('========================================================================');
            $this->info(' [SUCESSO] Vínculos de UBS e ACS complementados com êxito!');
            $this->info('========================================================================');
        }

        return self::SUCCESS;
    }

    /**
     * Renderiza o relatório visual formatado do diagnóstico somente-leitura.
     *
     * @param  array<string, mixed>  $dados
     */
    public function exibirRelatorioDiagnostico(array $dados): void
    {
        $schema = $dados['diagnostico_schema'] ?? [];
        $tabelas = $dados['tabelas_status'] ?? [];
        $colunas = $dados['colunas_vinculos'] ?? [];
        $metricas = $dados['metricas_vinculos'] ?? [];
        $pacientesCer = $dados['pacientes_cer'] ?? [];

        $this->newLine();
        $this->info('========================================================================');
        $this->info(' CER DIGITAL — DIAGNÓSTICO ESTRUTURAL E-SUS APS PEC (SOMENTE LEITURA)');
        $this->info('========================================================================');
        $this->line(sprintf(' Latência da Conexão : <comment>%s ms</comment>', number_format($dados['latencia_ms'] ?? 0, 2, ',', '.')));
        $this->line(sprintf(' Tipo de Banco       : <comment>%s</comment>', $schema['tipo_banco'] ?? 'DESCONHECIDO'));
        $this->line(sprintf(' Versão do e-SUS PEC : <comment>%s</comment>', $schema['versao_pec'] ?? 'Não informada / sem tb_versao_sistema'));
        $this->line(sprintf(' Tabela Principal DW : <comment>%s</comment>', $schema['tabela_origem'] ?? 'Não localizada'));
        $this->line(sprintf(' Chave de Paginação  : <comment>%s</comment>', $schema['coluna_sequencial'] ?? 'Nenhuma chave sequencial detectada'));
        $this->info('========================================================================');
        $this->newLine();

        $this->info('--- 1. TABELAS DETECTADAS NO POSTGRESQL ---');
        $linhasTabelas = [];
        foreach ($tabelas as $nomeTab => $info) {
            $linhasTabelas[] = [
                $nomeTab,
                $info['existe'] ? '<fg=green>DISPONÍVEL</>' : '<fg=red>AUSENTE</>',
                $info['total_registros'] !== null ? number_format($info['total_registros'], 0, ',', '.') : ($info['existe'] ? 'Acessível' : '—'),
            ];
        }
        $this->table(['Tabela', 'Status', 'Total de Registros'], $linhasTabelas);
        $this->newLine();

        $this->info('--- 2. COLUNAS DE VÍNCULO EM tb_acomp_cidadaos_vinculados ---');
        $linhasColunas = [];
        foreach ($colunas as $col => $existe) {
            $linhasColunas[] = [
                $col,
                $existe ? '<fg=green>SIM (Presente)</>' : '<fg=yellow>NÃO (Ausente)</>',
            ];
        }
        $this->table(['Coluna de Vínculo', 'Presença no DW'], $linhasColunas);
        $this->newLine();

        $this->info('--- 3. ESTATÍSTICAS DE VÍNCULOS NO DW PEC (AGREGADO / LGPD SEGURO) ---');
        $this->table(
            ['Métrica de Vínculo', 'Quantidade'],
            [
                ['Total de Cidadãos no DW', number_format($metricas['total_cidadaos_dw'] ?? 0, 0, ',', '.')],
                ['Com CNES Vinculado (Equipe/Unidade)', number_format($metricas['com_cnes_vinculado'] ?? 0, 0, ',', '.')],
                ['Com INE de Equipe Vinculado', number_format($metricas['com_ine_vinculado'] ?? 0, 0, ',', '.')],
                ['Com Nome de Unidade Direto na Tabela', number_format($metricas['com_nome_unidade_direto'] ?? 0, 0, ',', '.')],
                ['Com Nome de ACS Direto na Tabela', number_format($metricas['com_acs_direto'] ?? 0, 0, ',', '.')],
                ['Total de Profissionais ACS no PEC (CBO 515105 / 322255)', number_format($metricas['total_acs_cadastrados_pec'] ?? 0, 0, ',', '.')],
            ]
        );
        $this->newLine();

        if (! empty($metricas['top_unidades'])) {
            $this->info('--- TOP 5 UNIDADES DE SAÚDE (CNES) ---');
            $linhasTopUbs = [];
            foreach ($metricas['top_unidades'] as $u) {
                $linhasTopUbs[] = [
                    (string) ($u['cnes'] ?? 'N/D'),
                    (string) ($u['nome_unidade'] ?? 'Não mapeado'),
                    number_format((int) ($u['total'] ?? 0), 0, ',', '.'),
                ];
            }
            $this->table(['CNES', 'Nome da Unidade', 'Cidadãos Vinculados'], $linhasTopUbs);
            $this->newLine();
        }

        if (! empty($metricas['top_equipes'])) {
            $this->info('--- TOP 5 EQUIPES DE SAÚDE DA FAMÍLIA (INE) ---');
            $linhasTopEq = [];
            foreach ($metricas['top_equipes'] as $e) {
                $linhasTopEq[] = [
                    (string) ($e['ine'] ?? 'N/D'),
                    (string) ($e['nome_equipe'] ?? 'N/D'),
                    number_format((int) ($e['total'] ?? 0), 0, ',', '.'),
                ];
            }
            $this->table(['INE', 'Nome da Equipe', 'Cidadãos Vinculados'], $linhasTopEq);
            $this->newLine();
        }

        $this->info('--- 4. SITUAÇÃO ATUAL NO CER DIGITAL (BANCO LOCAL) ---');
        $this->table(
            ['Métrica Local', 'Quantidade'],
            [
                ['Total de Pacientes Cadastrados', number_format($pacientesCer['total_locais'] ?? 0, 0, ',', '.')],
                ['Pacientes com UBS de Referência Preenchida', number_format($pacientesCer['com_ubs_preenchida'] ?? 0, 0, ',', '.')],
                ['Pacientes SEM UBS de Referência (Pendentes)', number_format($pacientesCer['sem_ubs_preenchida'] ?? 0, 0, ',', '.')],
                ['Pacientes com Nome de ACS Preenchido', number_format($pacientesCer['com_acs_preenchido'] ?? 0, 0, ',', '.')],
                ['Pacientes SEM Nome de ACS (Pendentes)', number_format($pacientesCer['sem_acs_preenchido'] ?? 0, 0, ',', '.')],
            ]
        );
        $this->newLine();

        $this->info('========================================================================');
        $this->info(' RECOMENDAÇÃO OPERACIONAL');
        $this->info('========================================================================');
        if (($pacientesCer['sem_ubs_preenchida'] ?? 0) > 0 || ($pacientesCer['sem_acs_preenchido'] ?? 0) > 0) {
            $this->line(' Para complementar os vínculos pendentes nos pacientes existentes sem');
            $this->line(' duplicar ou alterar dados clínicos/pessoais, execute:');
            $this->line('   <info>php8.5 artisan esus:sincronizar --apenas-vinculos --lote=2000 --force</info>');
        } else {
            $this->line(' Todos os pacientes locais já possuem os vínculos preenchidos.');
        }
        $this->info('========================================================================');
        $this->newLine();
    }
}
