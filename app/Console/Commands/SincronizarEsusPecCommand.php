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
}
