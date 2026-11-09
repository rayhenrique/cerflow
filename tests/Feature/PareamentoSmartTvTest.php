<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\ConfiguracoesSistema;
use App\Livewire\PainelChamadas;
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
            'nome' => 'Consultório 01 - Teste TV',
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
    }

    public function test_tv_nao_autorizada_nao_exibe_dados_de_pacientes_e_gera_codigo_de_ativacao(): void
    {
        // Cria uma chamada no banco para o dia atual
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

        // Acessa o componente como TV anônima (sem cookie e sem usuário)
        $componente = Livewire::test(PainelChamadas::class);

        $componente->assertSet('autorizado', false);
        $componente->assertSee('Conectar este Televisor');
        $componente->assertSee('Código de Ativação do Dispositivo');

        // NUNCA pode exibir o nome do paciente antes da autorização
        $componente->assertDontSee($this->paciente->nome);
        $componente->assertDontSee('Carlos Eduardo');

        // Um código de ativação válido foi gerado e salvo no banco
        $codigo = $componente->get('codigoAtivacao');
        $this->assertNotNull($codigo);
        $this->assertDatabaseHas('solicitacoes_pareamento_tv', [
            'codigo' => $codigo,
            'status' => 'PENDENTE',
        ]);
    }

    public function test_codigo_valido_pode_ser_aprovado_pela_coordenacao(): void
    {
        $service = app(TvPairingService::class);
        $dados = $service->iniciarSolicitacaoPareamento('192.168.1.50', 'LG WebOS TV');

        // Coordenador autoriza a TV
        Livewire::actingAs($this->coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'chamadas'])
            ->set('tv_codigo_ativacao', $dados['codigo'])
            ->set('tv_nome_dispositivo', 'TV Sala de Espera Térreo')
            ->call('autorizarTv')
            ->assertHasNoErrors();

        // O dispositivo foi criado no banco
        $this->assertDatabaseHas('dispositivos_tv', [
            'nome' => 'TV Sala de Espera Térreo',
            'status' => 'ATIVO',
            'autorizado_por_id' => $this->coordenador->id,
        ]);

        // A solicitação de pareamento foi aprovada
        $this->assertDatabaseHas('solicitacoes_pareamento_tv', [
            'id' => $dados['solicitacao']->id,
            'status' => 'APROVADO',
        ]);

        // No próximo polling da TV, o pareamento é consumido e o cookie é emitido
        $verificacao = $service->verificarStatusPareamento($dados['solicitacao']->solicitacao_uid, $dados['segredo_temporario']);
        $this->assertSame('APROVADO', $verificacao['status']);
        $this->assertNotNull($verificacao['token']);
        $this->assertInstanceOf(DispositivoTv::class, $verificacao['dispositivo']);

        // A solicitação é finalizada
        $this->assertDatabaseHas('solicitacoes_pareamento_tv', [
            'id' => $dados['solicitacao']->id,
            'status' => 'CONCLUIDO',
        ]);
    }

    public function test_codigo_expirado_apos_10_minutos_e_rejeitado(): void
    {
        $service = app(TvPairingService::class);
        $dados = $service->iniciarSolicitacaoPareamento('192.168.1.50', 'Samsung Tizen');

        // Simula expiração do código (11 minutos atrás)
        $dados['solicitacao']->update([
            'expira_em' => now()->subMinute(),
        ]);

        $this->expectException(ValidationException::class);
        $service->autorizarCodigo($dados['codigo'], 'TV Antiga', $this->coordenador);
    }

    public function test_codigo_utilizado_nao_pode_ser_reutilizado(): void
    {
        $service = app(TvPairingService::class);
        $dados = $service->iniciarSolicitacaoPareamento('192.168.1.50', 'Samsung Tizen');

        // Primeira aprovação tem sucesso
        $service->autorizarCodigo($dados['codigo'], 'TV Primeira', $this->coordenador);

        // Segunda tentativa com o mesmo código deve falhar
        $this->expectException(ValidationException::class);
        $service->autorizarCodigo($dados['codigo'], 'TV Tentativa Duplicada', $this->coordenador);
    }

    public function test_usuario_sem_permissao_de_coordenacao_nao_pode_autorizar_tv(): void
    {
        $service = app(TvPairingService::class);
        $dados = $service->iniciarSolicitacaoPareamento('192.168.1.50', 'TCL Android TV');

        // Recepcionista não tem permissão de autorizar TV (deve retornar 403)
        Livewire::actingAs($this->recepcionista)
            ->test(ConfiguracoesSistema::class)
            ->set('tv_codigo_ativacao', $dados['codigo'])
            ->set('tv_nome_dispositivo', 'TV Hackeada')
            ->call('autorizarTv')
            ->assertForbidden();

        // Terapeuta também não tem permissão
        Livewire::actingAs($this->terapeuta)
            ->test(ConfiguracoesSistema::class)
            ->set('tv_codigo_ativacao', $dados['codigo'])
            ->set('tv_nome_dispositivo', 'TV Hackeada 2')
            ->call('autorizarTv')
            ->assertForbidden();
    }

    public function test_cookie_da_tv_permite_acesso_ao_painel_mas_nao_as_outras_areas_do_sistema(): void
    {
        $service = app(TvPairingService::class);
        $dados = $service->iniciarSolicitacaoPareamento();
        $service->autorizarCodigo($dados['codigo'], 'TV Saguão', $this->coordenador);
        $resultado = $service->verificarStatusPareamento($dados['solicitacao']->solicitacao_uid, $dados['segredo_temporario']);

        $tokenTv = $resultado['token'];
        $this->assertNotNull($tokenTv);

        // 1. O cookie permite acessar o painel de chamadas
        $responsePainel = $this->withCookie(TvPairingService::COOKIE_NAME, $tokenTv)
            ->get('/painel-chamadas');
        $responsePainel->assertOk();
        $responsePainel->assertSee('TV Saguão');

        // 2. O mesmo cookie NÃO autentica em rotas protegidas (deve redirecionar para /login)
        $this->withCookie(TvPairingService::COOKIE_NAME, $tokenTv)
            ->get('/dashboard')
            ->assertRedirect('/login');

        $this->withCookie(TvPairingService::COOKIE_NAME, $tokenTv)
            ->get('/prontuario')
            ->assertRedirect('/login');

        $this->withCookie(TvPairingService::COOKIE_NAME, $tokenTv)
            ->get('/recepcao')
            ->assertRedirect('/login');
    }

    public function test_dispositivo_revogado_perde_acesso_imediatamente(): void
    {
        $service = app(TvPairingService::class);
        $dados = $service->iniciarSolicitacaoPareamento();
        $disp = $service->autorizarCodigo($dados['codigo'], 'TV Recepção 2', $this->coordenador);
        $resultado = $service->verificarStatusPareamento($dados['solicitacao']->solicitacao_uid, $dados['segredo_temporario']);
        $tokenTv = $resultado['token'];

        // TV acessa com sucesso
        request()->cookies->set(TvPairingService::COOKIE_NAME, $tokenTv);
        $componente = Livewire::withCookie(TvPairingService::COOKIE_NAME, $tokenTv)
            ->test(PainelChamadas::class);
        $componente->assertSet('autorizado', true);

        // Coordenação revoga o dispositivo
        $service->revogarDispositivo($disp, $this->coordenador);
        $this->assertFalse($disp->fresh()->estaAtivo());

        // No próximo ciclo de polling / requisição, o acesso é revogado imediatamente
        $componente->call('atualizarPainel');
        $componente->assertSet('autorizado', false);
        $componente->assertSet('revogado', true);
        $componente->assertSee('Dispositivo Não Autorizado');
    }

    public function test_revogacao_de_uma_tv_nao_afeta_outras_tvs_autorizadas(): void
    {
        $service = app(TvPairingService::class);

        // Cria TV 1
        $dados1 = $service->iniciarSolicitacaoPareamento();
        $disp1 = $service->autorizarCodigo($dados1['codigo'], 'TV Sala 1', $this->coordenador);
        $res1 = $service->verificarStatusPareamento($dados1['solicitacao']->solicitacao_uid, $dados1['segredo_temporario']);

        // Cria TV 2
        $dados2 = $service->iniciarSolicitacaoPareamento();
        $disp2 = $service->autorizarCodigo($dados2['codigo'], 'TV Sala 2', $this->coordenador);
        $res2 = $service->verificarStatusPareamento($dados2['solicitacao']->solicitacao_uid, $dados2['segredo_temporario']);

        // Revoga apenas a TV 1
        $service->revogarDispositivo($disp1, $this->coordenador);

        // TV 1 deve ser recusada
        $this->assertNull($service->autenticarPorCookie($res1['token']));

        // TV 2 continua 100% ativa e autorizada
        $autenticada2 = $service->autenticarPorCookie($res2['token']);
        $this->assertNotNull($autenticada2);
        $this->assertSame($disp2->id, $autenticada2->id);
    }

    public function test_token_permanente_nunca_e_salvo_em_texto_puro_no_banco_nem_no_html(): void
    {
        $service = app(TvPairingService::class);
        $dados = $service->iniciarSolicitacaoPareamento();
        $service->autorizarCodigo($dados['codigo'], 'TV Segura', $this->coordenador);
        $res = $service->verificarStatusPareamento($dados['solicitacao']->solicitacao_uid, $dados['segredo_temporario']);

        $tokenTv = $res['token'];
        $this->assertNotNull($tokenTv);

        // O token em texto puro NÃO pode estar gravado em nenhuma coluna da tabela dispositivos_tv
        $this->assertDatabaseMissing('dispositivos_tv', ['token_hash' => $tokenTv]);

        // Apenas o hash SHA-256 é armazenado
        $this->assertDatabaseHas('dispositivos_tv', [
            'token_hash' => hash('sha256', $tokenTv),
        ]);

        // Na renderização do painel, o token em texto puro não aparece no HTML
        $response = $this->withCookie(TvPairingService::COOKIE_NAME, $tokenTv)
            ->get('/painel-chamadas');
        $response->assertDontSee($tokenTv);
    }
}
