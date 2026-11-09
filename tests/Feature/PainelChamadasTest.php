<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\ConfiguracoesSistema;
use App\Livewire\PainelChamadas;
use App\Livewire\RecepcaoCheckin;
use App\Models\Agendamento;
use App\Models\ChamadaPainel;
use App\Models\ConfiguracaoEstabelecimento;
use App\Models\Paciente;
use App\Models\Profissional;
use App\Models\Sala;
use App\Models\User;
use App\Rules\CnsValido;
use App\Services\ChamadaPacienteService;
use Carbon\CarbonImmutable;
use Database\Seeders\ProcedimentoSigtapSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SalaSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PainelChamadasTest extends TestCase
{
    use RefreshDatabase;

    private User $coordenador;

    private User $recepcionista;

    private User $terapeuta1;

    private User $terapeuta2;

    private Profissional $profissional1;

    private Profissional $profissional2;

    private Sala $sala;

    private Paciente $paciente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RoleAndPermissionSeeder::class,
            ProcedimentoSigtapSeeder::class,
            SalaSeeder::class,
        ]);

        $this->coordenador = User::factory()->create(['ativo' => true]);
        $this->coordenador->assignRole('coordenacao');

        $this->recepcionista = User::factory()->create(['ativo' => true]);
        $this->recepcionista->assignRole('recepcao');

        $this->terapeuta1 = User::factory()->create(['ativo' => true]);
        $this->terapeuta1->assignRole('terapeuta');
        $this->profissional1 = Profissional::create([
            'user_id' => $this->terapeuta1->id,
            'nome' => 'Dr. André Fisioterapeuta',
            'cpf' => '11122233344',
            'cns' => CnsValido::gerarDefinitivo('11122233344'),
            'registro_conselho' => 'CREFITO-1 1234-F',
            'cbo_padrao' => '223605',
            'especialidade' => 'Fisioterapia',
            'pin_assinatura_hash' => Hash::make('1234'),
            'ativo' => true,
        ]);

        $this->terapeuta2 = User::factory()->create(['ativo' => true]);
        $this->terapeuta2->assignRole('terapeuta');
        $this->profissional2 = Profissional::create([
            'user_id' => $this->terapeuta2->id,
            'nome' => 'Dra. Beatriz Terapeuta Ocupacional',
            'cpf' => '22233344455',
            'cns' => CnsValido::gerarDefinitivo('22233344455'),
            'registro_conselho' => 'CREFITO-1 5678-TO',
            'cbo_padrao' => '223905',
            'especialidade' => 'Terapia Ocupacional',
            'pin_assinatura_hash' => Hash::make('1234'),
            'ativo' => true,
        ]);

        $this->sala = Sala::first() ?? Sala::create(['nome' => 'Consultório 01', 'tipo' => 'CONSULTORIO', 'ativo' => true]);

        $this->paciente = Paciente::create([
            'nome' => 'Carlos Eduardo da Silva',
            'cpf' => '33344455566',
            'cns' => CnsValido::gerarDefinitivo('33344455566'),
            'data_nascimento' => '1990-05-15',
            'sexo' => 'M',
            'nome_mae' => 'Maria Silva',
            'municipio_ibge' => '270430',
            'cid_principal' => 'G80.0',
            'ativo' => true,
        ]);
    }

    public function test_chamar_somente_pacientes_com_status_presente(): void
    {
        $hoje = CarbonImmutable::now();
        $service = app(ChamadaPacienteService::class);

        $agendamentoAgendado = Agendamento::create([
            'paciente_id' => $this->paciente->id,
            'sala_id' => $this->sala->id,
            'data_atendimento' => $hoje->toDateString(),
            'hora_inicio' => '08:00',
            'hora_fim' => '08:30',
            'dia_semana' => 'SEGUNDA',
            'tipo_atendimento' => 'INDIVIDUAL',
            'status' => 'AGENDADO',
        ]);
        $agendamentoAgendado->profissionais()->sync([$this->profissional1->id]);

        $this->expectException(ValidationException::class);
        $service->chamarPaciente($agendamentoAgendado, $this->terapeuta1);
    }

    public function test_chamar_paciente_presente_registra_chamada_e_nao_altera_status_para_atendido(): void
    {
        $hoje = CarbonImmutable::now();
        $service = app(ChamadaPacienteService::class);

        $agendamento = Agendamento::create([
            'paciente_id' => $this->paciente->id,
            'sala_id' => $this->sala->id,
            'data_atendimento' => $hoje->toDateString(),
            'hora_inicio' => '08:00',
            'hora_fim' => '08:30',
            'dia_semana' => 'SEGUNDA',
            'tipo_atendimento' => 'INDIVIDUAL',
            'status' => 'PRESENTE',
        ]);
        $agendamento->profissionais()->sync([$this->profissional1->id]);

        $chamada = $service->chamarPaciente($agendamento, $this->terapeuta1);

        $this->assertInstanceOf(ChamadaPainel::class, $chamada);
        $this->assertSame('CHAMADA', $chamada->tipo);
        $this->assertSame('CHAMANDO', $chamada->status);
        $this->assertSame($this->paciente->nome, $chamada->paciente_nome);
        $this->assertSame($this->sala->nome, $chamada->sala_nome);
        $this->assertSame('Dr. André Fisioterapeuta', $chamada->profissional_nome);

        // Agendamento deve continuar PRESENTE (não se altera para ATENDIDO)
        $agendamento->refresh();
        $this->assertSame('PRESENTE', $agendamento->status);

        $this->assertDatabaseHas('chamadas_painel', [
            'id' => $chamada->id,
            'agendamento_id' => $agendamento->id,
            'tipo' => 'CHAMADA',
        ]);
    }

    public function test_restringir_chamadas_aos_profissionais_autorizados(): void
    {
        $hoje = CarbonImmutable::now();
        $service = app(ChamadaPacienteService::class);

        $agendamento = Agendamento::create([
            'paciente_id' => $this->paciente->id,
            'sala_id' => $this->sala->id,
            'data_atendimento' => $hoje->toDateString(),
            'hora_inicio' => '09:00',
            'hora_fim' => '09:30',
            'dia_semana' => 'SEGUNDA',
            'tipo_atendimento' => 'INDIVIDUAL',
            'status' => 'PRESENTE',
        ]);
        $agendamento->profissionais()->sync([$this->profissional1->id]);

        // Terapeuta 1 é vinculado -> pode chamar
        $this->assertTrue($service->podeChamar($agendamento, $this->terapeuta1));

        // Terapeuta 2 NÃO é vinculado -> não pode chamar
        $this->assertFalse($service->podeChamar($agendamento, $this->terapeuta2));

        // Recepção e Coordenação podem chamar qualquer agendamento presente
        $this->assertTrue($service->podeChamar($agendamento, $this->recepcionista));
        $this->assertTrue($service->podeChamar($agendamento, $this->coordenador));

        // Tentar chamar com terapeuta 2 deve lançar AuthorizationException
        $this->expectException(AuthorizationException::class);
        $service->chamarPaciente($agendamento, $this->terapeuta2);
    }

    public function test_repetir_chamada_registra_tipo_repeticao_no_historico(): void
    {
        $hoje = CarbonImmutable::now();
        $service = app(ChamadaPacienteService::class);

        $agendamento = Agendamento::create([
            'paciente_id' => $this->paciente->id,
            'sala_id' => $this->sala->id,
            'data_atendimento' => $hoje->toDateString(),
            'hora_inicio' => '10:00',
            'hora_fim' => '10:30',
            'dia_semana' => 'SEGUNDA',
            'tipo_atendimento' => 'INDIVIDUAL',
            'status' => 'PRESENTE',
        ]);
        $agendamento->profissionais()->sync([$this->profissional1->id]);

        $chamada1 = $service->chamarPaciente($agendamento, $this->terapeuta1);
        $chamada2 = $service->repetirChamada($agendamento->id, $this->terapeuta1);

        $this->assertSame('CHAMADA', $chamada1->tipo);
        $this->assertSame('REPETICAO', $chamada2->tipo);

        $this->assertCount(2, $agendamento->chamadas()->get());
        $this->assertEquals($chamada2->id, $agendamento->ultimaChamada()->first()?->id);
    }

    public function test_atendimento_compartilhado_gera_chamada_unica_com_nomes_combinados(): void
    {
        $hoje = CarbonImmutable::now();
        $service = app(ChamadaPacienteService::class);

        $agendamento = Agendamento::create([
            'paciente_id' => $this->paciente->id,
            'sala_id' => $this->sala->id,
            'data_atendimento' => $hoje->toDateString(),
            'hora_inicio' => '10:30',
            'hora_fim' => '11:15',
            'dia_semana' => 'SEGUNDA',
            'tipo_atendimento' => 'COMPARTILHADO',
            'status' => 'PRESENTE',
        ]);
        $agendamento->profissionais()->sync([$this->profissional1->id, $this->profissional2->id]);

        $chamada = $service->chamarPaciente($agendamento, $this->terapeuta1);

        $this->assertStringContainsString('Dr. André Fisioterapeuta', $chamada->profissional_nome);
        $this->assertStringContainsString('Dra. Beatriz Terapeuta Ocupacional', $chamada->profissional_nome);
        $this->assertStringContainsString(' + ', $chamada->profissional_nome);

        $this->assertEquals(1, ChamadaPainel::where('agendamento_id', $agendamento->id)->count());
    }

    public function test_painel_tv_autorizacao_por_token_ou_usuario_autenticado(): void
    {
        $configuracao = ConfiguracaoEstabelecimento::atual();
        $token = $configuracao->painel_token_acesso;

        // Acesso anônimo sem token -> exibe tela de ativação de Smart TV (200) sem expor chamadas
        $this->get('/painel-chamadas')
            ->assertStatus(200)
            ->assertSee('Conectar este Televisor')
            ->assertDontSee('Chamada Atual');

        // Acesso anônimo com token incorreto -> exibe tela de ativação (200)
        $this->get('/painel-chamadas?token=token_invalido_xyz')
            ->assertStatus(200)
            ->assertSee('Conectar este Televisor')
            ->assertDontSee('Chamada Atual');

        // Acesso anônimo com token correto -> 200
        $this->get("/painel-chamadas?token={$token}")->assertStatus(200);

        // Acesso autenticado (ex: recepção) sem token -> 200
        $this->actingAs($this->recepcionista)->get('/painel-chamadas')->assertStatus(200);
    }

    public function test_protecao_contra_vazamento_de_dados_clinicos_no_painel_tv(): void
    {
        $configuracao = ConfiguracaoEstabelecimento::atual();
        $token = $configuracao->painel_token_acesso;

        $agendamento = Agendamento::create([
            'paciente_id' => $this->paciente->id,
            'sala_id' => $this->sala->id,
            'data_atendimento' => CarbonImmutable::now()->toDateString(),
            'hora_inicio' => '11:00',
            'hora_fim' => '11:30',
            'dia_semana' => 'SEGUNDA',
            'tipo_atendimento' => 'INDIVIDUAL',
            'status' => 'PRESENTE',
        ]);
        $agendamento->profissionais()->sync([$this->profissional1->id]);

        app(ChamadaPacienteService::class)->chamarPaciente($agendamento, $this->terapeuta1);

        $response = $this->get("/painel-chamadas?token={$token}");
        $response->assertStatus(200);

        // Garante que dados clínicos e demográficos sensíveis NÃO são vazados na view da TV
        $response->assertDontSee($this->paciente->cpf);
        $response->assertDontSee($this->paciente->cns);
        $response->assertDontSee('G80.0');
        $response->assertDontSee('1990-05-15');
        $response->assertDontSee('Maria Silva');

        // Confere presença do nome do paciente e da sala
        $response->assertSee($this->paciente->nome);
        $response->assertSee($this->sala->nome);
    }

    public function test_modos_de_exibicao_do_nome_do_paciente_para_privacidade(): void
    {
        $configuracao = ConfiguracaoEstabelecimento::atual();

        $nome = 'Carlos Eduardo da Silva';

        $configuracao->painel_modo_exibicao_nome = 'COMPLETO';
        $this->assertSame('Carlos Eduardo da Silva', $configuracao->formatarNomePaciente($nome));

        $configuracao->painel_modo_exibicao_nome = 'PRIMEIRO_NOME';
        $this->assertSame('Carlos', $configuracao->formatarNomePaciente($nome));

        $configuracao->painel_modo_exibicao_nome = 'PRIMEIRO_E_ULTIMO';
        $this->assertSame('Carlos Silva', $configuracao->formatarNomePaciente($nome));

        $configuracao->painel_modo_exibicao_nome = 'ABREVIADO';
        $this->assertSame('Carlos E. Silva', $configuracao->formatarNomePaciente($nome));
    }

    public function test_ordenacao_do_historico_no_painel_tv(): void
    {
        $configuracao = ConfiguracaoEstabelecimento::atual();
        $token = $configuracao->painel_token_acesso;

        $paciente2 = Paciente::create([
            'nome' => 'Mariana Oliveira Costa',
            'data_nascimento' => '1995-02-10',
            'sexo' => 'F',
            'municipio_ibge' => '270430',
            'ativo' => true,
        ]);

        $hoje = CarbonImmutable::now();

        $agendamento1 = Agendamento::create([
            'paciente_id' => $this->paciente->id,
            'sala_id' => $this->sala->id,
            'data_atendimento' => $hoje->toDateString(),
            'hora_inicio' => '13:00',
            'hora_fim' => '13:30',
            'dia_semana' => 'SEGUNDA',
            'tipo_atendimento' => 'INDIVIDUAL',
            'status' => 'PRESENTE',
        ]);

        $agendamento2 = Agendamento::create([
            'paciente_id' => $paciente2->id,
            'sala_id' => $this->sala->id,
            'data_atendimento' => $hoje->toDateString(),
            'hora_inicio' => '13:30',
            'hora_fim' => '14:00',
            'dia_semana' => 'SEGUNDA',
            'tipo_atendimento' => 'INDIVIDUAL',
            'status' => 'PRESENTE',
        ]);

        $chamada1 = ChamadaPainel::create([
            'agendamento_id' => $agendamento1->id,
            'paciente_id' => $this->paciente->id,
            'sala_id' => $this->sala->id,
            'chamado_por_id' => $this->terapeuta1->id,
            'paciente_nome' => $this->paciente->nome,
            'sala_nome' => $this->sala->nome,
            'profissional_nome' => 'Dr. André Fisioterapeuta',
            'tipo' => 'CHAMADA',
            'status' => 'CHAMANDO',
            'chamado_em' => $hoje->subMinutes(10),
        ]);

        $chamada2 = ChamadaPainel::create([
            'agendamento_id' => $agendamento2->id,
            'paciente_id' => $paciente2->id,
            'sala_id' => $this->sala->id,
            'chamado_por_id' => $this->terapeuta1->id,
            'paciente_nome' => $paciente2->nome,
            'sala_nome' => $this->sala->nome,
            'profissional_nome' => 'Dr. André Fisioterapeuta',
            'tipo' => 'CHAMADA',
            'status' => 'CHAMANDO',
            'chamado_em' => $hoje->subMinutes(2),
        ]);

        Livewire::withQueryParams(['token' => $token])
            ->test(PainelChamadas::class)
            ->assertSet('ultimaChamadaId', $chamada2->id)
            ->assertSee($chamada2->paciente_nome)
            ->assertSee($chamada1->paciente_nome);
    }

    public function test_coordenacao_atualiza_configuracoes_do_painel_e_regenera_token(): void
    {
        $configuracao = ConfiguracaoEstabelecimento::atual();
        $tokenOriginal = $configuracao->painel_token_acesso;

        Livewire::actingAs($this->coordenador)
            ->test(ConfiguracoesSistema::class)
            ->set('abaAtiva', 'chamadas')
            ->set('painel_voz_ativa', false)
            ->set('painel_volume', 60)
            ->set('painel_tempo_destaque_segundos', 25)
            ->set('painel_quantidade_historico', 6)
            ->set('painel_modo_exibicao_nome', 'PRIMEIRO_E_ULTIMO')
            ->call('salvarConfiguracaoPainelChamadas')
            ->assertDispatched('toast');

        $configuracao->refresh();
        $this->assertFalse((bool) $configuracao->painel_voz_ativa);
        $this->assertSame(60, $configuracao->painel_volume);
        $this->assertSame(25, $configuracao->painel_tempo_destaque_segundos);
        $this->assertSame(6, $configuracao->painel_quantidade_historico);
        $this->assertSame('PRIMEIRO_E_ULTIMO', $configuracao->painel_modo_exibicao_nome);

        // Regenerar token
        Livewire::actingAs($this->coordenador)
            ->test(ConfiguracoesSistema::class)
            ->set('abaAtiva', 'chamadas')
            ->call('regenerarTokenTv')
            ->assertDispatched('toast');

        $configuracao->refresh();
        $this->assertNotSame($tokenOriginal, $configuracao->painel_token_acesso);
        $this->assertNotEmpty($configuracao->painel_token_acesso);
    }

    public function test_recepcao_checkin_chama_e_repete_paciente_via_livewire(): void
    {
        $hoje = CarbonImmutable::now();
        $agendamento = Agendamento::create([
            'paciente_id' => $this->paciente->id,
            'sala_id' => $this->sala->id,
            'data_atendimento' => $hoje->toDateString(),
            'hora_inicio' => '14:00',
            'hora_fim' => '14:30',
            'dia_semana' => 'SEGUNDA',
            'tipo_atendimento' => 'INDIVIDUAL',
            'status' => 'PRESENTE',
        ]);
        $agendamento->profissionais()->sync([$this->profissional1->id]);

        Livewire::actingAs($this->recepcionista)
            ->test(RecepcaoCheckin::class)
            ->call('chamarPaciente', $agendamento->id)
            ->assertDispatched('toast');

        $this->assertDatabaseHas('chamadas_painel', [
            'agendamento_id' => $agendamento->id,
            'tipo' => 'CHAMADA',
        ]);

        Livewire::actingAs($this->recepcionista)
            ->test(RecepcaoCheckin::class)
            ->call('repetirChamada', $agendamento->id)
            ->assertDispatched('toast');

        $this->assertDatabaseHas('chamadas_painel', [
            'agendamento_id' => $agendamento->id,
            'tipo' => 'REPETICAO',
        ]);
    }
}
