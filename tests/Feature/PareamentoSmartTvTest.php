<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\PainelChamadas;
use App\Models\Agendamento;
use App\Models\ChamadaPainel;
use App\Models\ConfiguracaoEstabelecimento;
use App\Models\Paciente;
use App\Models\Profissional;
use App\Models\Sala;
use App\Models\User;
use App\Rules\CnsValido;
use App\Services\TvPairingService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PareamentoSmartTvTest extends TestCase
{
    use RefreshDatabase;

    protected User $coordenador;

    protected User $recepcionista;

    protected User $terapeuta;

    protected Sala $sala;

    protected Paciente $paciente;

    protected Profissional $profissional;

    protected Agendamento $agendamento;

    protected ChamadaPainel $chamadaDoDia;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->coordenador = User::factory()->create([
            'email' => 'coord@cerflow.gov.br',
            'password' => Hash::make('password'),
            'ativo' => true,
        ]);
        $this->coordenador->assignRole('coordenacao');

        $this->recepcionista = User::factory()->create([
            'email' => 'recepcao@cerflow.gov.br',
            'password' => Hash::make('password'),
            'ativo' => true,
        ]);
        $this->recepcionista->assignRole('recepcao');

        $this->terapeuta = User::factory()->create([
            'email' => 'terapeuta@cerflow.gov.br',
            'password' => Hash::make('password'),
            'ativo' => true,
        ]);
        $this->terapeuta->assignRole('terapeuta');

        $this->sala = Sala::create([
            'nome' => 'Consultório 01 - Fisioterapia',
            'tipo' => 'CONSULTORIO',
            'ativo' => true,
        ]);

        $this->paciente = Paciente::create([
            'nome' => 'Carlos Eduardo da Silva Secreto',
            'cpf' => '12345678901',
            'cns' => CnsValido::gerarDefinitivo('12345678901'),
            'data_nascimento' => '1990-01-01',
            'sexo' => 'M',
            'municipio_ibge' => '270430',
            'ativo' => true,
        ]);

        $this->profissional = Profissional::create([
            'user_id' => $this->terapeuta->id,
            'nome' => 'Dr. Fernando Dias',
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
                'painel_token_acesso' => 'TOKEN-GLOBAL-LEGADO-TESTE',
                'painel_permitir_token_legado' => true,
            ]
        );

        $this->chamadaDoDia = ChamadaPainel::create([
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
    }

    /**
     * TESTE 1: Manipulação de $autorizado sem credencial.
     * Tentar forçar $autorizado = true pelo Livewire sem possuir credencial válida no servidor
     * é estritamente bloqueado e NENHUM dado de paciente é exibido.
     */
    public function test_1_manipulacao_de_autorizado_sem_credencial_rejeitada_no_servidor(): void
    {
        $componente = Livewire::test(PainelChamadas::class);

        // Inicialmente desautorizado
        $componente->assertSet('autorizado', false);
        $componente->assertDontSee($this->paciente->nome);

        // Tenta forçar $autorizado = true diretamente (simulando payload forjado)
        try {
            $componente->set('autorizado', true);
        } catch (\Throwable) {
            // #[Locked] pode disparar exceção no Livewire
        }

        // Renderização forçada: o servidor revalida e cancela a autorização
        $componente->assertSet('autorizado', false);
        $componente->assertSee('Conectar este Televisor');
        $componente->assertDontSee($this->paciente->nome);
        $componente->assertDontSee('Carlos Eduardo da Silva Secreto');
    }

    /**
     * TESTE 2: Acesso não autorizado durante atualizações Livewire.
     * Atualizações cíclicas via polling não permitem vazamento sem autorização prévia.
     */
    public function test_2_acesso_nao_autorizado_bloqueado_durante_atualizacoes_livewire(): void
    {
        $componente = Livewire::test(PainelChamadas::class);

        // Executa o ciclo de polling
        $componente->call('atualizarPainel');

        $componente->assertSet('autorizado', false);
        $componente->assertSee('Conectar este Televisor');
        $componente->assertDontSee($this->paciente->nome);
    }

    /**
     * TESTE 3: Revogação imediata da Smart TV.
     * TV conectada que tem seu acesso revogado pela Coordenação perde o acesso no ciclo de polling seguinte.
     */
    public function test_3_revogacao_imediata_da_tv(): void
    {
        $service = app(TvPairingService::class);
        $dados = $service->iniciarSolicitacaoPareamento('10.0.0.1');
        $disp = $service->autorizarCodigo($dados['codigo'], 'TV Recepção Bloco A', $this->coordenador);
        $resultado = $service->verificarStatusPareamento($dados['solicitacao']->solicitacao_uid, $dados['segredo_temporario']);
        $tokenTv = $resultado['token'];

        // TV acessa normalmente com o cookie emitido
        request()->cookies->set(TvPairingService::COOKIE_NAME, $tokenTv);
        $componente = Livewire::withCookie(TvPairingService::COOKIE_NAME, $tokenTv)
            ->test(PainelChamadas::class);
        $componente->assertSet('autorizado', true);
        $componente->assertSee($this->paciente->nome);

        // Coordenação revoga o televisor
        $service->revogarDispositivo($disp, $this->coordenador);

        // No ciclo de polling seguinte, perde a autorização imediatamente
        $componente->call('atualizarPainel');
        $componente->assertSet('autorizado', false);
        $componente->assertSet('revogado', true);
        $componente->assertSee('Dispositivo Não Autorizado');
        $componente->assertDontSee($this->paciente->nome);
    }

    /**
     * TESTE 4: Tentativa de reutilizar credencial revogada.
     * Uma credencial revogada nunca volta a ser aceita, nem via cookie, nem via service.
     */
    public function test_4_tentativa_de_reutilizar_credencial_revogada_falha_sempre(): void
    {
        $service = app(TvPairingService::class);
        $dados = $service->iniciarSolicitacaoPareamento('10.0.0.2');
        $disp = $service->autorizarCodigo($dados['codigo'], 'TV Consultórios 1', $this->coordenador);
        $res = $service->verificarStatusPareamento($dados['solicitacao']->solicitacao_uid, $dados['segredo_temporario']);
        $tokenAntigo = $res['token'];

        // Revoga o televisor
        $service->revogarDispositivo($disp, $this->coordenador);

        // 1. O service rejeita o token revogado
        $this->assertNull($service->autenticarPorCookie($tokenAntigo));

        // 2. Requisição HTTP direta com cookie revogado não exibe painel e não vaza dados
        $response = $this->withCookie(TvPairingService::COOKIE_NAME, $tokenAntigo)
            ->get('/painel-chamadas');
        $response->assertOk();
        $response->assertDontSee($this->paciente->nome);
        $response->assertSee('Dispositivo Não Autorizado');
    }

    /**
     * TESTE 5: Reautorização com novo pareamento.
     * A reautorização exige novo pareamento com código fresco; a credencial antiga permanece revogada.
     */
    public function test_5_reautorizacao_exige_novo_pareamento_e_descarta_credencial_antiga(): void
    {
        $service = app(TvPairingService::class);

        // 1. Cria TV e obtém primeira credencial
        $pareamento1 = $service->iniciarSolicitacaoPareamento('10.0.0.3');
        $disp = $service->autorizarCodigo($pareamento1['codigo'], 'TV Térreo', $this->coordenador);
        $res1 = $service->verificarStatusPareamento($pareamento1['solicitacao']->solicitacao_uid, $pareamento1['segredo_temporario']);
        $tokenAntigo = $res1['token'];

        // 2. Revoga a TV
        $service->revogarDispositivo($disp, $this->coordenador);
        $this->assertFalse($disp->fresh()->estaAtivo());

        // 3. TV gera um NOVO pareamento
        $pareamento2 = $service->iniciarSolicitacaoPareamento('10.0.0.3');
        $novoCodigo = $pareamento2['codigo'];
        $this->assertNotSame($pareamento1['codigo'], $novoCodigo);

        // 4. Coordenação reautoriza informando o novo código exibido na TV
        $service->reautorizarDispositivo($disp, $novoCodigo, $this->coordenador);
        $this->assertTrue($disp->fresh()->estaAtivo());

        // 5. TV obtém a nova credencial definitiva
        $res2 = $service->verificarStatusPareamento($pareamento2['solicitacao']->solicitacao_uid, $pareamento2['segredo_temporario']);
        $tokenNovo = $res2['token'];
        $this->assertNotNull($tokenNovo);
        $this->assertNotSame($tokenAntigo, $tokenNovo);

        // 6. A nova credencial funciona perfeitamente
        $autenticadoNovo = $service->autenticarPorCookie($tokenNovo);
        $this->assertNotNull($autenticadoNovo);
        $this->assertSame($disp->id, $autenticadoNovo->id);

        // 7. A credencial ANTIGA continua estritamente rejeitada para sempre
        $this->assertNull($service->autenticarPorCookie($tokenAntigo));
    }

    /**
     * TESTE 6: Expiração e reutilização de códigos.
     * Códigos expiram após 10 minutos e códigos aprovados/concluídos não podem ser reutilizados.
     */
    public function test_6_expiracao_e_impossibilidade_de_reutilizacao_de_codigos(): void
    {
        $service = app(TvPairingService::class);

        // A. Código expirado é rejeitado
        $dadosExp = $service->iniciarSolicitacaoPareamento('10.0.0.4');
        $dadosExp['solicitacao']->update(['expira_em' => now()->subMinute()]);

        $this->expectException(ValidationException::class);
        $service->autorizarCodigo($dadosExp['codigo'], 'TV Expirada', $this->coordenador);
    }

    public function test_6b_codigo_utilizado_nao_pode_ser_reutilizado(): void
    {
        $service = app(TvPairingService::class);

        // B. Código utilizado não pode ser aprovado novamente
        $dadosUso = $service->iniciarSolicitacaoPareamento('10.0.0.5');
        $service->autorizarCodigo($dadosUso['codigo'], 'TV Uso Único', $this->coordenador);

        $this->expectException(ValidationException::class);
        $service->autorizarCodigo($dadosUso['codigo'], 'TV Segunda Tentativa', $this->coordenador);
    }

    /**
     * TESTE 7: Limite de criação de solicitações (Rate Limiting).
     * Impede flooding de criação de solicitações a partir do mesmo IP.
     */
    public function test_7_limite_de_criacao_de_solicitacoes_rate_limiting(): void
    {
        $service = app(TvPairingService::class);
        $ipTeste = '192.168.100.88';

        // Cria até o limite permitido (10 tentativas)
        for ($i = 0; $i < 10; $i++) {
            $service->iniciarSolicitacaoPareamento($ipTeste);
        }

        // A 11ª chamada deve ser barrada por Rate Limiting
        $this->expectException(ValidationException::class);
        $service->iniciarSolicitacaoPareamento($ipTeste);
    }

    /**
     * TESTE 8: Duas TVs simultâneas e revogação individual.
     * Revogar a TV A não afeta a TV B, mantendo a TV B 100% ativa.
     */
    public function test_8_duas_tvs_simultaneas_e_revogacao_individual(): void
    {
        $service = app(TvPairingService::class);

        // Cria TV A
        $dadosA = $service->iniciarSolicitacaoPareamento('192.168.1.101');
        $dispA = $service->autorizarCodigo($dadosA['codigo'], 'TV Recepção A', $this->coordenador);
        $resA = $service->verificarStatusPareamento($dadosA['solicitacao']->solicitacao_uid, $dadosA['segredo_temporario']);

        // Cria TV B
        $dadosB = $service->iniciarSolicitacaoPareamento('192.168.1.102');
        $dispB = $service->autorizarCodigo($dadosB['codigo'], 'TV Recepção B', $this->coordenador);
        $resB = $service->verificarStatusPareamento($dadosB['solicitacao']->solicitacao_uid, $dadosB['segredo_temporario']);

        // Ambas ativas
        $this->assertNotNull($service->autenticarPorCookie($resA['token']));
        $this->assertNotNull($service->autenticarPorCookie($resB['token']));

        // Revoga apenas a TV A
        $service->revogarDispositivo($dispA, $this->coordenador);

        // TV A deve falhar
        $this->assertNull($service->autenticarPorCookie($resA['token']));

        // TV B continua autorizada sem qualquer interferência
        $authB = $service->autenticarPorCookie($resB['token']);
        $this->assertNotNull($authB);
        $this->assertSame($dispB->id, $authB->id);
    }

    /**
     * TESTE 9: Cookie persistente e seguro.
     * Verifica que o cookie utiliza nome seguro, expiração de 1 ano e não expõe o token em texto puro.
     */
    public function test_9_cookie_persistente_com_atributos_seguros(): void
    {
        $this->assertSame('cer_tv_token', TvPairingService::COOKIE_NAME);
        $this->assertSame(525600, TvPairingService::TEMPO_EXPIRACAO_COOKIE_MINUTOS); // 1 ano

        $service = app(TvPairingService::class);
        $dados = $service->iniciarSolicitacaoPareamento();
        $service->autorizarCodigo($dados['codigo'], 'TV Segura Cookie', $this->coordenador);
        $res = $service->verificarStatusPareamento($dados['solicitacao']->solicitacao_uid, $dados['segredo_temporario']);

        $tokenTv = $res['token'];
        $this->assertNotNull($tokenTv);
        $this->assertSame(64, strlen($tokenTv));

        // Banco de dados armazena apenas hash SHA-256
        $this->assertDatabaseMissing('dispositivos_tv', ['token_hash' => $tokenTv]);
        $this->assertDatabaseHas('dispositivos_tv', ['token_hash' => hash('sha256', $tokenTv)]);
    }

    /**
     * TESTE 10: Isolamento de permissões da TV.
     * O cookie de TV permite acessar apenas /painel-chamadas e é barrado de todas as demais rotas do sistema.
     */
    public function test_10_isolamento_de_permissoes_da_tv(): void
    {
        $service = app(TvPairingService::class);
        $dados = $service->iniciarSolicitacaoPareamento();
        $service->autorizarCodigo($dados['codigo'], 'TV Confinada', $this->coordenador);
        $res = $service->verificarStatusPareamento($dados['solicitacao']->solicitacao_uid, $dados['segredo_temporario']);
        $tokenTv = $res['token'];

        // Acessa /painel-chamadas com sucesso (200)
        $this->withCookie(TvPairingService::COOKIE_NAME, $tokenTv)
            ->get('/painel-chamadas')
            ->assertOk();

        // Bloqueado no Dashboard (redireciona para /login)
        $this->withCookie(TvPairingService::COOKIE_NAME, $tokenTv)
            ->get('/dashboard')
            ->assertRedirect('/login');

        // Bloqueado no Prontuário PEP
        $this->withCookie(TvPairingService::COOKIE_NAME, $tokenTv)
            ->get('/prontuario')
            ->assertRedirect('/login');

        // Bloqueado na Recepção
        $this->withCookie(TvPairingService::COOKIE_NAME, $tokenTv)
            ->get('/recepcao')
            ->assertRedirect('/login');

        // Bloqueado nas Configurações
        $this->withCookie(TvPairingService::COOKIE_NAME, $tokenTv)
            ->get('/configuracoes')
            ->assertRedirect('/login');
    }

    /**
     * TESTE 11: Proteção de dados pessoais dos pacientes (LGPD).
     * O painel nunca expõe CPF, CNS, data de nascimento ou informações clínicas.
     */
    public function test_11_protecao_de_dados_pessoais_dos_pacientes_lgpd(): void
    {
        $service = app(TvPairingService::class);
        $dados = $service->iniciarSolicitacaoPareamento();
        $service->autorizarCodigo($dados['codigo'], 'TV Sala de Espera', $this->coordenador);
        $res = $service->verificarStatusPareamento($dados['solicitacao']->solicitacao_uid, $dados['segredo_temporario']);
        $tokenTv = $res['token'];

        $response = $this->withCookie(TvPairingService::COOKIE_NAME, $tokenTv)
            ->get('/painel-chamadas');

        $response->assertOk();
        // Exibe nome do paciente e sala
        $response->assertSee('Carlos Eduardo da Silva Secreto');
        $response->assertSee('Consultório 01 - Fisioterapia');

        // NUNCA exibe dados sensíveis ou cadastrais
        $response->assertDontSee($this->paciente->cpf);
        $response->assertDontSee($this->paciente->cns);
        $response->assertDontSee('1990-01-01');
        $response->assertDontSee('CID');
        $response->assertDontSee('Diagnóstico');
    }

    /**
     * TESTE 12: Concorrência na emissão das credenciais e uso único.
     * Transação com lock pessimista garante que apenas uma requisição consome o pareamento.
     */
    public function test_12_concorrencia_na_emissao_das_credenciais_e_uso_unico(): void
    {
        $service = app(TvPairingService::class);
        $dados = $service->iniciarSolicitacaoPareamento('10.0.0.99');
        $service->autorizarCodigo($dados['codigo'], 'TV Concorrência', $this->coordenador);

        // Primeira chamada conclui e emite o token
        $primeiraTentativa = $service->verificarStatusPareamento($dados['solicitacao']->solicitacao_uid, $dados['segredo_temporario']);
        $this->assertSame('APROVADO', $primeiraTentativa['status']);
        $this->assertNotNull($primeiraTentativa['token']);

        // Segunda chamada imediata (simulando requisições paralelas) encontra status CONCLUIDO e não emite outro token
        $segundaTentativa = $service->verificarStatusPareamento($dados['solicitacao']->solicitacao_uid, $dados['segredo_temporario']);
        $this->assertSame('CONCLUIDO', $segundaTentativa['status']);
        $this->assertNull($segundaTentativa['token']);
    }
}
