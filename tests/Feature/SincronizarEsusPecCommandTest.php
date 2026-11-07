<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ConfiguracaoEsusPec;
use App\Models\HistoricoSincronizacaoEsus;
use App\Models\Paciente;
use App\Rules\CnsValido;
use App\Services\EsusPecIntegrationService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PDO;
use Tests\TestCase;

class SincronizarEsusPecCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $config = ConfiguracaoEsusPec::atual();
        $config->update(['ativo' => true]);
    }

    protected function tearDown(): void
    {
        EsusPecIntegrationService::definirPdoTeste(null);
        Cache::forget(EsusPecIntegrationService::LOCK_NAME);
        parent::tearDown();
    }

    /**
     * Cria um banco SQLite em memória com a tabela oficial tb_acomp_cidadaos_vinculados.
     */
    protected function criarPdoSimulado(int $quantidade = 7): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('
            CREATE TABLE tb_acomp_cidadaos_vinculados (
                co_seq_acomp_cidadaos_vinc INTEGER PRIMARY KEY,
                co_fat_cidadao_pec INTEGER,
                no_cidadao VARCHAR(255),
                nu_cpf_cidadao VARCHAR(11),
                nu_cns_cidadao VARCHAR(15),
                dt_nascimento_cidadao DATE,
                no_sexo_cidadao VARCHAR(20),
                no_mae_cidadao VARCHAR(255),
                nu_telefone_celular VARCHAR(20),
                ds_logradouro_domicilio VARCHAR(255),
                no_bairro_domicilio VARCHAR(255),
                co_ibge_domicilio VARCHAR(6),
                no_unidade_saude VARCHAR(255),
                no_profissional_acs VARCHAR(255)
            );
        ');

        $stmt = $pdo->prepare('
            INSERT INTO tb_acomp_cidadaos_vinculados VALUES
            (?, ?, ?, ?, ?, ?, "M", "Mae Paciente", "82999990001", "Rua Teste", "Centro", "270430", "UBS Central", "ACS Maria");
        ');

        for ($i = 1; $i <= $quantidade; $i++) {
            $cpf = sprintf('111222333%02d', $i);
            $cns = CnsValido::gerarDefinitivo(sprintf('100000000%02d', $i));
            $dt = sprintf('199%d-0%d-15', $i % 10, ($i % 9) + 1);
            $stmt->execute([
                $i * 10, // Sequência monotônica: 10, 20, 30...
                1000 + $i, // Identificador único do cidadão
                "Paciente CLI Teste {$i}",
                $cpf,
                $cns,
                $dt,
            ]);
        }

        return $pdo;
    }

    public function test_execucao_multiplos_lotes_e_saida_no_terminal(): void
    {
        $pdo = $this->criarPdoSimulado(7);
        EsusPecIntegrationService::definirPdoTeste($pdo);

        $this->artisan('esus:sincronizar', ['--lote' => '3', '--force' => true])
            ->expectsOutputToContain('CER DIGITAL — SINCRONIZAÇÃO e-SUS APS PEC (CLI)')
            ->expectsOutputToContain('[LOTE 1] Consultados: 3')
            ->expectsOutputToContain('[LOTE 2] Consultados: 3')
            ->expectsOutputToContain('[LOTE 3] Consultados: 1')
            ->expectsOutputToContain('RESUMO CONSOLIDADO DA SINCRONIZAÇÃO')
            ->expectsOutputToContain('[SUCESSO] Sincronização e-SUS APS PEC finalizada com êxito!')
            ->assertSuccessful();

        $this->assertSame(7, Paciente::count());

        $config = ConfiguracaoEsusPec::atual();
        $this->assertSame(70, $config->ultimo_checkpoint_id);
        $this->assertSame('SUCESSO', $config->ultima_sincronizacao_status);

        $historico = HistoricoSincronizacaoEsus::latest('id')->first();
        $this->assertNotNull($historico);
        $this->assertSame('CLI', $historico->tipo_execucao);
        $this->assertSame('SUCESSO', $historico->status);
        $this->assertSame(7, $historico->total_consultado);
        $this->assertSame(7, $historico->novos_pacientes);
    }

    public function test_execucao_com_ultimo_lote_incompleto(): void
    {
        $pdo = $this->criarPdoSimulado(5);
        EsusPecIntegrationService::definirPdoTeste($pdo);

        $this->artisan('esus:sincronizar', ['--lote' => '2', '--force' => true])
            ->expectsOutputToContain('[LOTE 1] Consultados: 2')
            ->expectsOutputToContain('[LOTE 2] Consultados: 2')
            ->expectsOutputToContain('[LOTE 3] Consultados: 1')
            ->assertSuccessful();

        $this->assertSame(5, Paciente::count());
        $this->assertSame(50, ConfiguracaoEsusPec::atual()->ultimo_checkpoint_id);
    }

    public function test_respeito_ao_limite_de_max_registros_e_status_parcial(): void
    {
        $pdo = $this->criarPdoSimulado(6);
        EsusPecIntegrationService::definirPdoTeste($pdo);

        $this->artisan('esus:sincronizar', [
            '--lote' => '2',
            '--max-registros' => '4',
            '--force' => true,
        ])
            ->expectsOutputToContain('[LOTE 1] Consultados: 2')
            ->expectsOutputToContain('[LOTE 2] Consultados: 2')
            ->expectsOutputToContain('[AVISO: EXECUÇÃO PARCIAL]')
            ->expectsOutputToContain('Checkpoint salvo: ID #40')
            ->assertSuccessful();

        $this->assertSame(4, Paciente::count());

        $config = ConfiguracaoEsusPec::atual();
        $this->assertSame(40, $config->ultimo_checkpoint_id);
        $this->assertSame('PARCIAL', $config->ultima_sincronizacao_status);

        $historico = HistoricoSincronizacaoEsus::latest('id')->first();
        $this->assertSame('PARCIAL', $historico->status);
        $this->assertSame(4, $historico->total_consultado);
    }

    public function test_retomada_automatica_a_partir_do_checkpoint(): void
    {
        $pdo = $this->criarPdoSimulado(6);
        EsusPecIntegrationService::definirPdoTeste($pdo);

        // 1ª execução limitada: processa 4 registros (IDs 10, 20, 30, 40)
        $this->artisan('esus:sincronizar', [
            '--lote' => '2',
            '--max-registros' => '4',
            '--force' => true,
        ])->assertSuccessful();

        $this->assertSame(4, Paciente::count());
        $this->assertSame(40, ConfiguracaoEsusPec::atual()->ultimo_checkpoint_id);

        // 2ª execução sem reiniciar: deve retomar a partir do checkpoint 40 e importar os 2 restantes (IDs 50, 60)
        $this->artisan('esus:sincronizar', [
            '--lote' => '2',
            '--force' => true,
        ])
            ->expectsOutputToContain('Checkpoint inicial  : ID #40')
            ->expectsOutputToContain('[LOTE 1] Consultados: 2')
            ->expectsOutputToContain('[SUCESSO] Sincronização e-SUS APS PEC finalizada com êxito!')
            ->assertSuccessful();

        $this->assertSame(6, Paciente::count());
        $this->assertSame(60, ConfiguracaoEsusPec::atual()->ultimo_checkpoint_id);
        $this->assertSame('SUCESSO', ConfiguracaoEsusPec::atual()->ultima_sincronizacao_status);
    }

    public function test_reiniciar_reseta_checkpoint_e_rele_desde_o_inicio_com_idempotencia(): void
    {
        $pdo = $this->criarPdoSimulado(4);
        EsusPecIntegrationService::definirPdoTeste($pdo);

        // 1ª execução: importa 2 registros
        $this->artisan('esus:sincronizar', [
            '--lote' => '2',
            '--max-registros' => '2',
            '--force' => true,
        ])->assertSuccessful();

        $this->assertSame(2, Paciente::count());
        $this->assertSame(20, ConfiguracaoEsusPec::atual()->ultimo_checkpoint_id);

        // 2ª execução com --reiniciar e --force: deve ler desde o início (0)
        // Os 2 primeiros pacientes já existem e serão identificados como sem alterações;
        // Os 2 seguintes serão cadastrados como novos, totalizando 4 pacientes sem duplicidade
        $this->artisan('esus:sincronizar', [
            '--reiniciar' => true,
            '--force' => true,
            '--lote' => '5',
        ])
            ->expectsOutputToContain('Checkpoint inicial  : Início (0)')
            ->expectsOutputToContain('[SUCESSO] Sincronização e-SUS APS PEC finalizada com êxito!')
            ->assertSuccessful();

        $this->assertSame(4, Paciente::count());
        $this->assertSame(40, ConfiguracaoEsusPec::atual()->ultimo_checkpoint_id);

        $historico = HistoricoSincronizacaoEsus::latest('id')->first();
        $this->assertSame(4, $historico->total_consultado);
        $this->assertSame(2, $historico->novos_pacientes);
        $this->assertSame(2, $historico->pacientes_sem_alteracoes);
        $this->assertSame(0, $historico->duplicidades_identificadas);
    }

    public function test_protecao_contra_concorrencia_com_lock_ativo(): void
    {
        $lock = Cache::lock(EsusPecIntegrationService::LOCK_NAME, 7200);
        $this->assertTrue($lock->get());

        try {
            $this->artisan('esus:sincronizar', ['--force' => true])
                ->expectsOutputToContain('[ERRO DE CONCORRÊNCIA]')
                ->assertFailed();
        } finally {
            $lock->release();
        }
    }

    public function test_protecao_contra_concorrencia_com_sincronizacao_em_andamento_no_banco(): void
    {
        // Cria registro ativo simulando Queue Worker em andamento
        HistoricoSincronizacaoEsus::create([
            'iniciado_em' => now(),
            'tipo_execucao' => 'AGENDADA',
            'status' => 'EM_ANDAMENTO',
            'total_consultado' => 100,
        ]);

        $this->artisan('esus:sincronizar', ['--force' => true])
            ->expectsOutputToContain('[ERRO DE CONCORRÊNCIA] Sincronização em andamento detectada')
            ->expectsOutputToContain('Uma sincronização já está sendo executada por: AGENDADA')
            ->assertFailed();
    }

    public function test_execucao_com_dry_run_sem_persistencia_e_sem_alterar_checkpoint(): void
    {
        $pdo = $this->criarPdoSimulado(5);
        EsusPecIntegrationService::definirPdoTeste($pdo);

        $config = ConfiguracaoEsusPec::atual();
        $config->update(['ultimo_checkpoint_id' => null]);

        $this->artisan('esus:sincronizar', [
            '--dry-run' => true,
            '--lote' => '2',
            '--force' => true,
        ])
            ->expectsOutputToContain('Simulação (Dry-Run) : SIM (Nenhuma alteração será gravada)')
            ->expectsOutputToContain('[MODO SIMULAÇÃO CONCLUÍDO]')
            ->assertSuccessful();

        // Nenhum paciente deve ser criado no banco
        $this->assertSame(0, Paciente::count());

        // Checkpoint deve permanecer nulo
        $config->refresh();
        $this->assertNull($config->ultimo_checkpoint_id);

        $historico = HistoricoSincronizacaoEsus::latest('id')->first();
        $this->assertSame('DRY_RUN', $historico->status);
        $this->assertSame(5, $historico->total_consultado);
        $this->assertSame(5, $historico->novos_pacientes);
    }

    public function test_falha_de_persistencia_com_rollback_do_lote_e_preservacao_do_checkpoint(): void
    {
        // 1ª execução bem-sucedida de 2 registros (checkpoint 20)
        $pdo = $this->criarPdoSimulado(4);
        EsusPecIntegrationService::definirPdoTeste($pdo);

        $this->artisan('esus:sincronizar', [
            '--lote' => '2',
            '--max-registros' => '2',
            '--force' => true,
        ])->assertSuccessful();

        $this->assertSame(2, Paciente::count());
        $this->assertSame(20, ConfiguracaoEsusPec::atual()->ultimo_checkpoint_id);

        // Simula falha catastrófica de banco interceptando evento ou fechando tabela
        // Forçamos o erro sobrescrevendo temporariamente com tabela corrompida ou trigger que falha
        $pdoFalho = new PDO('sqlite::memory:');
        $pdoFalho->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdoFalho->exec('
            CREATE TABLE tb_acomp_cidadaos_vinculados (
                co_seq_acomp_cidadaos_vinc INTEGER PRIMARY KEY,
                co_fat_cidadao_pec INTEGER,
                no_cidadao VARCHAR(255),
                nu_cpf_cidadao VARCHAR(11),
                nu_cns_cidadao VARCHAR(15),
                dt_nascimento_cidadao DATE
            );
            INSERT INTO tb_acomp_cidadaos_vinculados VALUES (30, 1003, "Paciente Crash", "99988877766", NULL, "2000-01-01");
        ');
        EsusPecIntegrationService::definirPdoTeste($pdoFalho);

        // Simulamos falha fatal na persistência durante a gravação do lote
        Paciente::saving(function () {
            throw new \RuntimeException('Erro simulado de integridade local durante a persistência.');
        });

        // 2ª execução falhará na transação
        $this->artisan('esus:sincronizar', [
            '--lote' => '2',
            '--force' => true,
        ])
            ->expectsOutputToContain('[AVISO DE FALHA OU INTERRUPÇÃO]')
            ->assertFailed();

        // Limpa o interceptador de eventos
        Paciente::flushEventListeners();

        // O paciente da segunda execução não foi persistido (rollback confirmado)
        $this->assertSame(2, Paciente::count());

        // O checkpoint deve ter permanecido inalterado em 20 (não pulou registros após falhas)
        $this->assertSame(20, ConfiguracaoEsusPec::atual()->ultimo_checkpoint_id);
    }
}
