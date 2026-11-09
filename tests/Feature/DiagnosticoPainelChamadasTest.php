<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Agendamento;
use App\Models\ChamadaPainel;
use App\Models\ConfiguracaoEstabelecimento;
use App\Models\DispositivoTv;
use App\Models\Paciente;
use App\Models\Profissional;
use App\Models\Sala;
use App\Models\User;
use App\Rules\CnsValido;
use App\Services\TvPairingService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DiagnosticoPainelChamadasTest extends TestCase
{
    use RefreshDatabase;

    protected User $coordenador;

    protected User $recepcionista;

    protected Sala $sala;

    protected Paciente $paciente;

    protected Profissional $profissional;

    protected Agendamento $agendamento;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->coordenador = User::factory()->create([
            'email' => 'coord.diag@cerflow.gov.br',
            'password' => Hash::make('password'),
            'ativo' => true,
        ]);
        $this->coordenador->assignRole('coordenacao');

        $this->recepcionista = User::factory()->create([
            'email' => 'recepcao.diag@cerflow.gov.br',
            'password' => Hash::make('password'),
            'ativo' => true,
        ]);
        $this->recepcionista->assignRole('recepcao');

        $this->sala = Sala::create([
            'nome' => 'Consultório Multidisciplinar 01',
            'numero' => '101',
            'tipo' => 'CONSULTORIO',
            'ativo' => true,
        ]);

        $this->paciente = Paciente::create([
            'nome' => 'Maria Silva Diagnostico',
            'cns' => CnsValido::gerarDefinitivo('12345678901'),
            'cpf' => '12345678901',
            'data_nascimento' => '1985-05-15',
            'sexo' => 'F',
            'nome_mae' => 'Mae Teste Diagnostico',
            'municipio_ibge' => '270430',
            'ativo' => true,
        ]);

        $this->profissional = Profissional::create([
            'user_id' => $this->recepcionista->id,
            'nome' => 'Dr. Terapeuta Teste',
            'cpf' => '98765432100',
            'cns' => CnsValido::gerarDefinitivo('98765432100'),
            'registro_conselho' => 'CREFITO 1111/AL',
            'cbo_padrao' => '223605',
            'especialidade' => 'Fisioterapia Geral',
            'pin_assinatura_hash' => Hash::make('1234'),
            'ativo' => true,
        ]);

        $this->agendamento = Agendamento::create([
            'paciente_id' => $this->paciente->id,
            'sala_id' => $this->sala->id,
            'data_atendimento' => now()->toDateString(),
            'hora_inicio' => '08:00',
            'hora_fim' => '08:30',
            'dia_semana' => 'SEGUNDA',
            'status' => 'PRESENTE',
            'tipo_atendimento' => 'INDIVIDUAL',
        ]);
        $this->agendamento->profissionais()->attach($this->profissional->id, ['papel' => 'PRINCIPAL']);

        ConfiguracaoEstabelecimento::updateOrCreate(
            ['id' => 1],
            [
                'cnes' => '2000001',
                'codigo_ibge_padrao' => '270430',
                'painel_voz_ativa' => true,
                'painel_volume' => 80,
                'painel_tempo_destaque_segundos' => 15,
                'painel_quantidade_historico' => 5,
                'painel_modo_exibicao_nome' => 'COMPLETO',
                'painel_token_acesso' => 'TOKEN-TESTE-DIAGNOSTICO',
                'painel_permitir_token_legado' => true,
            ]
        );
    }

    /**
     * CENÁRIO 1: Acesso sem autenticação e sem cookie.
     * Retorna HTTP 200, exibe tela de ativação por código e NUNCA vaza dados de pacientes.
     */
    public function test_cenario_1_acesso_sem_autenticacao_e_sem_cookie(): void
    {
        ChamadaPainel::create([
            'agendamento_id' => $this->agendamento->id,
            'paciente_id' => $this->paciente->id,
            'sala_id' => $this->sala->id,
            'profissional_id' => $this->profissional->id,
            'chamado_por_id' => $this->recepcionista->id,
            'paciente_nome' => $this->paciente->nome,
            'sala_nome' => $this->sala->nome,
            'profissional_nome' => $this->profissional->nome,
            'tipo' => 'CHAMADA',
            'status' => 'CHAMANDO',
            'chamado_em' => now(),
        ]);

        $response = $this->get('/painel-chamadas');
        $response->assertOk();
        $response->assertSee('Conectar este Televisor');
        $response->assertDontSee($this->paciente->nome);
        $response->assertDontSee('Dirigir-se ao Local');
    }

    /**
     * CENÁRIO 2: Primeiro acesso de uma TV não pareada gera solicitação pendente.
     */
    public function test_cenario_2_primeiro_acesso_tv_nao_pareada_gera_solicitacao(): void
    {
        $service = app(TvPairingService::class);
        $dados = $service->iniciarSolicitacaoPareamento('192.168.1.50', 'LG WebOS TV');

        $this->assertSame('PENDENTE', $dados['solicitacao']->status);
        $this->assertNotEmpty($dados['codigo']);
        $this->assertNotEmpty($dados['segredo_temporario']);
    }

    /**
     * CENÁRIO 3: Formato do código de ativação (legível à distância, 6 caracteres em formato XXX-XXX).
     */
    public function test_cenario_3_formato_codigo_ativacao_legivel(): void
    {
        $service = app(TvPairingService::class);
        $dados = $service->iniciarSolicitacaoPareamento();

        $this->assertMatchesRegularExpression('/^[2-9A-HJ-NP-Z]{3}-[2-9A-HJ-NP-Z]{3}$/', $dados['codigo']);
    }

    /**
     * CENÁRIO 4: TV aguardando autorização não recebe credencial enquanto pendente.
     */
    public function test_cenario_4_tv_aguardando_autorizacao(): void
    {
        $service = app(TvPairingService::class);
        $dados = $service->iniciarSolicitacaoPareamento();

        $status = $service->verificarStatusPareamento($dados['solicitacao']->solicitacao_uid, $dados['segredo_temporario']);
        $this->assertSame('PENDENTE', $status['status']);
        $this->assertNull($status['token']);
    }

    /**
     * CENÁRIO 5: Aprovação pela Coordenação.
     */
    public function test_cenario_5_aprovacao_pela_coordenacao(): void
    {
        $service = app(TvPairingService::class);
        $dados = $service->iniciarSolicitacaoPareamento();

        $dispositivo = $service->autorizarCodigo($dados['codigo'], 'Smart TV Recepção Principal', $this->coordenador);

        $this->assertInstanceOf(DispositivoTv::class, $dispositivo);
        $this->assertTrue($dispositivo->estaAtivo());
        $this->assertSame('Smart TV Recepção Principal', $dispositivo->nome);
    }

    /**
     * CENÁRIO 6: Recebimento do cookie persistente após aprovação.
     */
    public function test_cenario_6_recebimento_credencial_persistente(): void
    {
        $service = app(TvPairingService::class);
        $dados = $service->iniciarSolicitacaoPareamento();
        $service->autorizarCodigo($dados['codigo'], 'TV Teste Cookie', $this->coordenador);

        $status = $service->verificarStatusPareamento($dados['solicitacao']->solicitacao_uid, $dados['segredo_temporario']);

        $this->assertSame('APROVADO', $status['status']);
        $this->assertNotNull($status['token']);
        $this->assertSame(64, strlen($status['token']));
    }

    /**
     * CENÁRIO 7: Acesso por TV já autorizada com cookie persistente exibe o painel em tempo real.
     */
    public function test_cenario_7_acesso_por_tv_ja_autorizada(): void
    {
        ChamadaPainel::create([
            'agendamento_id' => $this->agendamento->id,
            'paciente_id' => $this->paciente->id,
            'sala_id' => $this->sala->id,
            'profissional_id' => $this->profissional->id,
            'chamado_por_id' => $this->recepcionista->id,
            'paciente_nome' => $this->paciente->nome,
            'sala_nome' => $this->sala->nome,
            'profissional_nome' => $this->profissional->nome,
            'tipo' => 'CHAMADA',
            'status' => 'CHAMANDO',
            'chamado_em' => now(),
        ]);

        $service = app(TvPairingService::class);
        $dados = $service->iniciarSolicitacaoPareamento();
        $service->autorizarCodigo($dados['codigo'], 'TV Sala de Espera A', $this->coordenador);
        $status = $service->verificarStatusPareamento($dados['solicitacao']->solicitacao_uid, $dados['segredo_temporario']);

        $response = $this->withCookie(TvPairingService::COOKIE_NAME, $status['token'])
            ->get('/painel-chamadas');

        $response->assertOk();
        $response->assertSee('TV Sala de Espera A');
        $response->assertSee($this->paciente->nome);
        $response->assertSee($this->sala->nome);
    }

    /**
     * CENÁRIO 8: Acesso com credencial inválida ou revogada exibe tela de revogação e bloqueia dados clínicos.
     */
    public function test_cenario_8_acesso_com_credencial_revogada(): void
    {
        ChamadaPainel::create([
            'agendamento_id' => $this->agendamento->id,
            'paciente_id' => $this->paciente->id,
            'sala_id' => $this->sala->id,
            'profissional_id' => $this->profissional->id,
            'chamado_por_id' => $this->recepcionista->id,
            'paciente_nome' => $this->paciente->nome,
            'sala_nome' => $this->sala->nome,
            'profissional_nome' => $this->profissional->nome,
            'tipo' => 'CHAMADA',
            'status' => 'CHAMANDO',
            'chamado_em' => now(),
        ]);

        $service = app(TvPairingService::class);
        $dados = $service->iniciarSolicitacaoPareamento();
        $disp = $service->autorizarCodigo($dados['codigo'], 'TV A Ser Revogada', $this->coordenador);
        $status = $service->verificarStatusPareamento($dados['solicitacao']->solicitacao_uid, $dados['segredo_temporario']);

        // Revoga o dispositivo
        $service->revogarDispositivo($disp, $this->coordenador);

        $response = $this->withCookie(TvPairingService::COOKIE_NAME, $status['token'])
            ->get('/painel-chamadas');

        $response->assertOk();
        $response->assertSee('Dispositivo Não Autorizado');
        $response->assertDontSee($this->paciente->nome);
    }

    /**
     * CENÁRIO 9: Acesso legado por token na URL (se habilitado).
     */
    public function test_cenario_9_acesso_legado_por_token_quando_habilitado(): void
    {
        ChamadaPainel::create([
            'agendamento_id' => $this->agendamento->id,
            'paciente_id' => $this->paciente->id,
            'sala_id' => $this->sala->id,
            'profissional_id' => $this->profissional->id,
            'chamado_por_id' => $this->recepcionista->id,
            'paciente_nome' => $this->paciente->nome,
            'sala_nome' => $this->sala->nome,
            'profissional_nome' => $this->profissional->nome,
            'tipo' => 'CHAMADA',
            'status' => 'CHAMANDO',
            'chamado_em' => now(),
        ]);

        $response = $this->get('/painel-chamadas?token=TOKEN-TESTE-DIAGNOSTICO');
        $response->assertOk();
        $response->assertSee($this->paciente->nome);

        // Quando desabilitado, token legado é rejeitado
        ConfiguracaoEstabelecimento::atual()->update(['painel_permitir_token_legado' => false]);
        $responseDesab = $this->get('/painel-chamadas?token=TOKEN-TESTE-DIAGNOSTICO');
        $responseDesab->assertOk();
        $responseDesab->assertDontSee($this->paciente->nome);
        $responseDesab->assertSee('Conectar este Televisor');
    }

    /**
     * CENÁRIO 10: Painel autorizado sem nenhuma chamada cadastrada hoje exibe tela de espera limpa.
     */
    public function test_cenario_10_painel_sem_nenhuma_chamada_cadastrada(): void
    {
        ChamadaPainel::query()->delete();

        $service = app(TvPairingService::class);
        $dados = $service->iniciarSolicitacaoPareamento();
        $service->autorizarCodigo($dados['codigo'], 'TV Sala de Espera Vazia', $this->coordenador);
        $status = $service->verificarStatusPareamento($dados['solicitacao']->solicitacao_uid, $dados['segredo_temporario']);

        $response = $this->withCookie(TvPairingService::COOKIE_NAME, $status['token'])
            ->get('/painel-chamadas');

        $response->assertOk();
        $response->assertSee('Aguardando Próxima Chamada');
        $response->assertSee('TV Sala de Espera Vazia');
    }

    /**
     * TESTE DE REGRESSÃO: Caching com cache driver persistente (database) não produz __PHP_Incomplete_Class.
     */
    public function test_regressao_cache_driver_database_nao_gera_incomplete_class(): void
    {
        config(['cache.default' => 'database']);

        // Primeira requisição: popula o cache
        $response1 = $this->get('/painel-chamadas');
        $response1->assertOk();

        // Segunda requisição: consome os dados do cache persistente
        $response2 = $this->get('/painel-chamadas');
        $response2->assertOk();
        $response2->assertSee('Conectar este Televisor');
    }
}
